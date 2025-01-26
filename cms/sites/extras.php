<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$position=3;
$subposition=4;

$siteID=intval($_GET['sID']);
$frameID=intval($_GET['frame']);


$error = "";


$menu = include "site_menu.php";

$que="SELECT * FROM `sites` WHERE siteID=".$siteID."";
$site= udb::single_row($que);

$edit = false;
$error = base64_decode($_GET['err']);

$que="SELECT MainPages.MainPageID, MainPages.MainPageTitle, MainPages.ifShow, sitesExtrasNew.* 
	  FROM MainPages 
	  LEFT JOIN sitesExtrasNew ON (MainPages.MainPageID=sitesExtrasNew.extraID AND siteID=".$siteID.") 
	  WHERE MainPageType=20 AND MainPages.ifShow=1";
$extras=udb::full_list($que);

?>
<div class="popRoom">
	<div class="popRoomContent"></div>
</div>
<div class="editItems">
    <h1><?=$site['TITLE']?></h1>
	<div class="miniTabs">
		<?php foreach($menu as $men){ 
		if($men['position']==$position && $men['sub']){
			$subMenu = $men['sub'];
		}
		?>
		<div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>?frame=<?=$frameID?>&sID=<?=$siteID?>'"><p><?=$men['name']?></p></div>
		<?php } ?>
	</div>
	<?php if($subMenu){ ?>
	<div class="subMenuTabs">
		<?php foreach($subMenu as $sub){ ?>
		<div class="minitab<?=$sub['position']==$subposition?" active":""?>" onclick="window.location.href='<?=$sub['href']?>?frame=<?=$frameID?>&sID=<?=$siteID?>'"><?=$sub['name']?></div>
		<?php } ?>
	</div>
	<?php } ?>
	<div class="manageItems">
		<?
			if (!$edit) {
				$i = 0;
		?>
		<table width="600" border=0 style="border-collapse:collapse" align="center" cellpadding=5 cellspacing=1>
			<tr>
				<th width="30">#</th>
				<th>שם תוספת</th>
				
				<th>מחיר</th>
				<th width="30">פעילה</th>
				<th width="120">&nbsp;</th>
			</tr>
		<?
				foreach($extras as $tID => $row) { ?>

					<tr>
						<td align="center"><?=(++$i)?></td>
						<td align="center" onclick="openPop(<?=$row['MainPageID']?>, <?=$siteID?>)"><?=$row['MainPageTitle']?></td>
						
						<td align="center" style="direction:ltr;"><?php if($row['extraID']){ ?>₪ <?=$row['price']?><?php } ?></td>
						<td align="center"><?php if($row['extraID']){ ?><?=($row['active'] ? '<span style="color:green">כן</span>' : '<span style="color:red">לא</span>')?> <?php } ?></td>
						<td align="center">
							<?php if($row['extraID']){ ?>
							<div style="margin-top:4px;">
								<div style="float:right;" onclick="openPop(<?=$row['MainPageID']?>, <?=$siteID?>)"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;ערוך</div></td>
							</div>
							<?php } else {?>
							<div style="margin-top:4px;">
								<div style="float:right;" onclick="openPop(<?=$row['MainPageID']?>, <?=$siteID?>)"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;הוסף</div></td>
							</div>
							<?php } ?>
						</td>
					</tr>
				<?php }
		?>
		</table><br />
		<?	}  ?>


	</div>
</div>
</section>
<div id="alerts">
    <div class="container">
        <div class="closer"></div>
        <div class="title"></div>
        <div class="body"></div>
    </div>
</div>
<script src="<?=$root;?>/app/jquery-ui.min.js"></script>
<script>
function openPop(extraID, siteID){
	$(".popRoomContent").html('<iframe id="frame_'+extraID+'_'+siteID+'" frameborder=0 src="/cms/sites/editExtraNew.php?extraID='+extraID+'&siteID='+siteID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+extraID+'_'+siteID+'\')">x</div>');
	$(".popRoom").show();
	window.parent.document.getElementById("frame"+<?=$frameID?>).style.zIndex="12";
}

function closeTab(id){
	$(".popRoomContent").html('');
	$(".popRoom").hide();
	window.parent.document.getElementById("frame"+<?=$frameID?>).style.zIndex="10";
}
</script>
</body>
</html>