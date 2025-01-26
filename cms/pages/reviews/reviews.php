<?php
include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";


$pageID = intval($_GET['pageID']);
$pageType = intval($_GET['type']);

if($pageType!=10) {
	echo "<br><br><h1>ניתן להכניס חוות דעת רק בדף הסוויטות</h1>";	
	exit;
	
}
if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	udb::query("DELETE FROM `reviews` WHERE `reviewID`=".$mainpageid."");
}

$menu = include "../pages_menu.php";

$que="SELECT `reviewID`,`title`,`ifShow` FROM `reviews` WHERE suiteID=".$pageID." ORDER BY day DESC";
$pages= udb::full_list($que);


?>
<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>חוות דעת</h1>
	<div class="miniTabs">
		<?php foreach($menu as $men){
			if($men['position']==$position && $men['sub']){
			$subMenu = $men['sub'];
			}
		?>
		<div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='/cms/pages/<?=$men['href']?>?pageID=<?=$pageID?>&type=<?=$pageType?>'"><p><?=$men['name']?></p></div>
		<?php  } ?>
	</div>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="window.location.href='/cms/pages/reviews/editReviews.php?pageID=<?=$pageID?>&type=<?=$pageType?>'">
		<?php if($pages=="00"){ ?>
		<input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה">
		<?php } ?>
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
            <tr id="<?=$page['reviewID']?>">
                <td><?=$page['reviewID']?></td>
                <td onclick="window.location.href='/cms/pages/reviews/editReviews.php?pageID=<?=$pageID?>&type=<?=$pageType?>&revID=<?=$page['reviewID']?>'"><?=outDb($page['title'])?></td>
                <td><?=($page['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td align="center" class="actb">
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?pageID=<?=$pageID?>&type=<?=$pageType?>&delPage=<?=$page['reviewID']?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/pages/reviews/editReviews.php?pageID='+pageID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
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


include_once "../../bin/footer.php";
?>