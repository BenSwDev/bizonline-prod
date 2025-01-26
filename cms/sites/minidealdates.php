<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$dealType=intval($_GET['dealType']);
$dealID=intval($_GET['dealID']);
$siteID=intval($_GET['siteID']);

if('POST' == $_SERVER['REQUEST_METHOD']) {
	$cp=Array();
	$cp['dealID']=$dealID;
	$cp['portalID']=1;
	if($_POST['fromDate']){
		$date=explode("/", $_POST['fromDate']);
		$date=$date[2]."-".$date[1]."-".$date[0];
		$cp["fromDate"] = $date;
	}
	if($_POST['toDate']){
		$date=explode("/", $_POST['toDate']);
		$date=$date[2]."-".$date[1]."-".$date[0];
		$cp["toDate"] = $date;
	}

	
	$que="SELECT * FROM dealsPortals WHERE dealID=".$dealID." ";
	$checkDeal=udb::single_row($que);
	if($checkDeal){
		udb::update("dealsPortals", $cp, "dealID=".$dealID." ");
	} else {
		udb::insert("dealsPortals", $cp);
	}

	 ?>
		<script> window.parent.location.reload(); window.parent.closeDealTab(); </script>
	<?php
	


}



$que="SELECT siteID, TITLE FROM sites WHERE siteID=".$siteID." ";
$site= udb::single_row($que);
if($siteID){
	$que="SELECT * FROM deals LEFT JOIN dealsPortals USING (dealID) WHERE dealType=".$dealType." AND siteID=".$siteID." LIMIT 1";
	$deal= udb::single_row($que);
}

$dealsList=Array();
$dealsList[1]="פרסום בדף הבית";
$dealsList[2]="באנר גדול יוקרתי";
$dealsList[3]="באנר גדול רומנטי";
$dealsList[4]="צימרים מומלצים";
$dealsList[5]="מומלצים דף הבית";
$dealsList[6]="חדשים באתר";
$dealsList[7]="דילים תפריט ניווט";
$dealsList[8]="קידום טופ טן בעמודי חיפוש לפי קטגוריה";
$dealsList[9]="צימרים שחשבנו שתאהבו";
$dealsList[10]="קידום באנרים שוכב";
$dealsList[11]="קידום באנר ימין";

?>
<div class="editItems">
    <h1><?=$dealsList[$dealType]?> - <?=$site['TITLE']?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		
		<div class="section">
			<div class="inptLine">
				<div class="label">מתאריך: </div>
				<input type="text" value="<?=$deal['fromDate'] ? date("d/m/Y", strtotime($deal['fromDate'])) : ""?>" name="fromDate" class="inpt datepicker">
			</div>
		</div>
		
		<div class="section">
			<div class="inptLine">
				<div class="label">עד תאריך: </div>
				<input type="text" value="<?=$deal['toDate'] ? date("d/m/Y", strtotime($deal['toDate'])) : ""?>" name="toDate" class="inpt datepicker">
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
<script>
$(function() {
	$( ".datepicker" ).datepicker({

	});
});
</script>