<?php
include_once "../../bin/system.php";
include_once "../../bin/top.php";

$pageType = intval($_POST['type']);

if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	udb::query("DELETE FROM `settlements` WHERE settlementID=".$mainpageid."");
	udb::query("DELETE FROM `settlements_text` WHERE settlementID=".$mainpageid."");
}


$que="SELECT `settlements`.* , COUNT(`sites`.`siteID`) AS sitesCNT FROM `settlements` LEFT JOIN sites ON (settlements.settlementID = sites.settlementID AND sites.active =1) WHERE 1  GROUP BY `settlementID` ORDER BY showAutoSug DESC, sitesCNT DESC, `settlements`.TITLE";
$pages= udb::full_list($que);


$que="SELECT `areaID`,`TITLE` FROM `areas` WHERE 1 ORDER BY `main_areaID`";
$areas= udb::key_row($que, "areaID");

$que = "select * from settlements_text where LangID=2";
$pagesEn = udb::key_row($que, "settlementID" );
?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>יישובים</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0)">
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>יישוב</th>
			<th>in English</th>
            <th>איזור</th>
            <th>מתחמים</th>
			<th>הצעה אוטומטית</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php 
			if($pages){
			foreach($pages as $page){ ?>
            <tr id="<?=$page['settlementID']?>">
                <td><?=$page['settlementID']?></td>
                <td onclick="openPop(<?=$page['settlementID']?>)"><?=outDb($page['TITLE'])?></td>
				<td onclick="openPop(<?=$page['settlementID']?>)"><?=outDb($pagesEn[$page['settlementID']]['TITLE'])?></td>
                <td onclick="openPop(<?=$page['settlementID']?>)"><?=outDb($areas[$page['areaID']]['TITLE'])?></td>
                <td onclick="openPop(<?=$page['settlementID']?>)"><?=outDb($page['sitesCNT'])?></td>				
				<td style="cursor:pointer;background: rgba(0,0,0,0.05) " data-status='<?=$page['showAutoSug']?>' data-setid="<?=$page["settlementID"]?>" onclick="changeAS($(this))"><?=$page['showAutoSug']? "כן" : ""?></td>
				<td align="center" class="actb">
				<?php if($pageType!=111) { ?>
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?delPage=<?=$page['settlementID']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="citiesFrame.php?pageID='+pageID+'&pageType='+pageType+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}

function changeAS(elem){
		$.post("ajax_autoSug2.php",{id:elem.data("setid"), status:elem.data("status")},function(res){
			if(res==0){
				elem.data("status",0);
				elem.text("");
			}else{			
				elem.data("status",1);
				elem.text("כן");
			}
		});
		

}

</script>
<?php

include_once "../../bin/footer.php";
?>