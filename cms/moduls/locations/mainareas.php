<?php
include_once "../../bin/system.php";
include_once "../../bin/top.php";

$pageType = intval($_POST['type']);

if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	udb::query("DELETE FROM `main_areas` WHERE main_areaID=".$mainpageid."");
	udb::query("DELETE FROM `main_areas_text` WHERE main_areaID=".$mainpageid."");
}


$que="SELECT * FROM `main_areas` WHERE 1";
$pages= udb::full_list($que);

$que="SELECT * FROM `main_areas_text` WHERE LangID=2";
$pagesEn = udb::key_row($que,"main_areaID");

?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>איזורים ראשיים</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0)">
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>איזור</th>
			<th>in English</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php 
			if($pages){
			foreach($pages as $page){ ?>
            <tr id="<?=$page['main_areaID']?>">
                <td><?=$page['main_areaID']?></td>
                <td onclick="openPop(<?=$page['main_areaID']?>)"><?=outDb($page['TITLE'])?></td>
				<td onclick="openPop(<?=$page['main_areaID']?>)"><?=outDb($pagesEn[$page['main_areaID']]['TITLE'])?></td>
				<td align="center" class="actb">
				<?php if($pageType!=111) { ?>
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?delPage=<?=$page['main_areaID']?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
				<?php } ?>
				</td>
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
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="mainareasFrame.php?pageID='+pageID+'&pageType='+pageType+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}



</script>
<?php

include_once "../../bin/footer.php";
?>