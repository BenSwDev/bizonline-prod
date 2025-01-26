<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$extraID = intval($_GET['extraID']);
$siteID = intval($_GET['siteID']);


if ('POST' == $_SERVER['REQUEST_METHOD']) {

	$array=Array();
	$array['price']=inputStr($_POST['price']);
	$array["active"] = intval($_POST['active'])?"1":"0";
	$array["siteID"] = $siteID;
	$array["extraID"] = $extraID;
	if(intval($_POST['extraID'])){
		udb::update("sitesExtrasNew", $array, "extraID=".$extraID." AND siteID=".$siteID."");
	} else {
		udb::insert("sitesExtrasNew", $array);
	}
?>
		<script>
			window.parent.location.reload();
			window.parent.closeTab('frame_<?=extraID?>_<?=$siteID?>');	
		</script>
<?php

}



$que="SELECT MainPageID, MainPageTitle, sitesExtrasNew.* FROM `MainPages` LEFT JOIN `sitesExtrasNew` ON (MainPages.MainPageID=sitesExtrasNew.extraID AND siteID=".$siteID.") WHERE MainPageID=".$extraID." ";
$editOp= udb::single_row($que);


?>
<div class="editItems">
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<input type="hidden" name="extraID" value="<?=$editOp['extraID']?>">
		<div class="section">
			<div class="inptLine">
				<div class="label">שם תוספת :</div>
				<input type="text" disabled value="<?=$editOp['MainPageTitle']?>" style="width:200px">
			</div>
		</div>
		<div class="section" style="width:120px;">
			<div class="inptLine">
				<div class="label">מחיר :</div>
				<input type="text" name="price" value="<?=$editOp['price']?>" style="width:70px;margin-left:10px;">₪
			</div>
		</div>
		
		<div class="section">
			<div class="inptLine">
				<div class="label">&nbsp;</div>
				<input type="checkbox" name="active" value="1" <?=(($editOp['active'] || !$editOp['extraID']) ? 'checked' : '')?>> תוספת פעילה
			</div>
		</div>
		<div  style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=!$editOp['extraID']?"הוסף":"שמור"?>" class="submit">
			</div>
		</div>
	</form>
</div>