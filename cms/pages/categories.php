<?php
include_once "../bin/system.php";
include_once "../bin/top.php";

($_GET['type']!=""?$type = $_GET['type']:$type=0);

if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	udb::query("DELETE FROM `category` WHERE ID=".$mainpageid."");
	udb::query("DELETE FROM `alias` WHERE ref=".$mainpageid." AND `table`='category' ");

}


$que="SELECT * FROM `category` WHERE `type`=".$type;
$pages= udb::full_list($que);
$pagesTitles=$_GET['name'];

?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1><?=$pagesTitles?></h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0,<?=$type?>)">
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>כותרת</th>
			<th>תמונה</th>
			<th>מוצג/לא מוצג</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php 
			if($pages){
			foreach($pages as $page){ ?>
            <tr id="<?=$page['ID']?>">
                <td><?=$page['ID']?></td>
                <td onclick="openPop(<?=$page['ID'].','.$type?>)"><?=outDb($page['facName'])?></td>
                <td><img src="/gallery/<?=$page['picture']?>" alt=""></td>
                <td><?=($page['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td align="center" class="actb">
				<?php if($pageType!=111) { ?>
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?type=<?=$type?>&delPage=<?=$page['ID']?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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
function openPop(pageID,type){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/pages/editCategories.php?pageID='+pageID+'&type='+type+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
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
		data: {ids:ids},
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
<style type="text/css">
	tr td img{max-width:100px}
</style>