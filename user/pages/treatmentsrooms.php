<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

$sid = $_CURRENT_USER->active_site() ?: 0;

$uid = 0;
if($_GET["uid"] || $_POST['uid']) {
    $uid = intval($_GET["uid"] ? $_GET["uid"] : $_POST['uid']);
}

?>
<h1>הגדרות חדרים</h1>
<style>
    .editItems form {
        margin-top: 30px;
        background: #ffffff;
        padding: 10px;
        border-bottom: 2px solid rgba(0,0,0,0.2);
        margin-bottom: 10px;
        border-radius: 3px;
        overflow: hidden;
        font-size: 14px;
    }

    .mainSectionWrapper {
        border: 1px solid #f3f3f3;
        clear: both;
        /*overflow: hidden;*/
        /*height: 50px;*/
        margin-top: 10px;
    }
    .mainSectionWrapper .sectionName {
        background: #d8d8d8;
        line-height: 50px;
        margin-bottom: 20px;
        cursor: pointer;
        text-align: start;
        box-sizing: border-box;
        font-weight: bold;
        font-size: 20px;
        padding-right: 10px;
    }
    .inputLblWrap {
        display: inline-block;
        vertical-align: middle;
        min-width: 200px;
        margin: 4%;
    }
    .editItems input#submitTreats {
        position: fixed;
        left: 23px;
        bottom: 38px;
        width: 90px;
        height: 50px;
        line-height: 50px;
        color: #ffffff;
        font-weight: bold;
        background: #2FC2EB;
        font-size: 16px;
        margin-top: 20px;
        text-shadow: -1px 1px 0 rgb(0 0 0 / 10%);
        border-bottom: 2px solid rgba(0,0,0,0.1);
        cursor: pointer;
        box-shadow: none;
        -moz-transition: all 0.25s;
        -webkit-transition: all 0.25s;
        transition: all 0.25s;
        text-align: center;
        display: inline-block;
        vertical-align: top;
    }
    .inputLblWrap .labelTo {
        display: block;
        vertical-align: middle;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .editItems input[type='text'], .editItems input[type='password'], .editItems input.submit, .editItems input[type='submit'], .editItems input[type='number'], .editItems textarea {
        line-height: 32px;
        height: 32px;
        background: #f5f5f5;
        border: 0;
        border-radius: 3px;
        box-sizing: border-box;
        outline: none;
        font-size: 12px;
        padding: 0 5px;
        box-shadow: -1px 1px 0 rgb(0 0 0 / 20%);
        margin: 0 auto;
        width: 98%;
        font-family: 'Rubik', sans-serif;
    }

    .frameContent {
        position: relative;
    }
    .checkIb {
        display: inline-block;
        margin: 1%;
        width: 206px;
    }
    .checkLabel .checkBoxWrap {
        position: relative;
        width: 20px;
        height: 20px;
        cursor: pointer;
        box-sizing: border-box;
        border: 1px solid #666;
        background: #fff;
        display: inline-block;
        vertical-align: middle;
        border-radius: 4px;
    }
    .checkLabel .checkBoxWrap input[type="checkbox"] {
        display: none;
    }
    .editItems input[type='checkbox'] {
        margin: 4px !important;
    }
    .editItems input[type='checkbox'] {
        -webkit-appearance: checkbox !important;
    }
    input, select, textarea {
        font-family: 'Rubik', sans-serif;
        border: 1px solid #ccc;
    }
    .checkLabel .checkBoxWrap label {
        width: 100%;
        height: 100%;
        cursor: pointer;
        position: absolute;
        top: 0;
        left: 0;
    }
    .checkLabel .checkBoxWrap label::after {
        content: '';
        width: 14px;
        height: 3px;
        position: absolute;
        top: 4px;
        left: 1px;
        border: 3px solid #666;
        border-top: none;
        border-right: none;
        background: transparent;
        opacity: 0;
        -webkit-transform: rotate(
            -45deg
        );
        transform: rotate(
            -45deg
        );
    }
    .checkLabel > label {
        font-size: 16px;
        color: #666;
        display: inline-block;
        vertical-align: middle;
        font-weight: bold;
        cursor: pointer;
    }
    .checkLabel .checkBoxWrap input:checked + label:after {
        opacity: 1;
    }
    .labelTo {
        display: block;
        vertical-align: middle;
        font-weight: bold;
        margin-bottom: 5px;
    }
    /*select{background:0 0;font-size:20px;color:#333;padding:0 10px;box-sizing:border-box}*/
    .manageItems table {
        margin-top: 25px;
        margin-bottom: 10px;
        width: 100%;
        border-bottom: 2px solid rgba(0,0,0,0.1);
        box-sizing: border-box;
        border-radius: 5px;
        overflow: hidden;
    }
    .manageItems table > thead {
        background: #ffffff;
        border-bottom: 2px solid #f5f5f5;
        line-height: 32px;
        font-weight: bold;
    }

    .manageItems table > thead > tr > th {
        text-align: start;
        padding-right: 5px;
        border: 2px solid #f5f5f5;
        line-height: 1;
        padding: 10px 4px;
        vertical-align: middle;
    }
    .manageItems table > thead > tr > th:nth-child(1) {
        width: 5%;
        text-align: center;
        padding-right: 0;
    }
    .manageItems table > tbody > tr {
        line-height: 30px;
        color: #666;
        cursor: pointer;
        font-size: 14px;
    }
    .manageItems table > tbody > tr:nth-child(odd) {
        background: #F9F9F9;
    }
    .manageItems table > tbody > tr > td {
        border: 1px solid #f5f5f5;
        padding-right: 10px;
        vertical-align: middle;
    }

    @media (min-width: 1000px) {
        .inputLblWrap {
            margin: 4% 1%;
            width: auto;
        }
    }
</style>
<?php

/*if (!$_CURRENT_USER->single_site){
    $sname = udb::key_row("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")",'siteID');
?>
    <div class="site-select">
		    <label for="sid" class="labelTo">בחר מתחם</label>
            <select name="sid" id="sid" title="שם מתחם" onchange="location.href = '?page=treatmentsrooms&asite=' + this[this.selectedIndex].value">
                <?php
                foreach($sname as $id => $name) {
                    echo '<option value="' , $name['siteID'] , '" ' , ($name['siteID'] == $sid ? 'selected' : '') , '>' , $name['siteName'] , '</option>';
                }
                ?>
            </select>
        </div>
<?
}*/

if ('POST' == $_SERVER['REQUEST_METHOD'] && $uid){
    $isError = '';
    try {
        $data = typemap($_POST, [
            'hasTreatments'   => 'int',
            'hasStaying'      => 'int',
            'maxTreatments'      => 'int',
            'attributes' => ['int' => 'int'],
            'uname'   => ['int' => 'string']
        ]);

// main site data
        $siteData = [
            'hasTreatments'  => $data['hasTreatments'],
            'hasStaying'     => $data['hasStaying'],
            'maxTreatments'     => $data['maxTreatments'],
        ];

        if ($data['uname'][1])
            $siteData['unitName'] = $data['uname'][1];

        udb::update('rooms_units', $siteData, '`unitID` = ' . $uid);

        empty($data['uname'][2]) ? Translation::clear('rooms_units', $uid, 'unitName', 2) : Translation::save('rooms_units', $uid, 'unitName', $data['uname'][2], 2);

        udb::query("DELETE FROM `units_treats` WHERE `unitID` = " . $uid);
		if($data['attributes'] && count($data['attributes'])){
            $que = [];
            foreach($data['attributes'] as $attr)
                $que[] = '(' . $uid . ', ' . $attr . ')';
            //print_r($que);
			$upsql = "INSERT INTO `units_treats` (`unitID`, `treatmentID` ) VALUES" . implode(',', $que); //. " ON DUPLICATE KEY UPDATE `unitID` = VALUES(`unitID`)";
            udb::query($upsql);
            unset($que);
        }




    }
    catch (LocalException $e){
// show error
        $isError = $e->getMessage();
    } ?>
    <script>
    <?if($isError) {?>
    alert('<?=$isError?>');
    <?} ?>
</script>
    <?php

}

if(isset($_GET['uid'])) {
    $uid = intval($_GET['uid']);
    $siteData = udb::single_row("SELECT * FROM `rooms_units` where unitID=".$uid);
    $usite = udb::single_value("SELECT `siteID` FROM `rooms` where roomID = " . $siteData['roomID']);
    $sitesTretmentsSQL = "SELECT treatments FROM `sitesTratments` where bizSiteID in (".$usite.")";
    $sitesTretments = udb::single_column($sitesTretmentsSQL);
    $useTreats = [];
    foreach ($sitesTretments as $t) {
        $currTreats = json_decode($t,true);
        if(is_array($currTreats)) {
            foreach ($currTreats as $tr) {
                if(isset($tr['id']))
                    $useTreats[$tr['id']] = intval($tr['id']);
            }
        }
    }
    $tTreats = [];
    $tTreats = udb::single_column("select treatmentID from units_treats where unitID=".$uid);
    $tratsSQL = "SELECT treatments.* FROM `treatments` INNER JOIN treatmentsPricesSites USING(`treatmentID`) where treatmentsPricesSites.siteID = " . $usite . " group by treatmentID";
    $treatments = udb::full_list($tratsSQL);
?>
<div class="edit_subtitle"><?=$siteData['unitName']?></div>
<div class="editItems">
	<a class="backbtn" href="?page=<?=$_GET["page"]?>">חזרה</a>
	<div class="inputLblWrap ">
         <div class="frameContent">
            <form method="post" enctype="multipart/form-data" >
                <div class="mainSectionWrapper">
                    <div class="sectionName">הגדרות</div>

                    <div class="inputLblWrap">
                        <div class="labelTo">שם החדר בעברית</div>
                        <input type="text" name="uname[1]" value="<?=htmlentities($siteData['unitName'])?>" />
                    </div>
                    <div class="inputLblWrap">
                        <div class="labelTo">שם החדר באנגלית</div>
                        <input type="text" name="uname[2]" value="<?=htmlentities(Translation::rooms_units($siteData['unitID'], 'unitName', 2))?>" />
                    </div>

                    <div class="inputLblWrap">
                        <div class="labelTo">טיפולים בחדר?</div>
                        <label class="switch">
                        <input type="checkbox" onchange=" if($(this).is(':checked')){ $('#maxTreats').addClass('show')}else{$('#maxTreats').removeClass('show')}"  name="hasTreatments" value="1" <?=$siteData['hasTreatments'] == 1 ? ' checked="checked" ' : ''?>>
                        <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="inputLblWrap">
                        <div class="labelTo">שהות בחדר?</div>
                        <label class="switch">
                        <input type="checkbox"  name="hasStaying" value="1" <?=$siteData['hasStaying'] == 1 ? ' checked="checked" ' : ''?>>
                        <span class="slider round"></span>
                        </label>
                    </div>
					<style>
						#maxTreats{opacity:0;transition:0.2s all}
						#maxTreats.show{opacity:1}
					</style>
					<div class="inputLblWrap <?=$siteData['hasTreatments']? "show" : ""?>" id='maxTreats'>
						<div class="labelTo">כמות טיפולים מקסימלית במקביל</div>
						<SELECT  name="maxTreatments" value="<?=$siteData['maxTreatments']?>" />
							<?for($i=1;$i<11;$i++){?>
								<option value="<?=$i?>" <?=$i== $siteData['maxTreatments']? "selected" : ""?>><?=$i?></option>
							<?}?>
						</SELECT>
					</div>
                </div>
                <div class="mainSectionWrapper attr">
				<div class="sectionName">טיפולים </div>
                    <div class="checksWrap">
						<div><span class="checkall">סמן הכל</span></div>
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
			<input type="submit" value="שמור" id="submitTreats" class="submit not-empty">
            </form>
        </div>
    </div>
</div>
    <?
}
else {


?>
<div class="manageItems" id="manageItems">
	<div class="tblMobile">
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>חדר</th>
			<th>חדר טיפולים</th>
            <th>שהות</th>
        </tr>
        </thead>
        <tbody id="sortRow">
        <?php
        $roomsSql = "select * from rooms where siteID=".$sid ;
        $rooms = udb::full_list($roomsSql);
        if (count($rooms)){
            foreach($rooms as $room){
                $roomID = $room['roomID'];
                $que = "SELECT u.* 
                    FROM `rooms_units` AS `u` 
                    WHERE u.roomID = " . $roomID . " 
                    GROUP BY u.unitID";
                $units = udb::key_row($que, 'unitID');
                foreach($units as $uid => $unit) {
                    //hasStaying
                    //hasTreatments

                ?>
                <tr id="<?=$unit['unitID']?>">
					<td onclick="openPop(<?=$unit['unitID']?>,'')"><?=$unit['unitID']?></td>
					<td onclick="openPop(<?=$unit['unitID']?>,'')"><?=outDb($unit['unitName'])?></td>
					<tD onclick="openPop(<?=$unit['unitID']?>,'')"><?=($unit['hasTreatments']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?><?=$unit['hasTreatments']? " <span style='display:inline-block'> (".$unit['maxTreatments'].")</span>": "";?></tD>
					<tD onclick="openPop(<?=$unit['unitID']?>,'')"><?=($unit['hasStaying']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></tD>

				</tr>
                <?php
            }
            }
        }
        ?>
        </tbody>
    </table>
	</div>
</div>
<?} ?><script>
var pageType="<?=$pageType?>";
function openPop(pageID, siteName){
    location.href = "?page=<?=$_GET['page']?>&sid=<?=$sid?>&uid=" + pageID;
}

$('.checkall').on('click',function(){
	//debugger;
	$(this).toggleClass('checked');
	if($(this).hasClass('checked')){	
		$(this).html('בטל הכל');
		$(this).closest('.mainSectionWrapper').find('input').each(function(){$(this).prop('checked',true)});
	}else{
		$(this).closest('.mainSectionWrapper').find('input').each(function(){$(this).prop('checked', false)});
		$(this).html('סמן הכל');
	}
});

</script>
