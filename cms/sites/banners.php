<?php
include_once "../bin/system.php";
include_once "../bin/top.php";

$dealType=intval($_GET['dealType']);

if(intval($_GET['dealDel'])!=''){
	$dealID=intval($_GET['dealDel']);

	udb::query("DELETE FROM `dealsPortals` WHERE dealID=".$dealID."");
	udb::query("DELETE FROM `deals` WHERE dealID=".$dealID."");

?>
<script>window.location.href='banners.php?dealType='+<?=$dealType?>;</script>
<?php

}

$freeSearch=inputStr($_GET['freeSearch']);
$where="1";
if($freeSearch){
	$where.=" AND TITLE LIKE '%".$freeSearch."%'";
}
if(intval($_GET['active'])==1){
	$where.=" AND if_show=1";
} else if(intval($_GET['active'])==2){
	$where.=" AND if_show=0";
}

if($dealType){
	$que="SELECT deals.dealPicture, deals.dealLink, deals.dealComment, deals.dealID, deals.dealVisible
		  FROM deals
		  WHERE deals.dealType='".$dealType."' 
		  ORDER BY dealID";
	$deals= udb::key_row($que, "dealID");
}

$que="SELECT * FROM `portals` WHERE 1";
$portals= udb::key_row($que, "portalID");

$que="SELECT * FROM `dealsPortals` INNER JOIN `deals` USING (dealID) 
	  WHERE dealType=".$dealType;
$datesDeals= udb::key_row($que, Array("portalID", "dealID"));


$dealsList=Array();
$dealsList[1]="פרסום בדף הבית";
$dealsList[2]="באנר גדול יוקרתי";
$dealsList[3]="באנר גדול רומנטי";
//$dealsList[4]="צימרים מומלצים";
$dealsList[5]="מומלצים דף הבית";
$dealsList[6]="חדשים באתר";
//$dealsList[7]="דילים תפריט ניווט";
$dealsList[8]="קידום טופ טן בעמודי חיפוש לפי קטגוריה";
$dealsList[9]="צימרים שחשבנו שתאהבו";
$dealsList[10]="קידום באנרים שוכב";
$dealsList[11]="קידום באנר ימין";

?>
<script> var defaultPass = "32"; </script>
<div class="popDeal">
	<div class="popDealContent"></div>
</div>
<div class="manageItems" id="manageItems">
    <h1>ניהול צימרים</h1>
	<div class="miniTabs general">	
		<div class="tab<?=!$dealType?" active":""?>" onclick="window.location.href='/cms/sites/index.php'"><p>ניהול כללי</p></div>
		<?php foreach($dealsList as $key=>$dlist){ 
		if($key==10 || $key==11){
			$link="window.location.href='/cms/sites/banners.php?dealType=".$key."'";
		} else {
			$link="window.location.href='/cms/sites/bigdeal.php?dealType=".$key."'";
		}
		?>
			<div class="tab<?=$key==$dealType?" active":""?>" onclick="<?=$link?>"><p><?=$dlist?></p></div>
		<?php } ?>
	</div>
    <div class="filter" style="margin-top:0;border-top:1px solid #fff;">
        <h2>חיפוש צימר:</h2>
        <form method="get">
            <div>
                <input type="search" name="freeSearch" <?php if(isset($freeSearch)){ echo 'value="'.$freeSearch.'"'; }?> placeholder="שם הצימר" autocomplete="off">
                <select name="active" style="width:100px;">
					<option value="0">-</option>
					<option value="1" <?=intval($_GET['active'])==1?"selected":""?>>פעיל</option>
					<option value="2" <?=intval($_GET['active'])==2?"selected":""?>>לא פעיל</option>
				</select>
				<input type="submit" value="חפש">
            </div>
        </form>
    </div>

	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openDealPop('-1', <?=$dealType?>, 0)">
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>כותרת</th>
            <th>לינק</th>
            <th>מוצג/לא מוצג</th>
            <?php foreach($portals as $portal){ ?>
			<th width="100"><?=$portal['portalShort']?></th>
			<?php } ?>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php foreach($deals as $deal){ ?>
            <tr>
                <td onclick="openDealPop(<?=$deal['dealID']?>, <?=$dealType?>, 0)"><?=($deal['dealID'])?></td>
                <td onclick="openDealPop(<?=$deal['dealID']?>, <?=$dealType?>, 0)"><?=outDb($deal['dealComment'])?></td>
                <td onclick="openDealPop(<?=$deal['dealID']?>, <?=$dealType?>, 0)"><?=$deal['dealLink']?></td>
                <td onclick="openDealPop(<?=$deal['dealID']?>, <?=$dealType?>, 0)"><?=($deal['dealVisible']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<?php foreach($portals as $portal){ 

				if($datesDeals[$portal['portalID']][$deal['dealID']]['toDate'] >= date("Y-m-d") && $datesDeals[$portal['portalID']][$deal['dealID']]['fromDate'] <= date("Y-m-d")){
					$color="green";
				} else if($datesDeals[$portal['portalID']][$deal['dealID']]['toDate'] > date("Y-m-d") && $datesDeals[$portal['portalID']][$deal['dealID']]['fromDate'] > date("Y-m-d")) {
					$color="blue";
				} else {
					$color="red";
				}
				?>
				<td width="100" onclick="openDealPop(<?=$deal['dealID']?>, <?=$dealType?>, <?=$deal['dealID']?>)">
					<div style="font-size:12px;line-height:12px;color:<?=$color?>"><?=$datesDeals[$portal['portalID']][$deal['dealID']]['fromDate'] ? date("d.m.Y", strtotime($datesDeals[$portal['portalID']][$deal['dealID']]['fromDate'])) : ""?></div>
					<div style="font-size:12px;line-height:12px;color:<?=$color?>"><?=$datesDeals[$portal['portalID']][$deal['dealID']]['toDate'] ? date("d.m.Y", strtotime($datesDeals[$portal['portalID']][$deal['dealID']]['toDate'])) : ""?></div>
				</td>
				<?php } ?>
				<td align="center" class="actb">
				<div onClick="if(confirm('אתה בטוח רוצה למלוק את הדיל?')){deleteDeal(<?=$deal['dealID']?>, <?=$dealType?>); }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
				</td>
            </tr>
			<?php } ?>
        </tbody>
    </table>
</div>
<style>
.manageItems table > thead > tr > th:nth-child(2){width:200px;}
.manageItems table > thead > tr > th:nth-child(3){width:130px;}
.manageItems table > thead > tr > th:nth-child(4){width:130px;}
.manageItems table > thead > tr > th:nth-child(5){width:130px;}
.manageItems table > thead > tr > th:nth-child(6){width:130px;}
.manageItems table > thead > tr > th:nth-child(7){width:130px;}
.manageItems table > thead > tr > th:nth-child(8){width:100px;}
.manageItems table > thead > tr > th:nth-child(9){width:40px;}
</style>
<input type="hidden" id="orderResult" name="orderResult" value="">
<script>
function openDealPop(dealID, dealType, siteID){
	$(".popDealContent").html('<iframe id="frame_'+dealType+'_'+siteID+'" frameborder=0 src="/cms/sites/minibigdeal.php?dealID='+dealID+'&dealType='+dealType+'&siteID='+siteID+'"></iframe><div class="tabCloser" onclick="closeDealTab()">x</div>');
	$(".popDeal").show();
}

function closeDealTab(){
	$(".popDealContent").html('');
	$(".popDeal").hide();
}

function deleteDeal(dealID, dealType){
 s=prompt('הכנס סיסמא','');
 if(s==defaultPass){
	if(confirm("בטוח רוצה למחוק את הדיל?")){
		location.href='?dealDel='+dealID+'&dealType='+dealType; 
	}
 } else {
	alert("סיסמא שגויה");
 } 
}
</script>
<?php
include_once "../bin/footer.php";
?>