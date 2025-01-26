<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";

$pageType = intval($_GET['type']);

if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	$exist = udb::single_value("SELECT MainPageID FROM MainPages WHERE MainPageID=".$mainpageid);
	if($exist) {
		$que = udb::single_value("SELECT MainPageType FROM MainPages WHERE MainPageID=".$mainpageid);
		if($que == 165) {
			udb::query("DELETE FROM `MainPages` WHERE MainPageID=".$mainpageid."");
			udb::query("DELETE FROM `MainPages_text` WHERE MainPageID=".$mainpageid."");
			udb::query("DELETE FROM `alias_text` WHERE ref=".$mainpageid."");
		}
	}
}

$que="SELECT * FROM `MainPages` WHERE MainPageType=".$pageType." ORDER BY `showOrder`";
$pages= udb::full_list($que);

$PageTitle = "דפים סטטים";
if($pageType == 103) {
	$PageTitle = "אמנת שירות";
}
?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1><?=$PageTitle?></h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0)">
		<?php if($pages && $pageType!=100){ ?>
		<input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה">
		<?php } ?>
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>כותרת</th>
            <th>מוצג / לא מוצג</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php 
			if($pages){
			foreach($pages as $page){ ?>
            <tr id="<?=$page['mainPageID']?>">
                <td><?=$page['mainPageID']?></td>
                <td onclick="openPop(<?=$page['mainPageID']?>)"><?=outDb($page['mainPageTitle'])?></td>
				<td><?=($page['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td align="center" class="actb">
				<?php if($pageType!=100) { ?>
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?delPage=<?=$page['mainPageID']?>&type=<?=$pageType?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="frame.php?pageID='+pageID+'&pageType='+pageType+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
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
		data: {ids:ids, table:"MainPages"},
		async: false,
		success: function (myData) {
			window.location.reload();
		}
	});
}

</script>
<?php

include_once "../../../bin/footer.php";
?>