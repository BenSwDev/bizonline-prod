<?php
include_once "../bin/system.php";
include_once "../bin/top.php";

$dealType=intval($_GET['dealType']);

if(intval($_GET['siteDel'])!=''){
	$siteID=intval($_GET['siteDel']);
	udb::query("DELETE FROM `sitesOptions` WHERE siteID=".$siteID." ");
	udb::query("DELETE FROM `sitesOptionsTags` WHERE siteID=".$siteID." ");
	udb::query("DELETE FROM `sitesPeriods` WHERE siteID=".$siteID." ");
	udb::query("DELETE FROM `sitesExtras` WHERE siteID=".$siteID." ");
	udb::query("DELETE FROM `sitesCustoms` WHERE siteID=".$siteID." ");
	$que="SELECT sitesRooms.roomID FROM `sitesRooms` WHERE sitesRooms.siteID = ".$siteID." ORDER BY showOrder";
	$rooms= udb::key_row($que, "roomID");
	if($rooms){
		foreach($rooms as $room){
			udb::query("DELETE FROM `sitesMinNights` WHERE roomID=".$room['roomID']." ");
		}
	}
	$que="SELECT GalleryID FROM `galleries` WHERE sID=".$siteID."";
	$galleries= udb::full_list($que);
	if($galleries){
		foreach($galleries as $gal){
			$que="SELECT src, id  FROM `files` WHERE ref=".$gal['GalleryID']." AND `table`='site'";
			$images= udb::full_list($que);
			if($images){
				foreach($images as $image){
					$filename = "../../gallery/".$image['src'];
					if (file_exists($filename)) {
						unlink($filename);
						
					} else {
						echo 'Could not delete '.$filename.', file does not exist';
						exit;
					}
					udb::query("DELETE FROM files WHERE id=".$image['id']." ");
				}
			}
			udb::query("DELETE FROM galleries WHERE GalleryID=".$gal['GalleryID']." AND sID=".$siteID." ");
		}
	}
	udb::query("DELETE FROM `sitesRooms` WHERE siteID=".$siteID." ");
	udb::query("DELETE FROM `alias` WHERE ref=".$siteID." AND `table`='sites' ");
	udb::query("DELETE FROM `Comments` WHERE siteID=".$siteID."");
	
	udb::query("DELETE FROM `sites` WHERE siteID=".$siteID."");

?>
<script>window.location.href='index.php';</script>
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


$que="SELECT * FROM `sites` WHERE ".$where." ORDER BY TITLE";
$sites= udb::key_row($que, "siteID");


$que="SELECT * FROM `areas` WHERE `status` = 1 ";
$areas= udb::key_row($que, "areaID");

$que="SELECT * FROM `settlements` WHERE 1";
$cities= udb::key_row($que, "settlementID");

$que="SELECT * FROM `portals` WHERE portalID=1";
$portals= udb::key_row($que, "portalID");

$que="SELECT * FROM `dealsPortals` INNER JOIN `deals` USING (dealID) 
	  WHERE dealType=0 AND portalID=1";
$datesDeals= udb::key_row($que, Array("portalID", "siteID"));



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
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openFrameSiteAdd(this, '0', 'צימר חדש')" tab-id="">
		<?php if($sites){ ?>
		<input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה">
		<?php } ?>
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>

            <th>שם הצימר</th>
            <th>כניסות</th>
			<th>איש קשר</th>
            <th>טלפון</th>
            <th>עיר</th>
            <th>אזור</th>
            <th>מוצג/לא מוצג</th>
            <?php foreach($portals as $portal){ ?>
			<th><?=$portal['portalShort']?></th>
			<?php } ?>
			<th>הקפץ</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php foreach($sites as $site){ ?>
            <tr id="<?=$site['siteID']?>" tab-id="">
                <td onclick="openFrameSite(this, <?=$site['siteID']?>, '<?=$site['TITLE']?>')"><?=($site['siteID'])?></td>

                <td onclick="openFrameSite(this, <?=$site['siteID']?>, '<?=$site['TITLE']?>')"><?=outDb($site['TITLE'])?></td>
				<td onclick="openFrameSite(this, <?=$site['siteID']?>, '<?=$site['TITLE']?>')"><?=intval($site['counter'])?></td>
				<td onclick="openFrameSite(this, <?=$site['siteID']?>, '<?=$site['TITLE']?>')"><?=outDb($site['owners'])?></td>
                <td onclick="openFrameSite(this, <?=$site['siteID']?>, '<?=$site['TITLE']?>')"><?=$site['phone1']?></td>
                <td onclick="openFrameSite(this, <?=$site['siteID']?>, '<?=$site['TITLE']?>')"><?=$cities[$site['settlementID']]['TITLE']?></td>
                <td onclick="openFrameSite(this, <?=$site['siteID']?>, '<?=$site['TITLE']?>')"><?=$areas[$cities[$site['settlementID']]['areaID']]['TITLE']?></td>
                <td onclick="openFrameSite(this, <?=$site['siteID']?>, '<?=$site['TITLE']?>')"><?=($site['if_show']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<?php foreach($portals as $portal){ 
				if($datesDeals[$portal['portalID']][$site['siteID']]['toDate'] >= date("Y-m-d") && $datesDeals[$portal['portalID']][$site['siteID']]['fromDate'] <= date("Y-m-d")){
					$color="green";
				} else if($datesDeals[$portal['portalID']][$site['siteID']]['toDate'] > date("Y-m-d") && $datesDeals[$portal['portalID']][$site['siteID']]['fromDate'] > date("Y-m-d")) {
					$color="blue";
				} else {
					$color="red";
				}
				?>
				<td width="100" >
					<div style="font-size:12px;line-height:12px;color:<?=$color?>"><?=$datesDeals[$portal['portalID']][$site['siteID']]['fromDate'] && $datesDeals[$portal['portalID']][$site['siteID']]['fromDate']!="0000-00-00" ? date("d.m.Y", strtotime($datesDeals[$portal['portalID']][$site['siteID']]['fromDate'])) : ""?></div>
					<div style="font-size:12px;line-height:12px;color:<?=$color?>"><?=$datesDeals[$portal['portalID']][$site['siteID']]['toDate'] && $datesDeals[$portal['portalID']][$site['siteID']]['toDate']!="0000-00-00" ? date("d.m.Y", strtotime($datesDeals[$portal['portalID']][$site['siteID']]['toDate'])) : ""?></div>
				</td>
				<?php } ?>
				<td ><a href="/cms/user?siteID=<?=$site['siteID']?>" target="_blank">הקפץ</a></td>
				<td  width="100" align="center" class="actb">
				<div onClick="if(confirm('האם אתה בטוח? כל נתוני המתחם כולל חדרים, מחירים, גלריות ומידע נוסף ימחק!!!!!')){deleteMinisite(<?=$site['siteID']?>); }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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
.manageItems table > thead > tr > th:nth-child(10){width:40px;}
</style>
<input type="hidden" id="orderResult" name="orderResult" value="">
<script>
function orderNow(is){
	$("#addNewAcc").hide();
	$(is).val("שמור סדר תצוגה");
	$(is).attr("onclick", "saveOrder()");
	$("#sortRow tr").attr("onclick", "");
	$("#sortRow").sortable({
		stop: function(){
			$("#orderResult").val($("#sortRow").sortable('toArray'));
		}
	});
	$("#orderResult").val($("#sortRow").sortable('toArray'));
}
function saveOrder(){
	var ids = $("#orderResult").val();
	$.ajax({
		url: 'js_order_sites.php',
		type: 'POST',
		data: {ids:ids},
		async: false,
		success: function (myData) {
			window.location.reload();
		}
	});
}

function deleteMinisite(siteID){
 s=prompt('הכנס סיסמא','');
 if(s==defaultPass){
	if(confirm("אתה באמת מתכוון למחוק את המתחם??????")){
		location.href='?siteDel='+siteID; 
	}
 } else {
	alert("סיסמא שגויה");
 } 
}
</script>
<?php
include_once "../bin/footer.php";
?>