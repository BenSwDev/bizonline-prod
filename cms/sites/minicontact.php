<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$contactID = intval($_GET['contactID']);
$siteID = intval($_GET['siteID']);


if ('POST' == $_SERVER['REQUEST_METHOD']) {
	$cp=Array();		
	$cp['contactStatus'] = intval($_POST['contactStatus'])?intval($_POST['contactStatus']):0;
	udb::update("sitesContacts", $cp, "contactID =".$contactID);
	
	if(!intval($_POST['refresh'])){ // save and close iframe ?>
		<script> window.parent.location.reload(); window.parent.closeTab(<?=$frameID?>); </script>
	<?php
	}
}

$que="SELECT * FROM `sites` WHERE siteID=".$siteID."";
$site= udb::single_row($que);

$que="SELECT * FROM `sitesContacts` WHERE contactSiteID = ".$siteID." AND contactID=".$contactID." ORDER BY contactDate";
$contact= udb::single_row($que);

if($contact['contactRoom']){
$que="SELECT sitesRooms.* FROM `sitesRooms` WHERE sitesRooms.siteID = ".$siteID." AND roomID=".$contact['contactRoom']." ORDER BY showOrder";
$room= udb::single_row($que);
}


$contactType=Array();
$contactType[1]="שאלה לבעל המתחם";
$contactType[2]="בקשה להזמנת נופש";


?>
<div class="editItems">
    <h1><?=outDb($site['TITLE'])?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<b>נתוני החדר</b>
		<div class="section">
			<div class="inptLine">
				<div class="label">סוג פנייה </div>
				<input type="text" value="<?=$contactType[$contact['contactType']]?>" disabled class="inpt">
			</div>
		</div>

		<?php if($contact['contactType']==2){ ?>
		<div class="section">
			<div class="inptLine">
				<div class="label">חדר: </div>
				<input type="text" value="<?=$room['roomName']?>" disabled class="inpt">
			</div>
		</div>
		<div style="clear:both;"></div>
		<div class="section">
			<div class="inptLine">
				<div class="label">תאריך אירוך: </div>
				<input type="text" value="<?=date("d.m.y", strtotime($contact['contactDateHost']))?>" disabled class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">כמות לילות: </div>
				<input type="text" value="<?=$contact['contactNights']?>" disabled class="inpt">
			</div>
		</div>
		<div style="clear:both;"></div>
		<div class="section prcs" style="width:100%">
			<div class="inptLine">
				<div class="label">הרכב אירוח</div>
				<div class="pricesBox">
					<div class="title">מבוגרים</div>
					<input type="text" disabled value="<?=$contact['contactAdults']?>" class="inpt">
				</div>
				<div class="pricesBox">
					<div class="title">ילדים</div>
					<input type="text" disabled value="<?=$contact['contactKids']?>" class="inpt">
				</div>
				<div class="pricesBox">
					<div class="title">תינוקות</div>
					<input type="text" disabled value="<?=$contact['contactBabies']?>" class="inpt">
				</div>
			</div>
		</div>
		<?php } ?>
		<div style="clear:both;"></div>
		<div class="section">
			<div class="inptLine">
				<div class="label">שם לקוח: </div>
				<input type="text" value="<?=$contact['contactName']?>" disabled class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">אימייל: </div>
				<input type="text" value="<?=$contact['contactEmail']?>" disabled class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">טלפון: </div>
				<input type="text" value="<?=$contact['contactPhone']?>" disabled class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">תוכן הודעה: </div>
				<textarea disabled><?=$contact['contactMsg']?></textarea>
			</div>
		</div>
		<div style="clear:both;"></div>
		<div class="section">
			<div class="inptLine">
				<div class="label">טופל: </div>
				<div class="chkBox">
					<input type="checkbox" value="1" <?=$contact['contactStatus']?"checked":""?> name="contactStatus" id="ifShow_<?=$siteID?$siteID:0?>">
					<label for="ifShow_<?=$siteID?$siteID:0?>"></label>
				</div>
			</div>
		</div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="שמור" class="submit">
			</div>
		</div>
	</form>
</div>