<?php
include_once "../bin/system.php";
include_once "../bin/top.php";


$pageType = intval($_GET['type']);

if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);


	$que="SELECT GalleryID FROM `galleries` WHERE pageID=".$mainpageid."";
	$galleries= udb::full_list($que);
	if($galleries){
		foreach($galleries as $gal){
			$que="SELECT src, id  FROM `files` WHERE ref=".$gal['GalleryID']." AND `table`='site'";
			$images= udb::full_list($que);
			if($images){
				foreach($images as $image){
					$filename = "../../gallery/".$image['src'];
					if (file_exists($filename)) {
						unlink($filename);
						
					} else {
						echo 'Could not delete '.$filename.', file does not exist';
						exit;
					}
					udb::query("DELETE FROM files WHERE id=".$image['id']." ");
				}
			}
			udb::query("DELETE FROM galleries WHERE GalleryID=".$gal['GalleryID']." AND pageID=".$mainpageid." ");
		}
	}

	udb::query("DELETE FROM `alias` WHERE ref=".$mainpageid." AND `table`='MainPages' ");
	udb::query("DELETE FROM `MainPages` WHERE MainPageID=".$mainpageid."");
}


$que="SELECT * FROM `MainPages` WHERE MainPageType=".$pageType." ORDER BY ShowOrder";
$pages= udb::full_list($que);

$pagesTitles=Array();
$pagesTitles[1]="ניהול דפים";


$attrTypes=Array();
$attrTypes[1]="אטרקציות כלליות";
$attrTypes[2]="מסעדות";
$attrTypes[3]="מסלולי טיולים";
?>
<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1><?=$pagesTitles[$pageType]?></h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0, <?=$pageType?>)">
		<?php if($pages){ ?>
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
            <tr id="<?=$page['MainPageID']?>">
                <td><?=$page['MainPageID']?></td>
                <td onclick="openPop(<?=$page['MainPageID']?>,<?=$page['MainPageType']?>)"><?=outDb($page['MainPageTitle'])?></td>
				<?php if($pageType==6){?><td><?=$attrTypes[$page['attrType']]?></td><?php } ?>
				<?php if($pageType==7){?><td><?=($page['showInHome']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td><?php } ?>
                <td><?=($page['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td align="center" class="actb">
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?type=<?=$pageType?>&delPage=<?=$page['MainPageID']?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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
function openPop(pageID, typeID){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/pages/editPage.php?pageID='+pageID+'&type='+typeID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
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