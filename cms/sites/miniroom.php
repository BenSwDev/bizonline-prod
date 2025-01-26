<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$roomID = intval($_GET['roomID']);
$siteID = intval($_GET['siteID']);


if ('POST' == $_SERVER['REQUEST_METHOD']) {
	$rooms = $cond = array();
	
	$rooms[] = "`roomName` = '".inDB($_POST['roomName'])."'";
	$rooms[] = "`roomDesc` = '".inDB($_POST['roomDesc'])."'";
	$rooms[] = "`roomDescText` = '".inDB($_POST['roomDescText'])."'";
	$rooms[] = "`roomDescTitle` = '".inDB($_POST['roomDescTitle'])."'";
	$rooms[] = "`roomPolicy` = '".inDB($_POST['roomPolicy'])."'";
	$rooms[] = "`max_kids` = ".(is_numeric($_POST['max_kids']) ? intval($_POST['max_kids']) : 0);
	$rooms[] = "`max_babies` = ".intval($_POST['max_babies']);
	$rooms[] = "`galleryID` = ".intval($_POST['galleryID']);
	$rooms[] = "`adults` = ".(is_numeric($_POST['adults']) ? intval($_POST['adults']) : 2);
	$rooms[] = "`max-total` = ".(is_numeric($_POST['max-total']) ? intval($_POST['max-total']) : intval($_POST['adults']) + intval($_POST['max_kids']));
	$rooms[] = "`roomCount` = ".(is_numeric($_POST['roomCount']) ? intval($_POST['roomCount']) : '1');
	$rooms[] = "`ifShow` = ".(is_numeric($_POST['ifShow']) ? intval($_POST['ifShow']) : 0);
	$rooms[] = "`roomType` = ".(is_numeric($_POST['roomType']) ? intval($_POST['roomType']) : 0);
	$rooms[] = "`roomStructure` = ".(is_numeric($_POST['roomStructure']) ? intval($_POST['roomStructure']) : 0);
	$rooms[] = "`roomBaseType` = ".(is_numeric($_POST['roomBaseType']) ? intval($_POST['roomBaseType']) : 0);

	
	//$rooms[] = "`inRoom` = '".inDB($_POST['inRoom'])."'";
	//$rooms[] = "`externalRoomID` = '".inDB($_POST['exID'])."'";
	//$rooms[] = "`num_rooms` = ".(is_numeric($_POST['num_rooms']) ? intval($_POST['num_rooms']) : 0);
	//$rooms[] = "`min-total` = ".(is_numeric($_POST['min-total']) ? intval($_POST['min-total']) : 1);
	//$rooms[] = "`galleryID` = ".(is_numeric($_POST['galleryID']) ? intval($_POST['galleryID']) : '-1');

	if (intval($_POST['nights_day']))
		$rooms[] = "`nights_day` = ".intval($_POST['nights_day']);
	if (intval($_POST['nights_end']))
		$rooms[] = "`nights_end` = ".intval($_POST['nights_end']);
	

	$type = 0;


	$type = 0;
	if (is_array($_POST['days']))
		foreach($_POST['days'] as $t)
			$type += intval($t);
	$rooms[] = "`days` = ".$type;

	$photo = pictureUpload('roomPicture',"../../gallery/");
	if($photo){
	$rooms[] = "`roomPicture` = '".$photo[0]['file']."'";
	}


	
	if ($roomID)
		$que = "UPDATE `sitesRooms` SET ".implode(',',$rooms)." WHERE `roomID` = ".$roomID;
	else
		$que = "INSERT INTO `sitesRooms` SET `siteID` = ".$siteID.",".implode(',',$rooms);
	mysql_query($que) or report_error(__FILE__,__LINE__,$que);
	$roomID = $roomID ? $roomID : mysql_insert_id();

	$que = "OPTIMIZE TABLE `sitesRooms`";
	mysql_query($que)or die(mysql_error().nl2br($que));
	

	if($roomID){
	udb::query("DELETE FROM `roomsOptions` WHERE roomID=".$roomID." ");
	$params=Array();
	foreach($_POST['param'] as $key=>$val){
		$params['roomID']=$roomID;
		$params['optionID']=$key;
		udb::insert("roomsOptions", $params);
	}
	}

?>
		<script>
			window.parent.location.reload();
			window.parent.closeTab('frame_<?=$roomID?>_<?=$siteID?>');	
		</script>
<?php

}





$que="SELECT sitesRooms.* FROM `sitesRooms` WHERE sitesRooms.siteID = ".$siteID." AND roomID=".$roomID." ORDER BY showOrder";
$room= udb::single_row($que);

$que = "SELECT * FROM `MainPages` WHERE ifShow=1 AND MainPageType=10 ORDER BY `ShowOrder`";
$params= udb::key_list($que, "inType");

if($roomID){
	$que = "SELECT * FROM `roomsOptions` WHERE roomID=".$roomID." ";
	$roomParams= udb::key_row($que, "optionID");
}

$que="SELECT * FROM `galleries` WHERE sID=".$siteID."";
$galleries= udb::full_list($que);

?>
<div class="editItems">
    <h1><?=outDb($room['roomName'])?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<b>נתוני החדר</b>
		<div class="section">
			<div class="inptLine">
				<div class="label">שם החדר: </div>
				<input type="text" value="<?=outDb($room['roomName'])?>" name="roomName" class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">סוג חדר: </div>
				<select name="roomType" style="width:160px">
					<option value="0">בחר סוג חדר</option>
					<option value="1" <?=$room['roomType']==1?"selected":""?>>זוגות בלבד</option>
					<option value="2" <?=$room['roomType']==2?"selected":""?>>צמוד חדר ילדים</option>			
				</select>
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">הגדרת המבנה: </div>
				<select name="roomStructure" style="width:160px">
					<option value="0">בחר מבנה</option>
					<option value="1" <?=$room['roomStructure']==1?"selected":""?>>צימר</option>
					<option value="2" <?=$room['roomStructure']==2?"selected":""?>>בקתה</option>			
					<option value="3" <?=$room['roomStructure']==3?"selected":""?>>וילה</option>			
					<option value="4" <?=$room['roomStructure']==4?"selected":""?>>סוויטה</option>			
					<option value="5" <?=$room['roomStructure']==5?"selected":""?>>יחידה</option>			
					<option value="6" <?=$room['roomStructure']==6?"selected":""?>>מערה</option>			
					<option value="7" <?=$room['roomStructure']==7?"selected":""?>>חדר</option>			
				</select>
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">מוצג באתר: </div>
				<div class="chkBox">
					<input type="checkbox" value="1" <?=$room['ifShow']?"checked":""?> name="ifShow" id="ifShow_<?=$siteID?$siteID:0?>">
					<label for="ifShow_<?=$siteID?$siteID:0?>"></label>
				</div>
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">מס. יחידות: </div>
				<input type="text" value="<?=(($room['roomCount'] >= 0) ? $room['roomCount'] : '')?>" name="roomCount" class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">סוגי אירוח: </div>
				<div class="chkBox">
					<input type="radio" value="1" <?=$room['roomBaseType']==1 || !$room['roomBaseType'] ?"checked":""?> name="roomBaseType" id="pension1">
					<label for="pension1">לינה בלבד</label>
				</div>
				<div class="chkBox">
					<input type="radio" value="2" <?=$room['roomBaseType']==2?"checked":""?> name="roomBaseType" id="pension2">
					<label for="pension2">לינה וארוחת בוקר</label>
				</div>
				<div class="chkBox">
					<input type="radio" value="3" <?=$room['roomBaseType']==3?"checked":""?> name="roomBaseType" id="pension3">
					<label for="pension3">חצי פנסיון</label>
				</div>
				<div class="chkBox">
					<input type="radio" value="4" <?=$room['roomBaseType']==4?"checked":""?> name="roomBaseType" id="pension4">
					<label for="pension4">פנסיון מלא</label>
				</div>
			</div>
		</div>
		<?/*?>
		<div class="section days">
			<div class="inptLine">
				<div class="label">ימי כניסה: </div>
				<?php
					$lett = array('א','ב','ג','ד','ה','ו','ש');
					for($i=0; $i<7; $i++){ ?>
					<div class="chkBox">
						<label for="days<?=$i?>"><?=$lett[$i]?></label>
						<input type="checkbox" name="days[]" value="<?=pow(2,$i)?>" <?=(($room['days'] & pow(2,$i) || !$roomID) ? 'checked' : '')?> id="days<?=$i?>">				
					</div>
				<?php } ?>
			</div>
		</div>
		<?*/?>
		<div class="section prcs" style="width:210px;">
			<div class="inptLine">
				<div class="label">מקס. אורחים: </div>
				<div style="clear:both;">
					<div class="pricesBox">
						<div class="title">מבוגרים</div>
						<input type="text" name="adults" value="<?=(($room['adults'] >= 0) ? $room['adults'] : '')?>" class="inpt">
					</div>
					<div class="pricesBox">
						<div class="title">ילדים</div>
						<input type="text" name="max_kids" value="<?=(($room['max_kids'] >= 0) ? $room['max_kids'] : '')?>" class="inpt">
					</div>
					<div class="pricesBox">
						<div class="title">תינוקות</div>
						<input type="text" name="max_babies" value="<?=(($room['max_babies'] >= 0) ? $room['max_babies'] : '')?>" class="inpt">
					</div>
					<div class="pricesBox">
						<div class="title total">סה"כ</div>
						<input type="text" name="max-total" value="<?=(($room['max-total'] >= 0) ? $room['max-total'] : '')?>" class="inpt">
					</div>
				</div>
			</div>
		</div>
		<div  style="clear:both;"></div>
		<div class="section">
			<div class="inptLine">
				<div class="label">כותרת טקסט: </div>
				<input type="text" value="<?=outDb($room['roomDescTitle'])?>" name="roomDescTitle" class="inpt">
			</div>
		</div>
		<div  style="clear:both;"></div>
		<div class="section txtarea">
			<div class="inptLine">
				<div class="label">תיאור החדר: </div>
				<textarea name="roomDesc"><?=outDb($room['roomDesc'])?></textarea>
			</div>
		</div>
		<div class="section txtarea">
			<div class="inptLine">
				<div class="label">תיאור קצר לחדר: </div>
				<textarea name="roomDescText"><?=outDb($room['roomDescText'])?></textarea>
			</div>
		</div>
		<div class="section txtarea">
			<div class="inptLine">
				<div class="label">חשוב לדעת: </div>
				<textarea name="roomPolicy"><?=$room['roomPolicy']?></textarea>
			</div>
		</div>
		<div  style="clear:both;"></div>
		<div class="section">
			<div class="inptLine">
				<div class="label">תמונה מייצגת: </div>
				<input type="file" name="roomPicture" class="inpt" value="<?=$room['roomPicture']?>"><br>
			</div>
		</div>
		<?php if($room['roomPicture']){ ?>
		<div class="section">
			<div class="inptLine">
				<img src="../../gallery/<?=$room['roomPicture']?>" style="width:100%">
			</div>
		</div>
		<?php } ?>
		<?php if ($galleries){ ?>
		<div class="section">
			<div class="inptLine">
				<div class="label">שיוך גלריה: </div>
				<select name="galleryID" id="galleryID" style="width:160px">
						<option value="0">ללא גלריה</option>
					<?php foreach($galleries as $gal){ ?>
						<option value="<?=$gal['GalleryID']?>" <?php if($gal['GalleryID'] == $room['galleryID']){echo "selected";}?>><?=$gal['GalleryTitle']?></option>
					<?php } ?>
				</select>
			</div>
		</div>
		<?php } ?>

		<b>אבזור החדר</b>
		<div class="sectionParams">	
			<?php 
			$paramTypes=Array();
			$paramTypes[1]="פנימי";
			$paramTypes[2]="חיצוני";
			$paramTypes[3]="כללי";
			if($params){ 
			foreach($params as $type=>$param){ ?>
			<b style="display:block;margin-bottom:5px;"><?=$paramTypes[$type]?></b>
			<?php foreach($param as $par){ 
			if(preg_match('/2/',$par['tags'])) { ?>
				<div class="param">
					<input type="checkbox" name="param[<?=$par['MainPageID']?>]" <?=$roomParams[$par['MainPageID']]?"checked":""?> value="<?=$par['MainPageID']?>" id="param_<?=$par['MainPageID']?>">
					<label for="param_<?=$par['MainPageID']?>"><?=outDB($par['MainPageTitle'])?></label>
				</div>
			<?php } }
			}
			} ?>
		</div>	
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="שמור" class="submit">
			</div>
		</div>
	</form>
</div>