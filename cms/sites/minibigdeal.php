<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$dealType=intval($_GET['dealType']);
$dealID=intval($_GET['dealID']);
$siteID=intval($_GET['siteID']);

if('POST' == $_SERVER['REQUEST_METHOD']) {
	$cp=Array();
	$cp['dealType']=$dealType;
	$cp['siteID']=intval($_POST['siteID']);
	$cp['dealComment']=$_POST['dealComment'];
	$cp['dealDesc']=$_POST['dealDesc'];
	$cp['dealLink']=$_POST['dealLink'];
	$cp['dealVisible'] = intval($_POST['dealVisible'])?intval($_POST['dealVisible']):0;
	$cp['dealSearchPage'] = intval($_POST['dealSearchPage'])?intval($_POST['dealSearchPage']):0;
	
	$photo = pictureUpload('dealPicture',"../../gallery/");
	if($photo){
	$cp["dealPicture"] = $photo[0]['file'];
	}

	if($dealType!=10 && $dealType!=11){
		$que="SELECT * FROM deals WHERE dealType=".$dealType." AND siteID=".intval($_POST['siteID'])." ";
		$checkDeal=udb::single_row($que);
		if($checkDeal){
			udb::update("deals", $cp, "dealType=".$dealType." AND siteID=".intval($_POST['siteID'])." ");
		} else {
			$dealID = udb::insert("deals", $cp);
		}
	} else {
		$que="SELECT * FROM deals WHERE dealID=".$dealID." ";
		$checkDeal=udb::single_row($que);
		
		if($checkDeal){
			udb::update("deals", $cp, "dealID=".$dealID." ");
		} else {
			$dealID = udb::insert("deals", $cp);
		}
	}

	udb::query("DELETE FROM topTenPages WHERE dealID=".$dealID." ");
	if(isset($_POST['searchpage'])){
		foreach($_POST['searchpage'] as $sid=>$val){
			if($val==1){
				$cp=Array();
				$cp['searchID']=$sid;
				$cp['dealID']=$dealID;
				udb::insert("topTenPages", $cp);
			}
		}
	}

	$cp2=Array();
	$cp2['dealID']=$dealID;
	$cp2['portalID']=1;
	if($_POST['fromDate']){
		$date=explode("/", $_POST['fromDate']);
		$date=$date[2]."-".$date[1]."-".$date[0];
		$cp2["fromDate"] = $date;
	}
	if($_POST['toDate']){
		$date=explode("/", $_POST['toDate']);
		$date=$date[2]."-".$date[1]."-".$date[0];
		$cp2["toDate"] = $date;
	}

	
	$que="SELECT * FROM dealsPortals WHERE dealID=".$dealID." ";
	$checkDeal=udb::single_row($que);
	if($checkDeal){
		udb::update("dealsPortals", $cp2, "dealID=".$dealID." ");
	} else {
		udb::insert("dealsPortals", $cp2);
	}


	 ?>
		<script> window.parent.location.reload(); window.parent.closeDealTab(); </script>
	<?php
	


}



$que="SELECT * FROM sites WHERE 1 ORDER BY TITLE";
$sites= udb::key_row($que, "siteID");
if($siteID){
$que="SELECT deals.*, dealsPortals.fromDate, dealsPortals.toDate 
	  FROM deals LEFT JOIN dealsPortals USING(dealID)
	  WHERE dealType=".$dealType." AND siteID=".$siteID." AND portalID=1
	  LIMIT 1";
$deal= udb::single_row($que);
} 

if( ( $dealType==10 || $dealType==11 ) && $dealID){
$que="SELECT deals.*, dealsPortals.fromDate, dealsPortals.toDate 
	  FROM deals LEFT JOIN dealsPortals USING(dealID)
	  WHERE dealType=".$dealType." AND dealID=".$dealID." AND portalID=1
	  LIMIT 1";
$deal= udb::single_row($que);
}


$dealsList=Array();
$dealsList[1]="פרסום בדף הבית";
$dealsList[2]="באנר גדול יוקרתי";
$dealsList[3]="באנר גדול רומנטי";
$dealsList[4]="צימרים מומלצים";
$dealsList[5]="מומלצים דף הבית";
$dealsList[6]="חדשים באתר";
//$dealsList[7]="דילים תפריט ניווט";
$dealsList[8]="קידום טופ טן בעמודי חיפוש לפי קטגוריה";
$dealsList[9]="צימרים שחשבנו שתאהבו";
$dealsList[10]="קידום באנרים שוכב";
$dealsList[11]="קידום באנר ימין";


?>
<div class="editItems">
    <h1><?=$dealsList[$dealType]?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<?php if($dealType==8 && $deal['dealID']){ ?>
		<input type="hidden" name="dealID" value="<?=$deal['dealID']?>">
		<?php } ?>
		<?php if($dealType!=10 && $dealType!=11){ ?>
		<div class="section">
			<div class="inptLine">
				<div class="label">מתחם: </div>
				<select name="siteID">
				<option value="0">בחר מתחם: </option>
				<?php foreach($sites as $site){ ?>
				<option value="<?=$site['siteID']?>" <?=$site['siteID']==$deal['siteID']?"selected":""?>><?=outDb($site['TITLE'])?></option>
				<?php } ?>
				</select>
			</div>
		</div>
		<?php } ?>
		<?php if($dealType==10 || $dealType==11){ ?>
		<div class="section">
			<div class="inptLine">
				<div class="label">כותרת: </div>
				<input type="text" value="<?=$deal['dealComment']?>" name="dealComment" class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">לינק: </div>
				<input type="text" value="<?=$deal['dealLink']?>" name="dealLink" class="inpt">
			</div>
		</div>
		<div style="border:1px solid #ccc">
			<div class="section">
				<div class="inptLine">
					<div class="label">תמונה מייצגת: </div>
					<input type="file" name="dealPicture" class="inpt" value="<?=$deal['dealPicture']?>"><br>
				</div>
			</div>
			<?php if($deal['dealPicture']){ ?>
			<div class="section">
				<div class="inptLine">
					<img src="../../gallery/<?=$deal['dealPicture']?>" style="width:100%">
				</div>
			</div>
			<?php } ?>
		</div>
		<?php } ?>

		<?php if($dealType==2 || $dealType==3 || $dealType==5 || $dealType==6){ ?>
		<?php if($dealType!=6){ ?>
		<div class="section">
			<div class="inptLine">
				<div class="label">טקסט כותרת: </div>
				<input type="text" value="<?=$deal['dealComment']?>" name="dealComment" class="inpt">
			</div>
		</div>
		<?php } ?>
		<div class="section">
			<div class="inptLine">
				<div class="label">טקסט: </div>
				<textarea name="dealDesc"><?=outDB($deal['dealDesc'])?></textarea>
			</div>
		</div>
		<div  style="clear:both;"></div>
		<div style="border:1px solid #ccc">
			<div class="section">
				<div class="inptLine">
					<div class="label">תמונה מייצגת: </div>
					<input type="file" name="dealPicture" class="inpt" value="<?=$deal['dealPicture']?>"><br>
				</div>
			</div>
			<?php if($deal['dealPicture']){ ?>
			<div class="section">
				<div class="inptLine">
					<img src="../../gallery/<?=$deal['dealPicture']?>" style="width:100%">
				</div>
			</div>
			<?php } ?>
		</div>
		<?php } ?>
		<div class="section">
			<div class="inptLine">
				<div class="label">מאושר להצגה: </div>
				<div class="chkBox">
					<input type="checkbox" value="1" <?=$deal['dealVisible']==0?"":"checked"?> name="dealVisible" id="ifShow_<?=$siteID?$siteID:0?>">
					<label for="ifShow_<?=$siteID?$siteID:0?>"></label>
				</div>
			</div>
		</div>
		<div  style="clear:both;"></div>
				<div class="section">
			<div class="inptLine">
				<div class="label">מתאריך: </div>
				<input type="text" value="<?=$deal['fromDate'] && $deal['fromDate']!="0000-00-00" ? date("d/m/Y", strtotime($deal['fromDate'])) : ""?>" name="fromDate" class="inpt datepicker">
			</div>
		</div>
		
		<div class="section">
			<div class="inptLine">
				<div class="label">עד תאריך: </div>
				<input type="text" value="<?=$deal['toDate'] && $deal['toDate']!="0000-00-00" ? date("d/m/Y", strtotime($deal['toDate'])) : ""?>" name="toDate" class="inpt datepicker">
			</div>
		</div>
		<div  style="clear:both;"></div>
		<?php if($dealType==8){
			$que="SELECT * FROM search WHERE active=1";
			$searchPages=udb::full_list($que);
			if($dealID){
			$que="SELECT * FROM topTenPages WHERE dealID=".$dealID."";
			$topTen = udb::key_row($que, "searchID");
			}
			?>
			<div class="section" style="width:100%">
				<div class="inptLine">
					<div class="label">דף חיפוש: </div>
					<div class="cats">
						<?php foreach($searchPages as $pg){ ?>
							<div class="line">
								<input type="checkbox" name="searchpage[<?=$pg['id']?>]" value=1 <?=($topTen[$pg['id']]?"checked":"")?> id="cat_<?=$pg['id']?>">
								<label for="cat_<?=$pg['id']?>"><p><?=outDb($pg['title'])?></p></label>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
			<div  style="clear:both;"></div>
		<?php } ?>

		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$deal['dealID']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>

	</form>
</div>
<script>
$(function() {
	$( ".datepicker" ).datepicker({

	});
});
</script>