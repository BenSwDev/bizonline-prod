<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$position=7;

$siteID=intval($_GET['sID']);
$frameID=intval($_GET['frame']);

if('POST' == $_SERVER['REQUEST_METHOD']) {

}


if ($rdel = intval($_GET['rdel'])){
	$que = "DELETE sitesRooms.*, sitesPrices.* 
			FROM `sitesRooms` LEFT JOIN `sitesPrices` ON (sitesRooms.roomID = sitesPrices.roomID)
			WHERE sitesRooms.siteID = ".$siteID." AND sitesRooms.roomID = ".$rdel;
	mysql_query($que) or report_error(__FILE__,__LINE__,$que);
	$que = "OPTIMIZE TABLE `sitesRooms`, `sitesPrices`";
	mysql_query($que) or report_error(__FILE__,__LINE__,$que);
}


$que="SELECT * FROM `sites` WHERE siteID=".$siteID."";
$site= udb::single_row($que);


$que="SELECT * FROM `sitesContacts` WHERE contactSiteID = ".$siteID." ORDER BY contactDate";
$contacts= udb::full_list($que);

$menu = include "site_menu.php";

$contactType=Array();
$contactType[1]="שאלה לבעל המתחם";
$contactType[2]="בקשה להזמנת נופש";


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



	<div class="manageItems">		
		<table>
			<thead>
			<tr>
				<th width="30">#</th>
				<th>תאריך פניה</th>
				<th>שם לקוח</th>
				<th>נושא הפנייה</th>
				<th>סטטוס</th>
				<th width="60">&nbsp;</th>
			</tr>
			</thead>
			<tbody id="sortRow">
			<?php 
			$total = count($contacts);
			foreach($contacts as $row) { ?>
				<tr id="<?=$row['contactID']?>">
					<td onclick="openContact(<?=$row['contactID']?>, <?=$siteID?>)" align="center"><?=$row['contactID']?></td>
					<td style="text-align:right;direction:ltr;" onclick="openContact(<?=$row['contactID']?>, <?=$siteID?>)"><?=date("d.m.Y H:i:s", strtotime($row['contactDate']))?></td>
					<td onclick="openContact(<?=$row['contactID']?>, <?=$siteID?>)"><?=$row['contactName']?></td>
	                <td onclick="openContact(<?=$row['contactID']?>, <?=$siteID?>)"><?=$contactType[$row['contactType']]?></td>
	                <td><?=$row['contactStatus']==1?"<span style='color:green'>טופל</span>":"<span style='color:red'>לא טופל</span>"?></td>
	                <td></td>
				</tr>
			<? } ?>
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
function openContact(contactID, siteID){
	$(".popRoomContent").html('<iframe id="frame_'+contactID+'_'+siteID+'" frameborder=0 src="/cms/sites/minicontact.php?contactID='+contactID+'&siteID='+siteID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+contactID+'_'+siteID+'\')">x</div>');
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