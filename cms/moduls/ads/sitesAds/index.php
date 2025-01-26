<?php
exit;
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";
//$pageType = intval($_POST['type']);

$langID = intval($_GET['langID']);
$domainID = intval($_GET['domainID']);

if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	udb::query("DELETE FROM `sites_ads` WHERE adID=".$mainpageid."");
	//udb::query("DELETE FROM `alias_text` WHERE ref=".$mainpageid." AND `table`='sites_ads'");
}


$que="SELECT * FROM `sites_ads` WHERE langID=".$langID." AND domainID=".$domainID." ORDER BY `showOrder`";
$pages= udb::full_list($que);


$que="SELECT LangID, LangName FROM language WHERE 1";
$languages = udb::full_list($que);

$domainsList = udb::full_list(DomainList::getLoadQuery());


?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>מודעות דף צימר</h1>
	<div class="miniTabs general">
	<?php foreach($domainsList as $domain) { ?>
		<div class="tab <?=$_GET['domainID']==$domain['domainID']?'active':''?>  " <?=$_GET['domainID']==$domain['domainID']?'active':''?>  onclick="window.location.href='/cms/moduls/ads/sitesAds/index.php?langID=<?=$langID?>&domainID=<?=$domain['domainID']?>'"><p><?=$domain['domainName']?></p></div>
	<?php } ?>
	</div>
	<div class="miniTabs general">
		<?php foreach($languages as $lang){ ?>
			<div class="tab<?=$lang['LangID']==$langID?" active":""?>" onclick="window.location.href='/cms/moduls/ads/sitesAds/index.php?langID=<?=$lang['LangID']?>&domainID=<?=$domainID?>'"><p><?=$lang['LangName']?></p></div>
		<?php } ?>
	</div>
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
            <th>כותרת</th>
            <th>מוצג / לא מוצג</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php 
			if($pages){
			foreach($pages as $page){ ?>
            <tr id="<?=$page['adID']?>">
                <td><?=$page['adID']?></td>
                <td onclick="openPop(<?=$page['adID']?>)"><?=outDb($page['adTitle'])?></td>
				<td><?=($page['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td align="center" class="actb">
				<?php if($pageType!=111) { ?>
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?delPage=<?=$page['adID']?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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
		data: {ids:ids, table:"sites_ads"},
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