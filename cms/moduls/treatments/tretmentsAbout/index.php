<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";




$que="SELECT * FROM `treatments` WHERE specialPage=1";
$pages= udb::full_list($que);


?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>אודות</h1>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>אודות</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php 
			if($pages){
			foreach($pages as $page){ ?>
            <tr id="<?=$page['treatmentID']?>">
                <td></td>
                <td onclick="openPop(<?=$page['treatmentID']?>)"><?=outDb($page['treatmentName'])?></td>		
            </tr>
			<?php }
			} ?>
        </tbody>
    </table>
</div>

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
<?php

include_once "../../../bin/footer.php";
?>