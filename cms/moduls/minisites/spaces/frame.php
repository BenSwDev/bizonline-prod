<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";


$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if('POST' == $_SERVER['REQUEST_METHOD']) {


  $data = typemap($_POST, [
            'spaceName'   =>  'string',
            '!isBedroom'    => 'int',
            'roomsCount'    => 'int',
	]);
	$cp=Array();
	$cp['spaceName'] = $data['spaceName'];
	$cp['isBedroom'] = $data['isBedroom'] ;
    $cp['roomsCount'] = $data['roomsCount'] ;


/*	$cp["LangID"] = intval($_POST['LangID']);

	$photo = pictureUpload('picture',"../../gallery/");
	if($photo){
		$cp["picture"] = $photo[0]['file'];
	}*/


	if($pageID){
		udb::update("spaces_type", $cp, "id =".$pageID);
	} else {

		$pageID = udb::insert("spaces_type", $cp);

	}
?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}


if($pageID){
	$que="SELECT * FROM `spaces_type` WHERE id=".$pageID." ";
	$page= udb::single_row($que);

}


?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$page['spaceName']?outDb($page['spaceName']):"הוספת אזור חדש"?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
			<div class="section">
				<div class="inptLine">
					<div class="label">שם האזור: </div>
					<input type="text" value='<?=stripslashes(htmlspecialchars($page['spaceName'], ENT_QUOTES))?>' name="spaceName" class="inpt">
				</div>
			</div>
			<div class="inputLblWrap">
				<div class="switchTtl">חדר שינה</div>
				<label class="switch">
				  <input type="checkbox" name="isBedroom" value="1" <?=($page['isBedroom'] ? 'checked="checked"' : '')?>  />
				  <span class="slider round"></span>
				</label>
			</div>
            <div class="section">
                <div class="inptLine">
                    <div class="label">מספר חדרים: </div>
                    <input type="text" value='<?=intval($page['roomsCount']);?>' name="roomsCount" class="inpt">
                </div>
            </div>

			<?php if(1==2) { ?>
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
			<?php } ?>
		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$page['id']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>
