<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";


if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	udb::query("DELETE FROM roomTypes WHERE id=".$mainpageid."");
}

$que="SELECT * FROM `roomTypes` WHERE 1";
$pages= udb::full_list($que);
$purpose[1] = "אירוח";
$purpose[2] = "אירועים";
?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>סוגי יחידות</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0)">
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>סוג היחידה</th>
            <th>רבים</th>
            <th>סוג</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php
			if($pages){
			foreach($pages as $page){ ?>
            <tr id="<?=$page['id']?>">
                <td><?=$page['id']?></td>
                <td onclick="openPop(<?=$page['id']?>)"><?=outDb($page['roomType'])?></td>
                <td onclick="openPop(<?=$page['id']?>)"><?=outDb($page['roomTypeMany'])?></td>
                <td onclick="openPop(<?=$page['id']?>)"><?=$purpose[$page['purpose']]?></td>
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
<script>
var pageType="<?=$pageType?>";
function openPop(pageID){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="roomTypesFrame.php?pageID='+pageID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}



</script>
<?php


include_once "../../../bin/footer.php";
?>