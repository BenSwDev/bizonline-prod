<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";
include_once "../../../classes/class.SearchManager.php";

$cleanTime = [1 => '15 דקות', 2 => '30 דקות', 3 => '45 דקות', 4 => 'שעה', 6 => 'שעה וחצי', 8 => 'שעתיים',12 => '3 שעות', 16 => '4 שעות'];
$siteID = intval($_GET['siteID']);
$fieldID = intval($_POST['fieldID'] ?? $_GET['fieldID'] ?? 0);

const BASE_LANG_ID = 1;

$siteID = intval($_GET['siteID']);
$fieldID = intval($_GET['fieldID']);
if(!$fieldID)exit;
$siteName = $_GET['siteName'];




if ('POST' == $_SERVER['REQUEST_METHOD']){

    try {
        $data = typemap($_POST, [
            'ifShow'    => 'int',
            'required'    => 'int',
			'fieldLabel' => 'string',
            'fieldLabelEn' => 'string'
        ]);
		// print_r($data);
		// exit;

        // main field data
		//'fieldCount' => max($data['fieldCount'], count($fieldUnits)) changed by gal 12.11.20
		 $fieldData = [
            'ifShow'    => $data['ifShow']?$data['ifShow']:0,
            'required'    => $data['required']?$data['required']:0,
            'siteID'    => $siteID,
            'fieldLabel'  => $data['fieldLabel'],
            'fieldLabelEn'  => $data['fieldLabelEn']
        ];
		udb::update('sites_health_fields', $fieldData, '`fieldID` = ' . $fieldID);

    }
    catch (LocalException $e){
        // show error
    } ?>
<script>window.parent.closeTab(true); //window.parent.location.reload();</script>
<?php
    exit;
}



$fieldTypes = array(
	'text' => 'שדה טקסט',
	'tel' => 'שדה טלפון'

);

if ($fieldID){
    $fieldData    = udb::single_row("SELECT * FROM `sites_health_fields` WHERE `fieldID` = " . $fieldID);
}

?>
<div class="editItems">
	<div class="popGallery">
		<div class="popGalleryCont"></div>
	</div>
	<div class="frameContent">
		<div class="siteMainTitle"><?=$siteName?></div>
<?php /*
		<div class="fieldChoosePage" <?=$fieldData['fieldOrPage']?"style='visibility: hidden;'":""?> >
			<div class="labelTo">חדר/דף</div>
			<select name="page" id="selectFrame">
				<option value="1" <?=($fieldData['fieldOrPage']==1?"selected":"")?>>חדר</option>
				<option value="2" <?=($fieldData['fieldOrPage']==2?"selected":"")?>>דף</option>
			</select>
		</div>
*/ ?>
		<div id="field" class="frameChoose" style="display: block;">
			<form action="" method="post">




			<div class="inputLblWrap">
			    <div class="labelTo">שם השדה</div>
			    <input type="text" placeholder="שם השדה" name="fieldLabel" value="<?=$fieldData['fieldLabel']?>" />
		    </div>
                <div class="inputLblWrap">
			    <div class="labelTo">שם השדה באנגלית</div>
			    <input type="text" placeholder="שם השדה" dir="ltr" name="fieldLabelEn" value="<?=$fieldData['fieldLabelEn']?>" />
		    </div>

	
					<div class="inputLblWrap">
						<div class="switchTtl">מוצג</div> 
						<label class="switch">  
						  <input type="checkbox" name="ifShow" value="1" <?=$fieldData['ifShow']?"checked":""?>> 
						  <span class="slider round"></span>
						</label>
					</div>

					<div class="inputLblWrap">
						<div class="switchTtl">שדה חובה</div> 
						<label class="switch">  
						  <input type="checkbox" name="required" value="1" <?=$fieldData['required']?"checked":""?>> 
						  <span class="slider round"></span>
						</label>
					</div>




				<div class="clear"></div>

				<input type="submit" value="שמור" class="submit">
			</form>	
		</div>
	</div>
</div>


<script>





</script>
