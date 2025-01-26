<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$position=9;


$siteID=intval($_GET['sID']);

$ArLevel2=Array("2"=>"site");

if('POST' == $_SERVER['REQUEST_METHOD']) { 


	$alias=Array();
	$alias['LEVEL1']="he";
	$alias['LEVEL2']="zimmer";
	$alias['LEVEL3']=inDb($_POST['LEVEL3'])?inDb($_POST['LEVEL3']):inDB($_POST['TITLE']);
	$alias['h1']=inDb($_POST['h1'])?inDb($_POST['h1']):inDB($_POST['TITLE']);
	$alias['title']=inDb($_POST['title'])?inDb($_POST['title']):inDB($_POST['TITLE']);
	$alias['keywords']=inDb($_POST['keywords']);
	$alias['description']=inDb($_POST['description']);
	$alias['ref']=$siteID;
	$alias['table']='sites';


	$que="SELECT * FROM `alias` WHERE `table`='sites' AND ref=".$siteID." ";
	$checkAlias= udb::single_row($que);
	if($checkAlias){
		udb::update("alias", $alias, "id=".$checkAlias['id']."");
	} else {
		$checkAlias['id']=udb::insert("alias", $alias);
	}



	if(isset($_POST['portalID'])){
		foreach($_POST['portalID'] as $portalID=>$val){

			$portalArray=Array();
			$portalArray['id']=$checkAlias['id'];
			$portalArray['LEVEL1']="he";
			$portalArray['PortalID']=$portalID;
			$portalArray['title']=$_POST['portal_title'][$portalID];
			$portalArray['h1']=$_POST['portal_title'][$portalID];
			$portalArray['LEVEL2']=$ArLevel2[$portalID];
			$portalArray['LEVEL3']=$_POST['portal_LEVEL3'][$portalID];
			$portalArray['keywords']=$_POST['portal_keywords'][$portalID];
			$portalArray['description']=$_POST['portal_description'][$portalID];
			$portalArray['ref']=$siteID;
			$portalArray['table']='sites';

			$que="SELECT id, ref, PortalID FROM `alias_text` WHERE `PortalID`='".$portalID."' AND `table`='sites' AND `ref`='".$siteID."' ";
			$checkAliasPortal= udb::single_row($que);
			if($checkAliasPortal){
				udb::update("alias_text", $portalArray, "`PortalID`='".$portalID."' AND `table`='sites' AND `ref`='".$siteID."'");
			} else {
				udb::insert("alias_text", $portalArray);
			}

		}
	}
}



$menu = include "site_menu.php";

$que="SELECT * FROM `sites` WHERE siteID=".$siteID."";
$site= udb::single_row($que);

$que="SELECT * FROM `alias` WHERE `table`='sites' AND ref=".$siteID." ";
$alias= udb::single_row($que);

$que="SELECT * FROM `portals` WHERE portalID!=1";
$portals= udb::key_row($que, "portalID");

?>
<div class="popRoom">
	<div class="popRoomContent"></div>
</div>
<div class="editItems">
    <h1><?=outDb($site['TITLE'])?></h1>
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
	<form method="POST" id="myform" enctype="multipart/form-data">
		<b>ערוך SEO</b>
		<div style="clear:both;"></div>
		<div class="miniTabs general" style="margin-right:50px;">
			<div class="tab active" data-portalid="1"><p>צימרטופ</p></div>
			<?php foreach($portals as $portal){ ?>
				<div class="tab<?=$portal['portalID']==1?" active":""?>" data-portalid="<?=$portal['portalID']?>"><p><?=$portal['portalName']?></p></div>
			<?php } ?>
		</div>
		<div class="frm" id="portalForm1">
			<div class="section">
				<div class="inptLine">
					<div class="label">כותרת עמוד: </div>
					<input type="text" value="<?=outDb($alias['title'])?>" name="title" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">קישור: </div>
					<input type="text" value="<?=outDb($alias['LEVEL3'])?>" name="LEVEL3" class="inpt">
				</div>
			</div>
			<div style="clear:both;"></div>
			<a href="<?=showAlias("sites", $site['siteID'])?>" target="_blank" style="direction:ltr;text-align:left;display:block"><?=urldecode(showAlias("sites", $site['siteID']))?></a>
			<div style="clear:both;"></div>
			<div class="section txtarea">
				<div class="inptLine">
					<div class="label">keywords: </div>
					<textarea name="keywords"><?=outDb($alias['keywords'])?></textarea>
				</div>
			</div>
			<div class="section txtarea">
				<div class="inptLine">
					<div class="label">תיאור קצר: </div>
					<textarea name="description"><?=outDb($alias['description'])?></textarea>
				</div>
			</div>
		</div>
		<?php
		$que = "SELECT * FROM alias_text WHERE ref=" . $site['siteID'] . " AND `table`='sites' ";
		$dataPortals = udb::key_row($que, "PortalID");

		foreach($portals as $portal){ ?>
			<div class="frm" id="portalForm<?=$portal['portalID']?>" style="display:none;">
				<input type="hidden" name="portalID[<?= $portal['portalID'] ?>]" value="<?=$portal['portalID'] ?>">
				<div class="section">
					<div class="inptLine">
						<div class="label">כותרת עמוד: </div>
						<input type="text" value="<?=outDb($dataPortals[$portal['portalID']]['title'])?>" name="portal_title[<?=$portal['portalID']?>]" class="inpt">
					</div>
				</div>
				<div class="section">
					<div class="inptLine">
						<div class="label">קישור: </div>
						<input type="text" value="<?=outDb($dataPortals[$portal['portalID']]['LEVEL3'])?>" name="portal_LEVEL3[<?=$portal['portalID']?>]" class="inpt">
					</div>
				</div>
				<div style="clear:both;"></div>
				<a href="<?=showAliasPortal("sites", $site['siteID'],$portal['portalID'])?>" target="_blank" style="direction:ltr;text-align:left;display:block"><?=urldecode(showAliasPortal("sites", $site['siteID'],$portal['portalID']))?></a>
				<div style="clear:both;"></div>
				<div class="section txtarea">
					<div class="inptLine">
						<div class="label">keywords: </div>
						<textarea name="portal_keywords[<?=$portal['portalID']?>]"><?=outDb($dataPortals[$portal['portalID']]['keywords'])?></textarea>
					</div>
				</div>
				<div class="section txtarea">
					<div class="inptLine">
						<div class="label">תיאור קצר: </div>
						<textarea name="portal_description[<?=$portal['portalID']?>]"><?=outDb($dataPortals[$portal['portalID']]['description'])?></textarea>
					</div>
				</div>
			</div>
		<?php } ?>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="שמור" class="submit">
			</div>
		</div>
	</form>
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
	$(".general .tab").click(function(){
		$(".general .tab").removeClass("active");
		$(this).addClass("active");

		var ptID = $(this).data("portalid");
		$(".frm").css("display","none");
		$("#portalForm"+ptID).css("display","block");
	});
</script>
</body>
</html>