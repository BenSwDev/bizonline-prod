<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$position=3;
$subposition=1;

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



$que="SELECT sitesRooms.* FROM `sitesRooms` WHERE sitesRooms.siteID = ".$siteID." ORDER BY showOrder";
$rooms= udb::key_row($que, "roomID");

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
			<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPopRoom('new', <?=$siteID?>)" tab-id=""  style="width:120px">
			<?php if($rooms){ ?>
			<input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה" style="width:120px">
			<?php } ?>
		</div>
		<table>
			<thead>
			<tr>
				<th width="30">#</th>
				<th>שם החדר</th>
				<th>סוג החדר</th>
				<th>מוצג / לא מוצג</th>
				<th width="60">&nbsp;</th>
			</tr>
			</thead>
			<tbody id="sortRow">
			<?php 
			$total = count($rooms);
			foreach($rooms as $row) { ?>
				<tr id="<?=$row['roomID']?>">
					<td align="center"><?=$row['roomID']?></td>
					<td onclick="openPopRoom(<?=$row['roomID']?>, <?=$siteID?>)"><?=outDb($row['roomName'])?></td>
					<td align="center"><?=($row['roomType'] == 2 ? 'צמוד חדר ילדים' : ($row['roomType']==1 ? 'חדר לזוג' : 'לא נבחר סוג חדר' ))?></td>
	                <td onclick="openPopRoom(<?=$row['roomID']?>, <?=$siteID?>)"><?=($row['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
					<td align="center" class="actb">
					<div onclick="openPopRoom(<?=$row['roomID']?>, <?=$siteID?>)"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;ערוך</div><div>|</div><div onClick="if(confirm('You are about to delete area. Continue?')){location.href='?sID=<?=$siteID?>&frame=<?=$frame?>&rdel=<?=$row['roomID']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
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
function openPopRoom(roomID, siteID){
	$(".popRoomContent").html('<iframe id="frame_'+roomID+'_'+siteID+'" frameborder=0 src="/cms/sites/miniroom.php?roomID='+roomID+'&siteID='+siteID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+roomID+'_'+siteID+'\')">x</div>');
	$(".popRoom").show();
	window.parent.document.getElementById("frame<?=$frameID?>").style.zIndex="12";
}

function closeTab(id){
	$(".popRoomContent").html('');
	$(".popRoom").hide();
	window.parent.document.getElementById("frame<?=$frameID?>").style.zIndex="10";
}

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
		url: 'js_order_rooms.php',
		type: 'POST',
		data: {ids:ids, siteID:<?=$siteID?>},
		async: false,
		success: function (myData) {
			window.location.reload();
		}
	});
}
</script>

</body>
</html>