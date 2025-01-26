<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$extraID = intval($_GET['extraID']);
$siteID = intval($_GET['siteID']);


if ('POST' == $_SERVER['REQUEST_METHOD']) {

	$array=Array();
	$array['extraTitle']=inputStr($_POST['extraTitle']);
	$array['extraPrice']=inputStr($_POST['extraPrice']);
	$array["active"] = intval($_POST['active'])?"1":"0";
	$array["roomID"] = intval($_POST['roomID']);

	udb::update("sitesExtras", $array, "extraID=".$extraID);

?>
		<script>
			window.parent.location.reload();
			window.parent.closeTab('frame_<?=extraID?>_<?=$siteID?>');	
		</script>
<?php

}

$que = "SELECT `roomID`,`roomName` FROM `sitesRooms` WHERE `siteID` = ".$siteID." ORDER BY `roomName`";
$sql = mysql_query($que) or report_error(__FILE__,__LINE__,$que);
while($row = mysql_fetch_assoc($sql))
	$rooms[$row['roomID']] = $row['roomName'];
mysql_free_result($sql);


$que="SELECT * FROM `sitesExtras` WHERE siteID = ".$siteID." AND extraID=".$extraID."";
$editOp= udb::single_row($que);

?>
<div class="editItems">
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<div class="section">
			<div class="inptLine">
				<div class="label">שם תוספת :</div>
				<input type="text" name="extraTitle" value="<?=$editOp['extraTitle']?>" style="width:200px">
			</div>
		</div>
		<div class="section" style="width:120px;">
			<div class="inptLine">
				<div class="label">מחיר :</div>
				<input type="text" name="extraPrice" value="<?=$editOp['extraPrice']?>" style="width:70px;margin-left:10px;">₪
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">שייל ל- :</div>
				<select name="roomID" style="width:200px"><option value="0">-- כל החדרים --</option>
					<?
						foreach($rooms as $tID => $tName)
							echo '<option value="'.$tID.'" '.($tID == $editOp['roomID'] ? 'selected' : '').'>'.$tName.'</option>';
					?>
				</select>
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
				<input type="submit" value="שמור" class="submit">
			</div>
		</div>
	</form>
</div>