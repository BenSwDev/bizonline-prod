<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";


if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	udb::query("DELETE FROM treatmentsCat WHERE id=".$mainpageid."");
	udb::query("DELETE FROM treatmentsCatLang WHERE id=".$mainpageid."");
}


$que="SELECT * FROM `treatmentsCat` WHERE 1 ORDER BY `showOrder`";
$pages= udb::full_list($que);


?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>קטגוריות טיפולים</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0)">
		<?php if($pages){ ?>
		<input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה">
		<?php } ?>
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>שם קטגוריה</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php 
			if($pages){
			foreach($pages as $page){ ?>
            <tr id="<?=$page['id']?>">
                <td><?=$page['id']?></td>
                <td onclick="openPop(<?=$page['id']?>)"><?=outDb($page['categoryName'])?></td>
				<td align="center" class="actb">
				<?php if($pageType!=111) { ?>
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?delPage=<?=$page['id']?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="frame.php?pageID='+pageID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
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
		data: {ids:ids, table:"treatmentsCat"},
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