<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";


$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['langID'])?intval($_GET['langID']):1;
$domainID = intval($_GET['domainID']);




if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
	
	    $data = typemap($_POST, [
            'mainPageTitle'   => 'string',
            'html_text'   => 'html',
            'articleTextTop'   => 'html',
            'articleTextBottom'   => 'html',
            'phone'   => 'string',
			'category'    => 'int',
			'!ifShow'    => 'int'

        ]);
        // main site data
		
        $siteData = [
            'ifShow' => $data['ifShow'],
			'langID'    => $langID,
			'domainID'    => $domainID,
			'adTitle'  => $data['mainPageTitle'],
			'adText'  => $data['html_text'],
			'phone'  => $data['phone']
          
        ];
		
		$photo = pictureUpload('picture',"../../../../gallery/");
		if($photo){
			$siteData["picture"] = $photo[0]['file'];
		}
	
        if (!$pageID){      // opening new
            $pageID = udb::insert('sites_ads', $siteData);
        } else {
            udb::update('sites_ads', $siteData, '`adID` = ' . $pageID);
        }

/*
		$data = typemap($_POST, [
			'seoTitle'   => 'string',
			'seoH1'   => 'string',
			'seoLink'   => 'string',
			'seoKeyword'   =>'string',
			'seoDesc'   => 'string'
			
		]);
		$data['ref']=$pageID;
		$data['table']="sites_ads";
		switch($langID){
			case 1: $data['LEVEL1'] = ""; break;
			case 2: $data['LEVEL1'] = "eng"; break;
			case 3: $data['LEVEL1'] = "fr"; break;
		};
		$data['LEVEL2']="art";


		$siteData = [
			'domainID'  => $domainID,
			'langID'    => $langID,
			'title'  => $data['seoTitle'],
			'h1'  => $data['seoH1'],
			'description'  => $data['seoDesc'],
			'keywords'  => $data['seoKeyword'],
			'ref'  => $data['ref'],
			'table'  => $data['table'],
			'LEVEL1' => $data['LEVEL1'],
			'LEVEL2' => $data['LEVEL2'],
			'LEVEL3' => $pageID,
		];



		udb::insert('alias_text', $siteData , true);
*/


	}

	

    catch (LocalException $e){
        // show error
    } ?>

	<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}

if ($pageID){
    $site = udb::single_row("SELECT * FROM `sites_ads` WHERE `adID`=".$pageID);
	/*$que = "SELECT * FROM `alias_text` WHERE `ref`=".$pageID." AND `table`='articles'";
	$seo = udb::single_row($que);*/
}
?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$site['adTitle']?outDb($site['adTitle']):"הוספת מודעה"?></h1>

	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >

			<div class="inputLblWrap">
				<div class="labelTo">כותרת</div>
				<input type="text" placeholder="כותרת" name="mainPageTitle" value="<?=js_safe($site['adTitle'])?>" />
			</div>
			<div class="inputLblWrap">
				<div class="switchTtl">מוצג</div>
				<label class="switch">
					<input type="checkbox" name="ifShow" value="1" <?=($site['ifShow'] ? 'checked="checked"' : '')?> <?=($site['ifShow']==1 && $id==0)?"checked":""?> />
					<span class="slider round"></span>
				</label>
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">טלפון</div>
				<input type="text" placeholder="טלפון" name="phone" value="<?=js_safe($site['phone'])?>" />
			</div>
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה  ראשית:</div>
						<input type="file" name="picture" class="inpt" value="<?=$site['picture']?>">
					</div>
				</div>
				<?php if($site['picture']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../../../gallery/<?=$site['picture']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
			<div class="section txtarea big">
				<div class="inptLine">
					<div class="label noFloat">טקסט</div>
					<textarea class="textEditor" name="html_text"><?=outDb($site['adText'])?></textarea>
				</div>
			</div>
		</div>

<?/*?>
		<div class="mainSectionWrapper">
			<div class="sectionName">SEO</div>
			<div class="inputLblWrap">
				<div class="labelTo">כותרת עמוד</div>
				<input type="text" placeholder="כותרת עמוד" name="seoTitle" value="<?=outDb($seo['title'])?>" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">H1</div>
				<input type="text" placeholder="H1" name="seoH1" value="<?=outDb($seo['h1'])?>" />
			</div>
			<div class="section txtarea">
				<div class="inptLine">
					<div class="label">מילות מפתח</div>
					<textarea name="seoKeyword"><?=outDb($seo['keywords'])?></textarea>
				</div>
			</div>
			<div class="section txtarea">
				<div class="inptLine">
					<div class="label">תאור דף</div>
					<textarea name="seoDesc"><?=outDb($seo['description'])?></textarea>
				</div>
			</div>
		</div>
<?*/?>

		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$site['id']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>

<script src="../../../app/tinymce/tinymce.min.js"></script>
<script type="text/javascript">



	tinymce.init({
	  selector: 'textarea.textEditor' ,
	  height: 500,
	  directionality : "rtl",
	  plugins: [
		"advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
		"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
		"table contextmenu directionality emoticons textcolor paste  textcolor colorpicker textpattern"
	  ],
	  fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
	  toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
	  toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor",
	  toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking pagebreak restoredraft"

	});

</script>