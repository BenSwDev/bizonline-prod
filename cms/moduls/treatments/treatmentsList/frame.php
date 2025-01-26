<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";

const BASE_LANG_ID = 1;

$domainID = DomainList::active();
$langID   = LangList::active();

$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
	        $data = typemap($_POST, [
            'treatmentName'   => ['int' => 'string'],
            'treatmentDesc'   => ['int' => 'html'],
            'treatmentPrice'   => ['int' => 'string'],
            'treatmentDuration'   => 'string',
            '!tretcat'   => ['int' => 'int'],
			'!active'    => 'int',
			'!specialPage'    => 'int'

        ]);
		if (!$data['treatmentName'][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');


        // main site data
        $siteData = [
            'treatmentName' => $data['treatmentName'][BASE_LANG_ID],
            'treatmentDuration' => $data['treatmentDuration'],
            'active' => $data['active'],
            'specialPage' => $data['specialPage']
          
        ];

		$photo = pictureUpload('picture',"../../../../gallery/");
		if($photo){
			$siteData["treatmentPic"] = $photo[0]['file'];
		}

        if (!$pageID){      // opening new
            $pageID = udb::insert('treatments', $siteData);
        } else {
            udb::update('treatments', $siteData, '`treatmentID` = ' . $pageID);
        }

		udb::query("DELETE FROM treatmentsToCategory WHERE treatmentID=".$pageID);
		if($data['tretcat']){
			foreach($data['tretcat'] as $tret){

				udb::insert('treatmentsToCategory', [
					'treatmentID'    => $pageID,
					'treatmentsCatID'    => $tret
				], true);
			}
		}
	
		foreach(LangList::get() as $lid => $lang){
			udb::insert('treatmentsLangs', [
				'treatmentID'    => $pageID,
				'langID'    => $lid,
				'treatmentName'  => $data['treatmentName'][$lid],
				'treatmentDesc'  => $data['treatmentDesc'][$lid],
				'treatmentPrice'  => $data['treatmentPrice'][$lid]
			], true);
		}

	}


    catch (LocalException $e){
        // show error
    } ?>

	<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}

if ($pageID){
    $site   = udb::single_row("SELECT * FROM treatments WHERE treatmentID=".$pageID);
    $siteLangs   = udb::key_row("SELECT * FROM `treatmentsLangs` WHERE `treatmentID` = " . $pageID, ['langID']);
}
$tretCategory = udb::full_list('SELECT * FROM `treatmentsCat` WHERE 1');
$treatmentsToCategory = udb::key_row('SELECT `treatmentsCatID` FROM `treatmentsToCategory` WHERE `treatmentID`='.$pageID,'treatmentsCatID');


?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$site['treatmentName']?outDb($site['treatmentName']):"הוספת טיפול חדש"?></h1>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
        <?=LangList::html_select()?>
	</div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
			<div class="inputLblWrap">
				<div class="switchTtl">מוצג</div>
				<label class="switch">
				  <input type="checkbox" name="active" value="1" <?=($site['active']==1 || !$pageID)?"checked":""?> />
				  <span class="slider round"></span>
				</label>
			</div>
			<div class="inputLblWrap">
				<div class="switchTtl">דף מיוחד</div>
				<label class="switch">
				  <input type="checkbox" name="specialPage" value="1" <?=($site['specialPage']==1)?"checked":""?> />
				  <span class="slider round"></span>
				</label>
			</div>
			<?php foreach(LangList::get() as $id => $lang){ ?>
			<div class="language" data-id="<?=$id?>">
				<div class="inputLblWrap">
					<div class="labelTo">שם הטיפול</div>
					<input type="text" placeholder="שם הטיפול" name="treatmentName" value="<?=$siteLangs[$id]['treatmentName'] ? js_safe($siteLangs[$id]['treatmentName']) : $site['treatmentName'];?>" />
				</div>
			</div>
			<?php } ?>
			<div class="inputLblWrap">
				<div class="labelTo">אורך הטיפול</div>
				<input type="text" placeholder="אורך הטיפול" name="treatmentDuration" value="<?=js_safe($site['treatmentDuration'])?>" />
			</div>
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה: </div>
						<input type="file" name="picture" class="inpt" value="<?=$site['treatmentPic']?>">
					</div>
				</div>
				<?php if($site['treatmentPic']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../../../gallery/<?=$site['treatmentPic']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>

			<?php foreach(LangList::get() as $id => $lang){ ?>
			<div class="language" data-id="<?=$id?>">
				<div class="inputLblWrap">
					<div class="labelTo">מחיר</div>
					<input type="text" placeholder="מחיר" name="treatmentPrice" value="<?=js_safe($siteLangs[$id]['treatmentPrice'])?>" />
				</div>
				<div class="section txtarea big">
					<div class="inptLine">
						<div class="label noFloat">תיאור הטיפול</div>
						<textarea class="textEditor" name="treatmentDesc"><?=outDb($siteLangs[$id]['treatmentDesc'])?></textarea>
					</div>
				</div>
			</div>
			<?php } ?>
			<div class="catName">שיוך לקטגוריה</div>
			<?php foreach($tretCategory as $tretCat) { ?>
			<div class="checkLabel checkIb">
				<div class="checkBoxWrap">
					<input class="checkBoxGr" type="checkbox" value="<?=$tretCat['id']?>" <?=(($tretCat['id']==$treatmentsToCategory[$tretCat['id']]['treatmentsCatID'])?"checked": "")?> name="tretcat[]" id="ch<?=$tretCat['id']?>">
					<label for="ch<?=$tretCat['id']?>"></label>
				</div>
				<label for="ch<?=$tretCat['id']?>"><?=$tretCat['categoryName']?></label>
			</div>
			<?php } ?>
		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$site['id']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>

<script src="../../../app/tinymce/tinymce.min.js"></script>
<script type="text/javascript">

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


});


	tinymce.init({
	  selector: 'textarea.textEditor' ,
	  height: 500,
	  directionality : "rtl",
	  plugins: [
		"advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
		"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
		"table contextmenu directionality emoticons textcolor paste  textcolor colorpicker textpattern"
	  ],
	  fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
	  toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
	  toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor",
	  toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking pagebreak restoredraft"

	});

</script>