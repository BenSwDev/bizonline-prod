<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";



$reviewID=intval($_GET['reviewID']);
$siteID=intval($_GET['siteID']);
$frameID=intval($_GET['frame']);
$siteName = $_GET['siteName'];




if (intval($_GET['delPage']))
{

	$cdel = intval($_GET['delPage']);
	$adData  = udb::single_row("SELECT * FROM `sites_article_ads` WHERE `adID` = " . $cdel);
	
	if($adData['adPic'])
		unlink('../../../../gallery/'.$adData['adPic']);

	$que = "DELETE FROM `sites_article_ads` WHERE `adID` = ".$cdel;
	udb::query($que);
	$que = "DELETE FROM `sites_article_ads_langs` WHERE `adID` = ".$cdel;
	udb::query($que);
}




$que = "SELECT * FROM `sites_article_ads` WHERE `siteID` = ".$siteID;
$ads= udb::full_list($que);


?>



<div class="popRoom"><div class="popRoomContent"></div></div>
<div class="editItems">
	<div class="siteMainTitle"><?=$siteName?></div>
		<?=showTopTabs(0)?>
		<div class="manageItems">
			<div class="addButton" style="margin-top:10px">
				<input type="button" class="addNew" value="הוסף חדש" onclick="openPop(0,<?=$siteID?>)">
			</div>
			<table border=0 style="border-collapse:collapse" align="center" cellpadding=5 cellspacing=1>
				<tr>
					<th>#</th>
					<th>כותרת</th>
					<th>תאריך הוספה</th>
					<th>מחק</th>
				</tr>
				<tbody>
			<?php if($ads){
			$i = 1;
			foreach($ads as $tID => $row) {  ?>
				<tr id="com_<?=$row['adID']?>">
					<td align="center" onclick="openPop(<?=$row['adID']?>,<?=$siteID?>)"><?=($i++)?></td>
					<td align="center" onclick="openPop(<?=$row['adID']?>,<?=$siteID?>)"><?=$row['adTItle']?></td>
					<td align="center" onclick="openPop(<?=$row['adID']?>,<?=$siteID?>)"><?=date("d.m.Y", strtotime($row['adCreate']))?></td>
					<td align="center" onclick="if(confirm('בטוח רוצה למחוק?')){window.location.href='/cms/moduls/minisites/articleAds/index.php?tab=9&siteName=<?=addslashes(htmlspecialchars($siteName))?>&siteID=<?=$siteID?>&delPage=<?=$row['adID']?>' } ">מחק</td>

				</tr>
			<?php }
			} ?>
				</tbody>
			</table>
		</div>

	
</div>


<style>
	.manageItems table > tbody > tr > th:nth-child(1){width:40px;}
	.manageItems table > tbody > tr > th:nth-child(2){width:130px;}
	.manageItems table > tbody > tr > th:nth-child(3){width:130px;}
	.manageItems table > tbody > tr > th:nth-child(4){width:150px;}
	.manageItems table > tbody > tr > th:nth-child(5){width:130px;}
	.manageItems table > tbody > tr > th:nth-child(6){width:50px;}
	.manageItems table > tbody > tr > th:nth-child(7){width:60px;}

</style>



<script type="text/javascript">

	function openPop(pageID,siteID){
		$(".popRoomContent").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/minisites/articleAds/frame.php?pageID='+pageID+'&siteID='+siteID+'&tab=1"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
		$(".popRoom").show();
		window.parent.parent.$('.tabCloser').hide();
	}
	function closeTab(){
		$(".popRoomContent").html('');
		$(".popRoom").hide();
		window.parent.parent.$('.tabCloser').show();
	}



</script>