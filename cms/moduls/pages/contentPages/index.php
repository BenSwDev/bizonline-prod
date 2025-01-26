<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";
include_once "../../../_globalFunction.php";
$pageType = intval($_GET['type']);
$domainID = intval($_GET['domainID']) ?:1;
if(intval($_GET['delPage'])!=''){
	if($pageType == 170) {
	$mainpageid=intval($_GET['delPage']);
		udb::query("DELETE FROM `MainPages` WHERE MainPageID=".$mainpageid."");
		udb::query("DELETE FROM `MainPages_text` WHERE MainPageID=".$mainpageid."");
		udb::query("DELETE FROM `alias_text` WHERE ref=".$mainpageid."");
	}
}


$que="SELECT MainPages.*, MIN(MainPages_text.domainID) AS `domainID` FROM `MainPages` LEFT JOIN `MainPages_text` USING(MainPageID) WHERE MainPages.MainPageType=".$pageType." and MainPages_text.domainID=".$domainID." GROUP BY MainPages.MainPageID ORDER BY `showOrder`";
$pages= udb::full_list($que);

$domList = DomainList::get();

?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>דפים</h1>
    <?php domainTabs($domainID);?>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0,<?=$domainID?>)">
		<?php if($pages && $pageType!=1){ ?>
		<input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה">
		<?php } ?>
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>כותרת</th>
            <th>דומיין</th>
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
                <td onclick="openPop(<?=$page['mainPageID']?>,<?=$page['domainID']?>)"><?=outDb($page['mainPageTitle'])?></td>
				<td><?=$domList[$page['domainID']]['domainName']?></td>
                <td><?=($page['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td align="center" class="actb">
				<?php if($pageType!=1) { ?>
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?type=<?=$pageType?>&delPage=<?=$page['mainPageID']?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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
function openPop(pageID,DomainID){
    var addDid = "";
    if(DomainID) addDid = "&domainID=" + DomainID;
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="frame.php?pageID='+pageID+'&pageType='+pageType+addDid+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
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
$(".miniTabs .tab").on("click",function(){
    var did = $(this).data("id");
    location.href = "?type=<?=$_GET['type']?>&domainID=" + did;
});
</script>
<?php

include_once "../../../bin/footer.php";
?>