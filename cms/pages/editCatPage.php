<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if('POST' == $_SERVER['REQUEST_METHOD']) {


	if($_POST['cats']){
		$cats = implode(",",$_POST['cats']);
	}
	$cp=Array();
	$cp['MainPageTitle'] = ($_POST['MainPageTitle']);
	$cp["ifShow"] = intval($_POST['ifShow'])?"1":"0";
	$cp["phoneNum"] = ($_POST['phoneNum']);
	$cp["address"] = ($_POST['address']);
	$cp["tags"] = $cats;
	$cp["MainPageType"] = intval($pageType);

	$photo = pictureUpload('picture',"../../gallery/");
	if($photo){
		$cp["picture"] = $photo[0]['file'];
	}


	if($pageID){
		udb::update("MainPages", $cp, "MainPageID =".$pageID);
	} else {
		$cp["createDay"] = date("Y-m-d");
		$pageID = udb::insert("MainPages", $cp);

	}

?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php
	
}

$position=1;
$menu = include "pages_menu.php";



if($pageID){
	$que="SELECT * FROM `MainPages` WHERE MainPageID=".$pageID." ";
	$page= udb::single_row($que);
	$categ = explode(",",$page['tags']);
}


?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$page['MainPageTitle']?outDb($page['MainPageTitle']):"הוספת דף חדש"?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<input type="hidden" name="LangID" value="<?=$langID?>">
		<div class="frm" id="langTab1">
			<div class="section">
				<div class="inptLine">
					<div class="label">כותרת: </div>
					<input type="text" value='<?=outDb($page['MainPageTitle'])?>' name="MainPageTitle" class="inpt">
				</div>
			</div>

			<div class="section">
				<div class="inptLine">
					<div class="label">טלפון </div>
					<input type="text" value='<?=outDb($page['phoneNum'])?>' name="phoneNum" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">כתובת </div>
					<input type="text" value='<?=outDb($page['address'])?>' name="address" class="inpt">
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

			<?php if($pageType==99){ ?>
			<div  style="clear:both;"></div>
			<div class="section">
				<div class="inptLine">
					<div class="label">קישור: </div>
					<input type="text" value="<?=outDb($page['link'])?>" name="link" class="inpt">
				</div>
			</div>
			<?php } ?>
			<div style="clear:both;"></div>

		<div style="clear:both;"></div>

		<div class="catWrap">
			<div class="catTtl">שיוך אטרקציה לקטגוריה</div>
		<?php $que="SELECT * FROM `category` WHERE `ifShow`=1 AND type=".intval($_GET['catType']); 
			  $facilities=udb::full_list($que); 
			  if($facilities) { 
			  foreach($facilities as $facilite){ 
			  ?>
				<div class="matWrap">
					<input type="checkbox" <?php if($categ) {if(in_array($facilite['ID'], $categ)){ echo 'checked'; }}?>  name="cats[<?=$facilite['ID']?>]" value="<?=$facilite['ID']?>">
					<span class="matName"><?=outDb($facilite['facName'])?></span>
				</div>
				<?php } } ?>
		</div>
		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$page['MainPageID']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>

<script>
	$(".general .lngtab").click(function(){
		$(".general .lngtab").removeClass("active");
		$(this).addClass("active");

		var ptID = $(this).data("langid");
		$(".frm").css("display","none");

		$("#langTab"+ptID).css("display","block");
	});

</script>