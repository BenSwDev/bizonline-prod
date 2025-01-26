<?php
include_once "../../bin/system.php";
include_once "../../bin/top.php";
include_once "../../_globalFunction.php";
//$pageType = intval($_POST['type']);

$langID = intval($_GET['langID']) ? intval($_GET['langID']) : 1;
$domainID = intval($_GET['domainID']);
$domainID = ($domainID == 1 ? 6 : $domainID);
if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	udb::query("DELETE FROM `articles` WHERE nid=".$mainpageid."");
	udb::query("DELETE FROM `alias_text` WHERE ref=".$mainpageid." AND `table`='articles'");
}





$que="SELECT LangID, LangName FROM language WHERE 1";
$languages = udb::full_list($que);

$domainsList = udb::full_list(DomainList::getLoadQuery());

unset($domainsList[0]);

$que="SELECT * FROM `articles` WHERE langID=".$langID." AND domainID=".$domainID." ORDER BY `showOrder`";
$pages= udb::full_list($que);
?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>מאמרים</h1>

	<div class="miniTabs general" style="display: block;">
	<?php foreach($domainsList as $domain) { ?>
		<div class="tab <?=$domainID==$domain['domainID']?'active':''?>  " <?=$domainID==$domain['domainID']?'active':''?>  onclick="window.location.href='?domainID=<?=$domain['domainID']?>'"><p><?=$domain['domainName']?></p></div>
	<?php } ?>
	</div>
	<div class="miniTabs general">
		<?php foreach($languages as $lang){ ?>
			<div class="tab<?=$lang['LangID']==$langID?" active":""?>" onclick="window.location.href='/cms/moduls/bubbles/index.php?langID=<?=$lang['LangID']?>&domainID=<?=$domainID?>'"><p><?=$lang['LangName']?></p></div>
		<?php } ?>
	</div>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0)">
		<?php if($pages){ ?>
		<input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="סדר תצוגה דף ראשי">
		<input type="button" class="addNew" id="innerbuttonOrder" onclick="innerOrder(this)" value="סידור כתבות לפי קטגוריה בשם">
		<?php } ?>
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>כותרת</th>
            <th>מוצג / לא מוצג</th>
			<th>מקודם</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php
			if($pages){
			foreach($pages as $page){ ?>
            <tr class="allLine" id="<?=$page['nid']?>" value="<?=$page['innerShowOrder']?>" data-section="<?=$page['articleSubjectOldID']?>">

                <td><?=$page['nid']?></td>
                <td onclick="openPop(<?=$page['nid']?>)"><?=outDb($page['articleTitle'])?></td>
				<td><?=($page['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td><?=($page['promoteArt']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td align="center" class="actb">
				<?php if($pageType!=111) { ?>
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?delPage=<?=$page['nid']?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
				<?php } ?>
				</td>
            </tr>
			<?php }
			} ?>
        </tbody>
    </table>
</div>
<input type="hidden" id="orderResult" name="orderResult" value="">
<input type="hidden" id="orderResultInner" name="orderResultInner" value="">

<script>

function openPop(pageID){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="frame.php?pageID='+pageID+'&langID=<?=$langID?>&domainID=<?=$domainID?>"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
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
		data: {ids:ids, table:"articles"},
		async: false,
		success: function (myData) {
			window.location.reload();
		}
	});
}

function innerOrder(is){
	$(".allLine").sort(sortLine).appendTo('#sortRow');
	$("#addNewAccInner").hide();
	$(is).val("שמור סדר תצוגה");
	$(is).attr("onclick", "saveOrderInner()");
	$("#sortRow tr").attr("onclick", "");
	$("#sortRow").sortable({

		stop: function(){
			$("#orderResultInner").val($("#sortRow").sortable('toArray'));
		}
	});
	$("#orderResultInner").val($("#sortRow").sortable('toArray'));

}

function saveOrderInner(){

	var ids = $("#orderResultInner").val();
	$.ajax({
		url: 'js_order_innerpages.php',
		type: 'POST',
		data: {ids:ids, table:"articles"},
		async: false,
		success: function (myData) {
			window.location.reload();
		}
	});


}
function sortLine(a, b){
	return ($(b).data("section")) < ($(a).data("section")) ? 1 : -1;
}
</script>
<?php

include_once "../../bin/footer.php";
?>