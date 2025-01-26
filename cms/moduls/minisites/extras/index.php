<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";

if($_GET['extraDel']==1 && $_GET['extraID']){

    $mainpageid=intval($_GET['extraID']);
    udb::query("DELETE FROM `extra` WHERE id=".$mainpageid."");
    udb::query("DELETE FROM `extra_langs` WHERE id=".$mainpageid."");

?>
    <script>window.parent.closeTab();</script>
<?php
}


$siteID = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);
$extras = udb::full_list("SELECT * FROM `extra` WHERE siteID=".$siteID);

?>

<div class="popRoom">
	<div class="popRoomContent"></div>
</div>
<div class="editItems">
	<div class="siteMainTitle"><?=$siteName?></div>
    <?php minisite_domainTabs($domainID,"2")?>
	<?=showTopTabs(0)?>
    <div class="manageItems" id="manageItems">
        <h1>ניהול תוספות</h1>
        <div style="margin-top: 20px;">
            <input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0, <?=$siteID?>)">
        </div>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>שם התוספת</th>
                <th>מחיר</th>
                <th width="40">מוצג</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody id="sortRow">
            <?php
            if (count($extras)){
                foreach($extras as $extra){
                    ?>
                    <tr id="<?=$extra['id']?>">
                        <td><?=$extra['id']?></td>
                        <td onclick="openPop(<?=$extra['id']?>,<?=$siteID?>)"><?=$extra['extraName']?></td>
                        <td onclick="openPop(<?=$extra['id']?>,<?=$siteID?>)"><?=outDb($extra['extraPrice'])?></td>
                        <td><?=($extra['active']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
                        <td><div onclick="if(confirm('האם אתה בטוח רוצה למחוק את התוספת?')){location.href='?extraDel=1&extraID=<?=$extra['id']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
                    </tr>
                    <?php
                }
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
<input type="hidden" id="orderResult" name="orderResult" value="">
<script type="text/javascript">

function openPop(pageID,siteID){
    $(".popRoomContent").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/minisites/prices/extras/frame.php?id='+pageID+'&siteID='+siteID+'&tab=1"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
    $(".popRoom").show();
    window.parent.parent.$('.tabCloser').hide();
}

function closeTab(id){
	$(".popRoomContent").html('');
	$(".popRoom").hide();
	window.parent.parent.$('.tabCloser').show();
}
</script>
<?php
include_once "../../../bin/system.php";
