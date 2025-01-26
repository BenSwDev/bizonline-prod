<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";


if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	udb::query("DELETE FROM holidays WHERE holidayID=".$mainpageid."");
	udb::query("DELETE FROM holidays_text WHERE holidayID=".$mainpageid."");
}


$que="SELECT * FROM `holidays` WHERE 1 ORDER BY `dateStart` DESC";
$pages= udb::full_list($que);


?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>תקופות חמות</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0)">
	</div>
	<div style="color:#cc0000;margin-top:5px;font-size:16px"><b>* יש לוודא שאין תקופות חמות חופפות בתאריכים - מסומנים באדום</b></div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>שם התקופה</th>
            <th>תאריכים</th>
            <th>שנתי</th>
            <th>פעיל</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php 
			if($pages){
			foreach($pages as $page){ ?>
            <tr id="<?=$page['holidayID']?>" <?=(isset($lastStartDate) && strtotime($lastStartDate) <= strtotime($page['dateEnd']))? "class='double'" : ""?>>
                <td><?=$page['holidayID']?></td>
                <td onclick="openPop(<?=$page['holidayID']?>)"><?=outDb($page['holidayName'])?></td>
                <td onclick="openPop(<?=$page['holidayID']?>)">
				<?=date("d/m/Y", strtotime($page['dateEnd']))." - ".date("d/m/Y", strtotime($page['dateStart']))?>
				</td>
                <td onclick="openPop(<?=$page['annual']?>)"><?=($page['annual']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
                <td onclick="openPop(<?=$page['holidayID']?>)"><?=($page['active']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td align="center" class="actb">
				<?php if($pageType!=111) { ?>
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?delPage=<?=$page['holidayID']?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
				<?php 
			$lastStartDate = $page['dateStart'];	
			} ?>
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





</script>
<style>
	.manageItems table > tbody > tr.double{background:#ffcccc;position:relative}
	.manageItems table > tbody > tr.double td:first-child{position:relative;border-right:2px red solid}
	.manageItems table > tbody > tr.double td:first-child::before{content:"";position:absolute;top:-60px;height:60px;width:2px;background:red;right:-2px}
	
</style>
<?php

include_once "../../../bin/footer.php";
?>