<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
//include_once "mainTopTabs.php";
include_once "../../../_globalFunction.php";

const BASE_LANG_ID = 1;

$cleanTime = [1 => '15 דקות', 2 => '30 דקות', 3 => '45 דקות', 4 => 'שעה', 6 => 'שעה וחצי', 8 => 'שעתיים'];

$pageID = intval($_POST['pageID'] ?? $_GET['pageID'] ?? 0);
$siteID = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);
$siteName = $_GET['siteName'];

$paymentsOpt = [1 => 'מזומן', 2 => 'צ\'ק' , 4 => 'ישראכארד', 8 => 'מאסטרכארד', 16 => 'ויזה' , 32 => 'דיינרס', 64 => 'אמריקן אקספרס'];



if ('POST' == $_SERVER['REQUEST_METHOD']){

    $isError = '';
    try {

        $active = 0;
        if ($siteID)
            list($active) = udb::single_row("SELECT `active` FROM `therapists` WHERE `siteID` = " . $siteID, UDB_NUMERIC);

        $data = typemap($_POST, [
            'siteName'   => 'string',
            'phone'      => 'string',
            'email'      => 'email',
            'active'      => 'int',
            'gender_self'      => 'int',
            'gender_client'      => 'int',
            'address'   => 'string',
            'password'  => 'string',
            'bankName'  => 'string',
            'charge' => 'float',
            'bankNumber'  => 'string',
            'bankBranch'  => 'string',
            'bankAccount'  => 'string',
            'bankAcoountOwner'  => 'string',
            'attributes' => ['int' => 'int']
        ]);
        if (!$data['siteName'])
            throw new LocalException('חייב להיות שם בעברית');


        $bankData = [
            'bankName'  => $data['bankName'],
            'bankNumber'  => $data['bankNumber'],
            'bankBranch'  => $data['bankBranch'],
            'bankAccount'  => $data['bankAccount'],
            'bankAcoountOwner'  => $data['bankAcoountOwner']
        ];

        $bankData = json_encode($bankData, true);

        // main site data
        $siteData = [
            'active'       => $data['active'][1] ?? 0,
            'siteName'     => $data['siteName'],
            'email'        => $data['email'],
            'address'        => $data['address'],
            'charge'        => $data['charge'],
            'gender_self'  => $data['gender_self'],
            'gender_client' => $data['gender_client'],
            'phone'        => $data['phone'],
            'active'        => $data['active'],
            'bankData' => $bankData
        ];


        //save attributes

        if (!$siteID){
            $siteID = udb::insert('therapists', $siteData);

        } else {
            udb::update('therapists', $siteData, '`siteID` = ' . $siteID);
        }

        $olda = udb::single_column("select treatmentID from therapists_treats where therapistID=".$siteID);
        if($data['attributes'] && count($data['attributes'])){
            $que = [];
            foreach($data['attributes'] as $attr)
                $que[] = '(' . $siteID . ', ' . $attr . ')';
            $upsql = "INSERT INTO `therapists_treats` (`therapistID`, `treatmentID` ) VALUES" . implode(',', $que) . " ON DUPLICATE KEY UPDATE `therapistID` = VALUES(`therapistID`)";
            udb::query($upsql);
            unset($que);
        }


        $new = array_diff($data['attributes'], $olda);
        if($data['attributes'] && $olda){
            if ($old = array_diff($olda, $data['attributes'])) {
                udb::query("DELETE FROM `therapists_treats` WHERE `therapistID` = " . $siteID . " AND `attrID` IN (" . implode(',', $old) . ")");
            }

        }
        else {
            if(!$data['attributes'] || count($data['attributes']) == 0) {
                udb::query('DELETE FROM `therapists_treats` WHERE therapistID=' . $siteID );
            }
        }

    }
    catch (LocalException $e){
        // show error
        $isError = $e->getMessage();
    } ?>
    <script>
        <?if($isError) {?>
        alert('<?=$isError?>');
        <?} else
            {?>
        //<?=$upsql?>
        //window.parent.location.reload(); window.parent.closeTab();
        <?}?>
    </script>
    <?php

}

$siteData = $siteDomains = $siteLangs = [];
$domainID = DomainList::active();
$langID   = LangList::active();
$treatments = udb::full_list("SELECT * FROM `treatments`");
$tTreats = udb::single_column("select treatmentID from therapists_treats where therapistID=".$siteID);
if ($siteID){
    $siteData    = udb::single_row("SELECT * FROM `therapists` WHERE  `therapists`.`siteID` = " . $siteID);
}
?>
<!--  -->
<div class="editItems">
	<div class="popGallery">
		<div class="popGalleryCont"></div>
	</div>
	<div class="siteMainTitle"><?=($siteName?$siteName:"מטפל חדש")?></div>
    <?//=showTopTabs()?>
    <div class="inputLblWrap langsdom domainsHide">
		<div class="labelTo">דומיין</div>
        <?=DomainList::html_select()?>
	</div>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
        <?=LangList::html_select()?>
	</div>
	<div class="frameContent">
		<form method="post" enctype="multipart/form-data" >
			<div class="mainSectionWrapper">
				<div class="sectionName">כללי</div>
			<div class="inputLblWrap">
				<div class="labelTo">שם המטפל</div>
				<input type="text" placeholder="שם המטפל" name="siteName" value="<?=js_safe($siteData['siteName'])?>" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">כתובת</div>
				<input type="text" placeholder="כתובת" name="address" value="<?=js_safe($siteData['address'])?>" />
			</div>
            <div class="inputLblWrap">
			<div class="labelTo">אימייל</div>
			<input type="text" placeholder="אימייל" name="email" value="<?=$siteData['email']?>" />
		</div>
		 <div class="inputLblWrap">
            <div class="labelTo">סיסמא</div>
            <input type="text" placeholder="<?=($siteData['password'] ? '*********' : 'סיסמא')?>" name="password" value="" />
        </div>
		<div class="inputLblWrap">
			<div class="labelTo">מגדר</div>
			<select name="gender_self">
				<option value="0" <?=!$siteData['gender_self']?"selected='selected'":""?>>- בחירה -</option>
				<option value="1" <?=$siteData['gender_self'] == 1?"selected='selected'":""?>>גבר</option>
				<option value="2" <?=$siteData['gender_self'] == 2?"selected='selected'":""?>>אישה</option>
			</select>
		</div>
		<div class="inputLblWrap">
			<div class="labelTo">מגדר מועדף</div>
			<select name="gender_client">
				<option value="0" <?=!$siteData['gender_client']?"selected='selected'":""?>>- בחירה -</option>
				<option value="1" <?=$siteData['gender_client'] == 1?"selected='selected'":""?>>גבר</option>
				<option value="2" <?=$siteData['gender_client'] == 2?"selected='selected'":""?>>אישה</option>
				<option value="3" <?=$siteData['gender_client'] == 3?"selected='selected'":""?>>לא משנה</option>
			</select>
		</div>
		<div class="inputLblWrap">
			<div class="labelTo">אחוזי עמלה</div>
			<div style="display:inline-block;width:100%;max-width:calc(100% - 20px)"><input type="text" placeholder="charge" name="charge" value="<?=$siteData['charge']?>" /></div>
			<div style="display:inline-block;width:100%;max-width:15px">%</div>

		</div>



			<div class="inputLblWrap">
				<div class="labelTo">טלפון</div>
				<input type="text" placeholder="טלפון" name="phone" value="<?=js_safe($siteData['phone'])?>" />
			</div>


			<div class="inputLblWrap">
				<div class="switchTtl">מוצג</div>
				<label class="switch">
				  <input type="checkbox" name="active" value="1" <?=($siteID ? '' : 'checked="checked"')?> <?=($siteData['active'] ? 'checked="checked"' : '')?> <?=($siteData['active']==1)?"checked":""?> />
				  <span class="slider round"></span>
				</label>
			</div>



			</div>

            <div class="mainSectionWrapper">
				<div class="sectionName">חשבון בנק</div>
				<div class="inSectionWrap">
					<?php if($siteData['bankData']) { $bData = json_decode($siteData['bankData'], true); } ?>
                    <div class="inputLblWrap">
						<div class="labelTo">שם בנק</div>
						<input type="text" placeholder='שם הבנק' name="bankName" value="<?=$bData['bankName']?>" />
					</div>

					<div class="inputLblWrap">
						<div class="labelTo">מספר בנק</div>
						<input type="text" placeholder='מספר בנק' name="bankNumber" value="<?=$bData['bankNumber']?>" />
					</div>

					<div class="inputLblWrap">
						<div class="labelTo">מספר סניף</div>
						<input type="text" placeholder='מספר סניף' name="bankBranch" value="<?=$bData['bankBranch']?>" />
					</div>

					<div class="inputLblWrap">
						<div class="labelTo">מספר חשבון</div>
						<input type="text" placeholder='מספר חשבון' name="bankAccount" value="<?=$bData['bankAccount']?>" />
					</div>

					<div class="inputLblWrap">
						<div class="labelTo">שם בעל החשבון</div>
						<input type="text" placeholder='שם הבעל החשבון' name="bankAcoountOwner" value="<?=$bData['bankAcoountOwner']?>" />
					</div>
				</div>
			</div>





            <div class="mainSectionWrapper attr">
				<div class="sectionName">טיפולים</div>
                    <div class="checksWrap">
						<?php foreach($treatments as $attribute) { ?>
                            <div class="checkLabel checkIb">
							<div class="checkBoxWrap">
								<input class="checkBoxGr" type="checkbox" name="attributes[]" <?=(in_array($attribute['treatmentID'],$tTreats)?"checked":"")?> value="<?=$attribute['treatmentID']?>" id="ch<?=$attribute['treatmentID']?>">
								<label for="ch<?=$attribute['treatmentID']?>"></label>
							</div>
							<label for="ch<?=$attribute['treatmentID']?>"><?=$attribute['treatmentName']?></label>
						</div>
                        <?php } ?>
					</div>
			</div>

            <input type="submit" value="שמור" class="submit">
		</form>
	</div>
</div>

<script src="../../../app/tinymce/tinymce.min.js"></script>
<script>





$(function(){

    $('.mainSectionWrapper').click(function(){
        var editors = $(this).find('textarea.textEditor:not([aria-hidden=true])');

        if(editors.length){
            editors.each(function(i){
                var obj = {
                    readonly : (i==0?1:0),
                    target: this,
                    height: 500,
                    plugins: [
                        "advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
                        "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
                        "table contextmenu directionality emoticons textcolor paste  textcolor colorpicker textpattern"
                    ],
                    fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
                    toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
                    toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor",
                    toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking pagebreak restoredraft"
                };
                tinymce.init(obj);
            });
        }
    });

});



var inputQuantity = [];
$(function() {
    $(".inputNumber").each(function(i) {
        inputQuantity[i]=this.defaultValue;
        $(this).data("idx",i); // save this field's index to access later
    }).on("keyup", function (e) {
        var $field = $(this),
            val=this.value,
            $thisIndex=parseInt($field.data("idx"),10); // retrieve the index
//        window.console && console.log($field.is(":invalid"));
        //  $field.is(":invalid") is for Safari, it must be the last to not error in IE8
        if (this.validity && this.validity.badInput || isNaN(val) || $field.is(":invalid") ) {
            this.value = inputQuantity[$thisIndex];
            return;
        }
        if (val.length > Number($field.attr("maxlength"))) {
            val=val.slice(0, 5);
            $field.val(val);
        }
        inputQuantity[$thisIndex]=val;
    });
});





function tabCloserGlobGal(id){
    $(".popGalleryCont").html('');
    $(".popGallery").hide();
    var elme = window.parent.document.getElementById(id);
    elme.style.zIndex="12";
    elme.style.position ="static";
}


$(function(){

    $.each({domain: <?=$domainID?>, language: <?=$langID?>}, function(cl, v){
        $('.' + cl).hide().each(function(){
            var id = $(this).data('id');
            $(this).find('input, select, textarea').each(function(){
                this.name = this.name + '[' + id + ']';
            });
        }).filter('[data-id="' + v + '"]').show();

        $('.' + cl + 'Selector').on('change', function(){

            $('.' + cl, $(this).data('cont')).hide().filter('[data-id="' + this.value + '"]').show();
        });
    });

    var sel = $('select', '#exSection');
    sel.on('change', function(){
        $('#exSection2').remove();

        if (this.value != ''){
            $.getJSON('js_exEngine.php', {act:'list', sid:<?=$siteID?>, id:this.value}, function(res){
                if (res.error)
                    return alert(res.error);
                else
                    $('#exSection').after('<div class="inputLblWrap" id="exSection2"><div class="inputLblWrap"><div class="labelTo">מזהה חיצוני :</div>' + res.html + '</div></div>');
            });
        }
    }).trigger('change');

    function MultiCcLabel(){
        $('#areasChecks .choosenCheck').text($('#areasChecks input:checked').map(function(){
            return $(this.parentNode).text();
        }).get().join(', '));
    };

    MultiCcLabel();

    $('#areasChecks .choosenCheck').click(function(){
        $(this.parentNode).toggleClass('open');
    }).parent().find('input').off('click').click(MultiCcLabel);

});
</script>