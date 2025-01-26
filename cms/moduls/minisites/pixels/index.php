<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";

$siteID = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);
$json   = udb::single_value("SELECT `pixels` FROM `sites` WHERE `siteID` = " . $siteID);
$pixels = json_decode($json ?: "[]", true);

if(!empty($_GET['pdel'])){
    $pHash = typemap($_GET['pdel'], 'string');

    if (isset($pixels[$pHash])){
        unset($pixels[$pHash]);
        udb::update("sites", ['pixels' => json_encode($pixels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)], "`siteID` = " . $siteID);
    }

    echo '<script>window.parent.closeTab();</script></section></body></html>';
    exit;
}
?>

<div class="popRoom">
	<div class="popRoomContent"></div>
</div>
<div class="editItems">
<?php
    minisite_domainTabs($domainID, "2");
	echo showTopTabs(0);
?>
    <div class="manageItems" id="manageItems">
        <h1>ניהול פיקסלים / אנליטיקה</h1>
        <div style="margin-top: 20px;">
            <input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop('', <?=$siteID?>)">
        </div>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th style="width:auto">שם הפיקסל</th>
                <th style="width:40px">מוצג</th>
                <th style="width:40px">&nbsp;</th>
            </tr>
            </thead>
            <tbody id="sortRow">
<?php
    $c = 0;
    foreach($pixels as $hash => $pixel){
?>
                <tr>
                    <td><?=(++$c)?></td>
                    <td onclick="openPop('<?=$hash?>', <?=$siteID?>)"><?=$pixel['title']?></td>
                    <td style="width:60px"><?=($pixel['active'] ? "<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
                    <td style="width:60px"><div onclick="if(confirm('האם את/ה בטוח/ה שרוצה למחוק את הפיקסל?')){location.href='?pdel=<?=$hash?>&siteID=<?=$siteID?>&tab=21';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
                </tr>
<?php
    }
?>
            </tbody>
        </table>
    </div>
</div>
<input type="hidden" id="orderResult" name="orderResult" value="">
<script type="text/javascript">

function openPop(pageID,siteID){
    $(".popRoomContent").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/minisites/pixels/frame.php?id='+pageID+'&siteID='+siteID+'&tab=21"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
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
include_once "../../../bin/footer.php";
