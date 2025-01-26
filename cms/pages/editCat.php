<?php
include_once "../bin/system.php";
include_once "../bin/top.php";


$pageType = intval($_GET['type']);
$catType = intval($_GET['catType']);

if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	udb::query("DELETE FROM `MainPages` WHERE MainPageID=".$mainpageid."");
}


$que="SELECT * FROM `MainPages` WHERE MainPageType=".$pageType." ORDER BY ShowOrder,MainPageID DESC";
$pages= udb::full_list($que);


?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1><?=$_GET['name']?></h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0, <?=$pageType?> , <?=$catType?>)">
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>כותרת</th>
			<th>מוצג/לא מוצג</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php 
			if($pages){
			foreach($pages as $page){ ?>
            <tr id="<?=$page['MainPageID']?>">
                <td><?=$page['MainPageID']?></td>
                <td onclick="openPop(<?=$page['MainPageID']?>,<?=$page['MainPageType']?> , <?=$catType?>)"><?=outDb($page['MainPageTitle'])?></td>
                <td><?=($page['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td align="center" class="actb">
				<?php if($pageType!=111) { ?>
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?type=<?=$pageType?>&delPage=<?=$page['MainPageID']?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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

function openPop(pageID, typeID , catType){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/pages/editCatPage.php?pageID='+pageID+'&type='+typeID+'&catType='+ catType +'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
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
		data: {ids:ids, type:pageType},
		async: false,
		success: function (myData) {
			window.location.reload();
		}
	});
}
</script>
<?php


include_once "../bin/footer.php";
?>