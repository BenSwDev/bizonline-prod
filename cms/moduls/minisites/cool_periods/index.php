<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";


if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	udb::query("DELETE FROM not_holidays WHERE notHolidayID=".$mainpageid."");
	udb::query("DELETE FROM not_holidays_text WHERE notHolidayID=".$mainpageid."");
}


$que="SELECT * FROM `not_holidays` WHERE 1 ORDER BY `dateStart`";
$pages= udb::full_list($que);


?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>חגים ביומן</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0)">
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>שם התקופה</th>
            <th>תאריכים</th>
            <th>שנתי</th>
            <th>פעיל</th>
			<th>&nbsp;</th>
            <th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php
			if($pages){
			foreach($pages as $page){ ?>
            <tr id="<?=$page['notHolidayID']?>">
                <td><?=$page['notHolidayID']?></td>
                <td onclick="openPop(<?=$page['notHolidayID']?>)"><?=outDb($page['notHolidayName'])?></td>
                <td onclick="openPop(<?=$page['notHolidayID']?>)">
				<?=date("d/m/Y", strtotime($page['dateEnd']))." - ".date("d/m/Y", strtotime($page['dateStart']))?>
				</td>
                <td onclick="openPop(<?=$page['notHolidayID']?>)"><?=($page['annual']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
                <td onclick="openPop(<?=$page['notHolidayID']?>)"><?=($page['active']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
                <td ><div onclick="crateHotOne(<?=$page['notHolidayID']?>)">צור תקופה חמה</div></td>
                <td align="center" class="actb">
				<?php if($pageType!=111) { ?>
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?delPage=<?=$page['notHolidayID']?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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

function crateHotOne(id) {
    $.get("cloneToHot.php?id="+id,function (response) {
        try {
            var res = JSON.parse(response);
        } catch (e) {
            var res = response;
        }
        if(res.status == 'ok') {
            alert("פעולה הסתיימה בהצלחה: "  + res.message);
        }
        else {
            alert("פעולה נכשלה: "  + res.message);
        }


    });
}



</script>
<?php

include_once "../../../bin/footer.php";
?>