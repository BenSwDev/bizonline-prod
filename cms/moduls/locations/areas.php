<?php
include_once "../../bin/system.php";
include_once "../../bin/top.php";

$pageType = intval($_POST['type']);

if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
	udb::query("DELETE FROM `areas` WHERE areaID=".$mainpageid."");
	udb::query("DELETE FROM `areas_text` WHERE areaID=".$mainpageid."");
}


$que="SELECT `areas`.* , COUNT(`sites`.`siteID`) AS sitesCNT FROM `areas` LEFT JOIN settlements USING(`areaID`) LEFT JOIN sites ON (settlements.settlementID = sites.settlementID AND sites.active =1) WHERE 1  GROUP BY `areaID` ORDER BY activeAutoSuggest DESC, sitesCNT DESC, `areas`.TITLE";
$pages= udb::full_list($que);


$que="SELECT `main_areaID`,`TITLE` FROM `main_areas` WHERE 1 ORDER BY `main_areaID`";
$main_areas= udb::key_row($que, "main_areaID" );


$que = "select * from areas_text where LangID=2";
$pagesEn = udb::key_row($que, "areaID" );
?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>איזורים</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0)">
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>איזור</th>
			<th>in English</th>
            <th>איזור ראשי</th>
            <th>מתחמים</th>
			<th>הצעה אוטומטית</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php 
			if($pages){
			foreach($pages as $page){ ?>
            <tr id="<?=$page['areaID']?>">
                <td><?=$page['areaID']?></td>
                <td onclick="openPop(<?=$page['areaID']?>)"><?=outDb($page['TITLE'])?></td>
				<td onclick="openPop(<?=$page['areaID']?>)"><?=outDb($pagesEn[$page['areaID']]['TITLE'])?></td>
                <td onclick="openPop(<?=$page['areaID']?>)"><?=outDb($main_areas[$page['main_areaID']]['TITLE'])?></td>
				<td onclick="openPop(<?=$page['areaID']?>)"><?=outDb($page['sitesCNT'])?></td>				

				<td  style="cursor:pointer;background: rgba(0,0,0,0.05) " data-status='<?=$page['activeAutoSuggest']?>' data-areaid="<?=$page["areaID"]?>" onclick="changeAS($(this))"><?=$page['activeAutoSuggest']? "כן" : ""?></td>
				<td align="center" class="actb">
				<?php if($pageType!=111) { ?>
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?delPage=<?=$page['areaID']?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="areasFrame.php?pageID='+pageID+'&pageType='+pageType+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}

function changeAS(elem){
	$.post("ajax_autoSug.php",{id:elem.data("areaid"), status:elem.data("status")},function(res){
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