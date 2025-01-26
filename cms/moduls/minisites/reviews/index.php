<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";


$que="SELECT * FROM `domains` WHERE 1";
$domains= udb::key_row($que,'domainID');



$reviewID=intval($_GET['reviewID']);
$siteID=intval($_GET['siteID']);
$frameID=intval($_GET['frame']);
$siteName = addslashes(htmlspecialchars($_GET['siteName']));
$domainID = intval($_GET['domainID']);

$que ="SELECT publishReviews FROM sites WHERE siteID = ".$siteID;
$publishReviews =  udb::single_value($que);



if (intval($_GET['delPage']))
{
	$cdel = intval($_GET['delPage']);

	$que = "DELETE FROM `reviews` WHERE `reviewID` = ".$cdel;
	udb::query($que);
	$que = "DELETE FROM `reviewScore` WHERE `reviewID` = ".$cdel;
	udb::query($que);
	$que = "DELETE FROM `reviewsDomains` WHERE `reviewID` = ".$cdel;
	udb::query($que);

	$reviewPic = udb::full_list("SELECT * FROM `files` WHERE `table`= 'reviews' AND `ref` =".$reviewID);
	if($reviewPic){
		foreach($reviewPic as $pic){
			if(strpos($pic, 'http') !== true){
				unlink('../../../gallery/'.$pic['src']);
			}
		}
	}


	$que = "DELETE FROM `files` WHERE `table`='reviews' AND `ref` = ".$cdel;
	udb::query($que);
	$que = "DELETE FROM `alias_text` WHERE `table`='reviews' AND `ref` = ".$cdel;
	udb::query($que);
    //udb::query("insert into (reviewID,siteID) VALUES(".$cdel.",".$siteID.")");
	udb::query("OPTIMIZE TABLE `reviews`");
	udb::query("OPTIMIZE TABLE `reviewScore`");
	udb::query("OPTIMIZE TABLE `files`");
}

$que = "SELECT *  FROM reviewsDomains  WHERE reviewID = 11365 LIMIT 20";
//$que = "SELECT * FROM `reviews`  WHERE `siteID` = ".$siteID."  ORDER BY `selected` DESC ,`day` DESC";
$aaa= udb::full_list($que);
//print_r($aaa);

$que = "SELECT `reviews`.*, reviewsDomains.domainID AS showInDomain  FROM `reviews`  
		LEFT JOIN reviewsDomains USING (reviewID) WHERE `siteID` = ".$siteID." AND reviewsDomains.domainID > 0 GROUP BY `reviews`.reviewID  ORDER BY `selected` DESC ,`day` DESC";
//$que = "SELECT * FROM `reviews`  WHERE `siteID` = ".$siteID."  ORDER BY `selected` DESC ,`day` DESC";
$comments= udb::full_list($que);


?>



<div class="popRoom"><div class="popRoomContent"></div></div>
<div class="editItems">
	<div class="siteMainTitle"><?=$siteName?></div>
    <?php minisite_domainTabs($domainID,"2")?>
	<?=showTopTabs("2")?>
	<div class="miniTabs hideDomainMiniTabs">
		<?php foreach($domains as $key=>$mlist){ ?>
			<div class="tab<?=$key==$domainID?" active":""?>" onclick="window.location.href='/cms/moduls/minisites/reviews/index.php?siteID=<?=$siteID?>&domainID=<?=$key?>&tab=6&siteName=<?=$siteName?>'"><p><?=$mlist['domainName']?></p></div>
		<?php } ?>
	</div>
		<div class="manageItems">
			<div class="addButton" style="margin-top:10px">
				<input type="button" class="addNew" value="הוסף חדש" onclick="openPop(0,<?=$siteID?>,<?=$domainID?>)">
			</div>
			<table border=0 style="border-collapse:collapse" align="center" cellpadding=5 cellspacing=1>
				<tr>
					<th>#</th>
					<th>שם הכותב</th>
					<th>כותרת</th>
					<th>דעה</th>
					<th>תאריך אירוח</th>
					<th>מקור</th>
					<th>מוצג</th>
					<th>דומיין לתצוגה</th>
					<?/*<th>מחק</th>*/?>
				</tr>
				<tbody>
			<?php if($comments){
			$que="SELECT * FROM `domains` WHERE domainID != 1 and domainMenu=1";
			$domains= udb::key_row($que,'domainID');
			$i = 1;
			foreach($comments as $tID => $row) {  ?>
				<tr id="com_<?=$row['reviewID']?>" <?=($row['selected']?'style="border: 3px solid #3fb220;"':'')?>>
					<td align="center" onclick="openPop(<?=$row['reviewID']?>,<?=$siteID?>,<?=$row['domainID'] ?? 1?>)"><?=($i++)?></td>
					<td align="center" onclick="openPop(<?=$row['reviewID']?>,<?=$siteID?>,<?=$row['domainID'] ?? 1?>)"><?=$row['name']?></td>
					<td align="center" onclick="openPop(<?=$row['reviewID']?>,<?=$siteID?>,<?=$row['domainID'] ?? 1?>)"><?=$row['title']?></td>
					<td align="center" onclick="openPop(<?=$row['reviewID']?>,<?=$siteID?>,<?=$row['domainID'] ?? 1?>)" style="font-size:13px;text-align:right;"><?=mb_substr($row['text'],0,100)?></td>
					<td align="center" onclick="openPop(<?=$row['reviewID']?>,<?=$siteID?>,<?=$row['domainID'] ?? 1?>)"><?=date("d.m.Y", strtotime($row['day']))?></td>
					<td><?//=$row['showInDomain']?><?
					if($row['orderID']) {
						echo 'Biz order';
						$fromUSER = 1;
					}
					else {
						if($row['domainID'] == 0) {
							echo 'Biz cms';
						}
						else {
							if($row['domainID'] != 1){
								echo $domains[$row['domainID']]['domainName'];
							}
							else {
								echo 'Biz link';
								$fromUSER = 1;
							}

						}
					}

					//$row['domainID'] ? $domains[$row['domainID']]['domainName'] : 'לא מוגדר'
					?></td>
					<td align="center" id="commentStatus_<?=$row['reviewID']?>"><?=($row['ifShow'] ? '<span style="color:green">כן</span>' : '<span style="color:red">לא</span>')?><?=($fromUSER && !$publishReviews)? "<b style='color:red;display:inline'>*</b>" : ""?></td>
					
					<td>
						<?/*
						<select onchange="set_showInDomain($(this).val(),<?=$row['reviewID']?>)">
							<option value="0">לא נבחר</option>
							<?foreach($domains as $key => $domain){?>
								<option value="<?=$key?>" <?=$row['showInDomain'] == $key? "selected" : ""?>><?=$domain['domainName']?></option>
							<?}?>
							*/?><?=$row['showInDomain']? $domains[$row['showInDomain']]['domainName'] : "לא מוגדר"?>
					</td>
					<?/*
					<td align="center" onclick="if(confirm('בטוח רוצה למחוק?')){window.location.href='/cms/moduls/minisites/reviews/index.php?tab=6&siteName=<?=addslashes(htmlspecialchars($siteName))?>&siteID=<?=$siteID?>&delPage=<?=$row['reviewID']?>' } ">מחק</td>
					 */?>
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

	function openPop(pageID,siteID,domainID){
		$(".popRoomContent").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/minisites/reviews/frame.php?pageID='+pageID+'&siteID='+siteID+'&tab=1&domainID='+domainID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
		$(".popRoom").show();
		window.parent.parent.$('.tabCloser').hide();
	}
	function closeTab(){
		$(".popRoomContent").html('');
		$(".popRoom").hide();
		window.parent.parent.$('.tabCloser').show();
	}

	function set_showInDomain(domainID,reviewID){
		$.ajax({
			method: 'POST',
			url: '/cms/moduls/minisites/reviews/ajax_showInDomain.php',
			data: {domainID: domainID , reviewID: reviewID},
			success: function(response){
				debugger;
				console.log("update");
			}
		});
	}



</script>