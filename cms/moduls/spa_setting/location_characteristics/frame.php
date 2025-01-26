<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";

const BASE_LANG_ID = 1;

$domainID = DomainList::active();
$langID   = LangList::active();

$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
	        $data = typemap($_POST, [
            'extraName'   =>  'string'

        ]);
        // main site data
        $siteData = [
            'extraName' => $data['extraName']
          
        ];

		$photo = pictureUpload('picture',"../../../../gallery/");
		if($photo){
			$siteData["extraPic"] = $photo[0]['file'];
		}

        if (!$pageID){      // opening new
            $pageID = udb::insert('treatmentsExtras', $siteData);
        } else {
            udb::update('treatmentsExtras', $siteData, '`extraID` = ' . $pageID);
        }

	}


    catch (LocalException $e){
        // show error
    } ?>

	<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}

if ($pageID){
    $site   = udb::single_row("SELECT * FROM treatmentsExtras WHERE extraID=".$pageID);
}



?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$site['extraName']?outDb($site['extraName']):"הוספת טיפול חדש"?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
			<div class="inputLblWrap">
				<div class="labelTo">שם התוספת</div>
				<input type="text" placeholder="שם התוספת" name="extraName" value="<?=js_safe($site['extraName'])?>" />
			</div>
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה: </div>
						<input type="file" name="picture" class="inpt" value="<?=$site['extraPic']?>">
					</div>
				</div>
				<?php if($site['extraPic']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../../../gallery/<?=$site['extraPic']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$site['id']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>

<script src="../../../app/tinymce/tinymce.min.js"></script>
