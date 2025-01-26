<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";

$pageType = intval($_GET['type']);
/*
if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	udb::query("DELETE FROM `MainPages` WHERE MainPageID=".$mainpageid."");
	udb::query("DELETE FROM `MainPages_text` WHERE MainPageID=".$mainpageid."");
	udb::query("DELETE FROM `alias_text` WHERE ref=".$mainpageid." AND `table`=`MainPages`");
}
*/

$que="SELECT * FROM `domains` WHERE 1";
$pages= udb::full_list($que);


?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>דומיינים</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0)">
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>שם הדומיין</th>
            <th>חיפוש</th>
            <th>דומיין</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php
			if($pages){
			foreach($pages as $page){ ?>
            <tr id="<?=$page['domainID']?>">
                <td><?=$page['domainID']?></td>
                <td onclick="openPop(<?=$page['domainID']?>)"><?=outDb($page['domainName'])?></td>
                <td><?=$page['searchLevel2']."/" . $page['searchNumberExt']?></td>
				<td><A href="https://<?=$page['domainURL']?>" target="_blank"><?=$page['domainURL']?></A></td>
            </tr>
			<?php }
			} ?>
        </tbody>
    </table>
</div>
<input type="hidden" id="orderResult" name="orderResult" value="">

<script>
var pageType="<?=$pageType?>";
function openPop(pageID){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="frame.php?pageID='+pageID+'&pageType='+pageType+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}


</script>
<?php

include_once "../../../bin/footer.php";
?>