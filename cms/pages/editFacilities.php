<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$pageID=intval($_GET['pageID']);

if('POST' == $_SERVER['REQUEST_METHOD']) {

	$cp=Array();
	$cp['facName'] = ($_POST['facName']);
	$cp["ifShow"] = intval($_POST['ifShow'])?"1":"0";
	$photo = pictureUpload('picture',"../../gallery/");
	if($photo){
		$cp["picture"] = $photo[0]['file'];
	}
	if($pageID){
		udb::update("facilites", $cp, "ID =".$pageID);
	} else {
		$pageID = udb::insert("facilites", $cp);
	}
?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php
	
}

$position=1;
$menu = include "pages_menu.php";


if($pageID){
	$que="SELECT * FROM `facilites` WHERE ID=".$pageID." ";
	$page= udb::single_row($que);
}

?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$page['facName']?outDb($page['facName']):"הוספת דף חדש"?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<div class="frm" id="langTab1">
			<div class="section">
				<div class="inptLine">
					<div class="label">כותרת: </div>
					<input type="text" value='<?=outDb($page['facName'])?>' name="facName" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">מוצג באתר: </div>
					<div class="chkBox">
						<input type="checkbox" value="1" <?=$page['ifShow']?"checked":""?><?=(!$pageID?"checked":"")?> name="ifShow" id="ifShow_<?=$siteID?$siteID:0?>">
						<label for="ifShow_<?=$siteID?$siteID:0?>"></label>
					</div>
				</div>
			</div>
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה: </div>
						<input type="file" name="picture" class="inpt" value="<?=$page['picture']?>">
					</div>
				</div>
				<?php if($page['picture']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../gallery/<?=$page['picture']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
			<div style="clear:both;"></div>
		<div style="clear:both;"></div>
		</div>
		
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$page['ID']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>


