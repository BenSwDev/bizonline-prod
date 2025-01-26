<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";

$where ="1 = 1 ";

if($_GET['free']){
    $where .= "AND (`therapists`.`siteName` LIKE '%".trim(filter_var($_GET['free'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW))."%' /*OR `therapists`.`siteID`=".trim(filter_var($_GET['free'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW))."*/)";

}

if($_GET['active']!=""){
    $where .= "AND `therapist`.`active`=".intval($_GET['active']);
}

$therapists = udb::full_list("SELECT `therapists`.`siteName`, `therapists`.`active`, `therapists`.`siteID`, `therapists`.`phone`, `therapists`.`email`,  `therapists`.`siteID` 
FROM `therapists` 
WHERE " . $where . "  ORDER BY `therapists`.`active` DESC, `therapists`.`siteName` ASC");

//print_r($therapists);
/*
ini_set('display_errors', 1);
error_reporting(-1 ^ E_NOTICE);
*/
?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>ניהול מטפלים</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0, 0)">
	</div>
	<div class="searchCms">
		<form method="GET">
			<input type="text" name="free" placeholder="מלל חופשי" value="<?=htmlspecialchars($_GET['free'], ENT_QUOTES | ENT_XHTML, 'UTF-8', false)?>">
			<select name="active">
				<option value="">פעיל/לא פעיל</option>
				<option value="1" <?=($_GET['active']==1?"selected":"")?> >פעיל</option>
				<option value="0" <?=(isset($_GET['active']) && $_GET['active']=="0" ?"selected":"")?>>לא פעיל</option>
			</select>

			<a href="/cms/moduls/minitherapists/table.php">נקה</a>
			<input type="submit" value="חפש">
		</form>
	</div>
	<div class="tblMobile">
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>שם המטפל</th>
			<th>טלפון</th>
            <th>דוא"ל</th>
			<th width="40">מוצג</th>
			<th></th>
			
        </tr>
        </thead>
        <tbody id="sortRow">
<?php
if (count($therapists)){
    foreach($therapists as $site){
        ?>
        <tr id="<?=$site['siteID']?>">
                <td><?=$site['siteID']?></td>
                <td onclick="openPop(<?=$site['siteID']?>,'<?=addslashes(htmlspecialchars($site['siteName']))?>')"><?=outDb($site['siteName'])?></td>
                <td onclick="openPop(<?=$site['siteID']?>,'<?=addslashes(htmlspecialchars($site['siteName']))?>')"><?=outDb($site['phone'])?></td>
                <td onclick="openPop(<?=$site['siteID']?>,'<?=addslashes(htmlspecialchars($site['siteName']))?>')"><?=outDb($site['email'])?></td>

                <td><?=($site['active']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td><div onclick="if(confirm('האם אתה בטוח רוצה למחוק את המתחם?')){delsite(<?=$site['siteID']?>)}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>

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
<script>
var pageType="<?=$pageType?>";
function openPop(pageID, siteName){
    $(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/treatments/treaters/frame.dor.php?siteID='+pageID+'&siteName='+encodeURIComponent(siteName)+'&tab=1"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
    $(".pagePop").show();
}
function closeTab(){
    $(".pagePopCont").html('');
    $(".pagePop").hide();
}


function delsite(siteID){
    $.post('delSite.php',{'siteID':siteID},function(){
        window.location.reload();
    });

}
</script>
<?php



include_once "../../../bin/footer.php";
?>
