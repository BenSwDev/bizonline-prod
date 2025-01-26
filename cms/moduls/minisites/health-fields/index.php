<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";


$siteID=intval($_GET['siteID']);
$frameID=intval($_GET['frame']);
$siteName = $_GET['siteName'];

$pages = udb::full_list("SELECT `sites`.* , sites_health_fields.* FROM `sites`
INNER JOIN `sites_health_fields` USING (siteID)
INNER JOIN sites_domains USING (siteID)
WHERE `siteID`=".$siteID." AND sites_domains.domainID=1 ORDER BY sites_health_fields.`showOrder`");

$fieldTypes = array(
	'text' => 'שדה טקסט',
	'tel' => 'שדה טלפון'

);
?>


<div class="popRoom">
	<div class="popRoomContent"></div>
</div>
<div class="editItems">
	<div class="siteMainTitle"><?=$siteName?></div>
    <?php minisite_domainTabs($domainID,"2")?>
	<?=showTopTabs(0)?>
	<div class="manageItems" id="manageItems">
		<h1>ניהול שדות הצהרות בריאות</h1>
		<div style="margin-top: 20px;">
			<!-- <input type="button" class="addNew" id="addNewAcc" value="הוסף שדה" onclick="openPopField(0, <?=$siteID?>)"> -->
			<?php if($pages){ ?>
			<input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה">
			<?php } ?>
		</div>
		<table>
			<thead>
			<tr>
				<th>#</th>
				<th>שם השדה</th>
				<th>סוג השדה</th>
				<th>מוצג</th>
				<!-- <th></th> -->
			</tr>
			</thead>
			<tbody id="sortRow">
				<?php
				if($pages){
				foreach($pages as $page){ ?>
				<tr id="<?=$page['fieldID']?>">
					<td><?=$page['fieldID']?></td>
					<td onclick="openPopField(<?=$page['fieldID']?>,<?=$siteID?>)"><?=$page['fieldLabel']?></td>
					<td onclick="openPopField(<?=$page['fieldID']?>,<?=$siteID?>)"><?=$fieldTypes[$page['fieldType']]?></td>
					<td><?=($page['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>

				</tr>
				<?php }
				} ?>
			</tbody>
		</table>
	</div>
</div>
<input type="hidden" id="orderResult" name="orderResult" value="">
<script type="text/javascript">

function openPopField(fieldID, siteID){
	$(".popRoomContent").html('<iframe id="frame_'+siteID+'_'+fieldID+'" frameborder=0 src="/cms/moduls/minisites/health-fields/popField.php?fieldID='+fieldID+'&siteID='+siteID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+siteID+'_'+fieldID+'\')">x</div>');
	$(".popRoom").show();
	window.parent.parent.$('.tabCloser').hide();

}

function closeTab(reload){
	$(".popRoomContent").html('');
	$(".popRoom").hide();
	window.parent.parent.$('.tabCloser').show();
    reload && window.location.reload();
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
		url: 'js_order_pages.php',
		type: 'POST',
		data: {ids:ids},
		async: false,
		success: function (myData) {
			window.location.reload();
		}
	});
}
</script>