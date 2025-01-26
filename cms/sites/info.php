<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";


$siteID=intval($_GET['sID']);
$frameID=intval($_GET['frame']);

$position=2;

if('POST' == $_SERVER['REQUEST_METHOD']) {

	if(!intval($_POST['refresh'])){ // save and close iframe ?>
		<script> window.parent.closeTab(<?=$frameID?>); </script>
	<?php
	} else { // save and get alert success ?>
		<script> window.parent.formAlert("green", "עודכן בהצלחה", ""); </script>
	<?php }
}

$que="SELECT * FROM `sites` WHERE siteID=".$siteID."";
$site= udb::single_row($que);


$menu = include "site_menu.php";


$pageID=intval($_GET['pageID']);

$que="SELECT sitesCustoms.customID, sitesCustoms.customTitle, sitesCustoms.customKey, sitesCustoms.ifShow, sitesCustoms.html_text
	  FROM `sitesCustoms` 
	  WHERE `LangID` = 1 AND `siteID` = ".$siteID." AND (`customKey` IS NOT NULL) 
	  ORDER BY `showOrder`, sitesCustoms.showOrder";
$addon= udb::full_list($que);

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
		<div class="minitab" onclick="window.location.href='<?=$sub['href']?>?frame=<?=$frameID?>&sID=<?=$siteID?>'"><?=$sub['name']?></div>
		<?php } ?>
	</div>
	<?php } ?>
	<div class="manageItems">
		<b>מידע נוסף</b>
		<table>
			<thead>
			<tr>
				<th>#</th>
				<th style="width:10%">כותרת</th>
				<th style="width:40%">טקסט</th>
				<th style="width:8%">מוצג/לא מוצג</th>
			</tr>
			</thead>
			<tbody>
		<?php foreach($addon as $add){ ?>
				<tr onclick="openPop(<?=$add['customID']?>, <?=$siteID?>)">
					<td><?=$add['customID']?></td>
					<td><?=$add['customTitle']?></td>
					<td><?=outDB($add['html_text'])?></td>
					<td><?=($add['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>	
	</div>
</div>
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
function openPop(customID, siteID){
	$(".popRoomContent").html('<iframe id="frame_'+customID+'_'+siteID+'" frameborder=0 src="/cms/sites/customInfo.php?customID='+customID+'&siteID='+siteID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+customID+'_'+siteID+'\')">x</div>');
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