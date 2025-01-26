<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";
require_once "../classes/class.PriceCache.php";
$position=4;

$siteID=intval($_GET['sID']);
$frameID=intval($_GET['frame']);

if('POST' == $_SERVER['REQUEST_METHOD']) {
	if(!intval($_POST['refresh'])){ // save and close iframe ?>
	<script>
		window.parent.closeTab(<?=$frameID?>);
	</script>
	<?php
	} else { // save and get alert success ?>
	<script>
		window.parent.formAlert("green", "עודכן בהצלחה", "");
	</script>
	<?php }
}
elseif($specID = intval($_GET['Ddel'])){
    $que = "DELETE sitesSpecials.*, sitesSpecialsExtras.*, sitesSpecialsRooms.* 
	        FROM `sitesSpecials` LEFT JOIN `sitesSpecialsExtras` USING(`specID`)
	            LEFT JOIN `sitesSpecialsRooms` ON (sitesSpecialsRooms.specID = sitesSpecials.specID)
	        WHERE sitesSpecials.specID = " . $specID . " AND sitesSpecials.siteID = " . $siteID;
    udb::query($que);

    PriceCache::updateTomorrow($siteID);
    PriceCache::updateWeekend($siteID);
    PriceCache::updateVideo($siteID);
	
	?>
	<script>
		window.location.href="/cms/sites/deals.php?frame=<?=$frameID?>&sID=<?=$siteID?>";
	</script>

<?php } 

$dealsTypes=Array(1=>"בין תאריכים", 2=>"קבוע");
$daysInWeek=Array(1=>"כל ימות השבוע", 2=>'אמצ"ש', 3=>'ספ"ש');
$periodInYear=Array(1=>"כל תקופה", 2=>"תקופה רגילה בלבד");
$limitations=Array(1=>"יום לפני הזמנה", 2=>"עד יומיים לפני הזמנה", 3=>"עד 3 ימים לפני הזמנה", 4=>"ללא הגבלה");
$dealTo=Array(1=>"לילה אחד ומעלה", 2=>"לילה שני", 3=>"לילה שלישי", 4=>"יום כיף");



$que="SELECT * FROM `sites` WHERE siteID=".$siteID."";
$site= udb::single_row($que);

$que="SELECT * FROM `sitesSpecials` WHERE siteID=".$siteID."";
$deals= udb::full_list($que);

$menu = include "site_menu.php";

?>
<div class="popRoom">
	<div class="popRoomContent"></div>
</div>
<div class="editItems">
    <h1><?=outDb($site['TITLE'])?></h1>
	<div class="miniTabs">
		<?php foreach($menu as $men){ 
		if($men['position']==$position && $men['sub']){
			$subMenu = $men['sub'];
		}
		?>
		<div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>?frame=<?=$frameID?>&sID=<?=$siteID?>'"><p><?=$men['name']?></p></div>
		<?php } ?>
	</div>
	<?php if($subMenu){ ?>
	<div class="subMenuTabs">
		<?php foreach($subMenu as $sub){ ?>
		<div class="minitab<?=$sub['position']==$subposition?" active":""?>" onclick="window.location.href='<?=$sub['href']?>?frame=<?=$frameID?>&sID=<?=$siteID?>'"><?=$sub['name']?></div>
		<?php } ?>
	</div>
	<?php } ?>


	<div class="manageItems">		
		<div style="margin-top: 20px;">
			<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openDeal('new', <?=$siteID?>)" tab-id=""  style="width:120px">

		</div>
		<table>
			<thead>
			<tr>
				<th width="30">#</th>
				<th>סוג מבצע</th>
				<th>אחוזי הנחה</th>
				<th>ימי שבוע</th>
				<th>פעיל</th>
				<th width="60">בלעדי</th>
				<th width="60">&nbsp;</th>
			</tr>
			</thead>
			<tbody id="sortRow">
			<?php 
			$total = count($deals);
			foreach($deals as $row) { 
				?>
				<tr id="<?=$row['specID']?>">
					<td align="center"><?=$row['specID']?></td>
					<td onclick="openDeal(<?=$row['specID']?>, <?=$siteID?>)"><?=$dealsTypes[$row['dealType']]?></td>
					<td onclick="openDeal(<?=$row['specID']?>, <?=$siteID?>)"><?=$row['discount']?$row['discount']."% הנחה":"מתנה"?></td>
					<td onclick="openDeal(<?=$row['specID']?>, <?=$siteID?>)"><?=$daysInWeek[$row['daysInWeek']]?></td>	
					<td onclick="openDeal(<?=$row['specID']?>, <?=$siteID?>)"><?=$row['active']?"כן":"לא"?></td>	
					<td onclick="openDeal(<?=$row['specID']?>, <?=$siteID?>)"><?=$row['exclusive']?"כן":"לא"?></td>	
					<td class="actb" align="center"><div onclick="openDeal(<?=$row['specID']?>, <?=$siteID?>)"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;ערוך</div><div>|</div><div onClick="if(confirm('You are about to delete area. Continue?')){location.href='?sID=<?=$siteID?>&frame=<?=$frameID?>&Ddel=<?=$row['specID']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
				</tr>
			<? } ?>
			</tbody>
		</table>
	</div>
</div>
<input type="hidden" id="orderResult" name="orderResult" value="">
</section>
<div id="alerts">
    <div class="container">
        <div class="closer"></div>
        <div class="title"></div>
        <div class="body"></div>
    </div>
</div>
<script src="<?=$root;?>/app/jquery-ui.min.js"></script>
<script>
function openDeal(dealID, siteID){
	$(".popRoomContent").html('<iframe id="frame_'+dealID+'_'+siteID+'" frameborder=0 src="/cms/sites/minideal.php?dealID='+dealID+'&siteID='+siteID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+dealID+'_'+siteID+'\')">x</div>');
	$(".popRoom").show();
	window.parent.document.getElementById("frame"+<?=$frameID?>).style.zIndex="12";
}

function closeTab(id){
	$(".popRoomContent").html('');
	$(".popRoom").hide();
	window.parent.document.getElementById("frame"+<?=$frameID?>).style.zIndex="10";
}
</script>
</body>
</html>