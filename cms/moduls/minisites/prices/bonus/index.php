<?php
include_once "../../bin/system.php";
include_once "../../bin/top.php";

$siteID = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);
/*
$bonus = udb::full_list("SELECT * FROM `bonus` WHERE 1");

if($_GET['bonusDel']==1 && $_GET['bonusID']){

	$mainpageid=intval($_GET['bonusID']);
	udb::query("DELETE FROM `bonus` WHERE id=".$mainpageid."");
	udb::query("DELETE FROM `bonus_langs` WHERE id=".$mainpageid."");

?>
<script>window.parent.closeTab();</script>
<?php
}


*/
?>

<div class="modulFrame">
	<iframe src="/SiteManager/tab-benefits.php?siteID=<?=$siteID?>" frameborder="0" width="100%" height="100%"></iframe>
</div>	

<?/*?>
<div class="popRoom"><div class="popRoomContent"></div></div>
<div class="manageItems" id="manageItems">
    <h1>הטבות</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0, <?=$siteID?>)">
	</div>
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>כותרת</th>
			<th>סוג</th>
			<th width="40">פעיל</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
<?php
    if (count($bonus)){
        foreach($bonus as $extra){
?>
            <tr id="<?=$extra['id']?>">
                <td><?=$extra['id']?></td>
                <td onclick="openPop(<?=$extra['id']?>,<?=$siteID?>)"><?=$extra['bonusName']?></td>
                <td onclick="openPop(<?=$extra['id']?>,<?=$siteID?>)"><?=outDb($extra['bonusPrice'])?></td>
                <td><?=($extra['active']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
                <td><div onclick="if(confirm('האם אתה בטוח רוצה למחוק את התוספת?')){location.href='?bonusDel=1&bonusID=<?=$extra['id']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
            </tr>
<?php
			}
			}
?>
        </tbody>
    </table>
</div>
<input type="hidden" id="orderResult" name="orderResult" value="">
<script>
var pageType="<?=$pageType?>";
function openPop(pageID,siteID){
	$(".popRoomContent").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/minisites/prices/bonus/frame.php?id='+pageID+'&siteID='+siteID+'&tab=1"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".popRoom").show();
	window.parent.parent.$('.tabCloser').hide();
}
function closeTab(){
	$(".popRoomContent").html('');
	$(".popRoom").hide();
	window.parent.parent.$('.tabCloser').show();
}


</script>
<?php  */ ?>




