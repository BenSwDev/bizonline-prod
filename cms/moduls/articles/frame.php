<?php
include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "../../_globalFunction.php";


$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['langID'])?intval($_GET['langID']):1;
$domainID = intval($_GET['domainID']);

$articlesFolder = udb::single_value("select articlesFolder from domains where domainID=".$domainID);


if ('POST' == $_SERVER['REQUEST_METHOD']){
/*ini_set('display_errors', 1);
error_reporting(-1 ^ E_NOTICE);*/
    try {

	    $data = typemap($_POST, [
            'mainPageTitle'   => 'string',
            'html_text'   => 'html',
            'shortDesc'   => 'html',
            'articleTextTop'   => 'text',
            'articleFooter'   => 'html',
			'category'    => 'int',
			'!ifShow'    => 'int',
			'!promoteArt'    => 'int',
            'main_area'  => 'int',
            'area'		 => 'int',
            'city'		 => 'int',
			'type'		 => 'int',
			'attributes'   => ['int' => 'int'],
			'ads'   => ['int' => 'int'],
			'customArt'   => ['int' => 'int'],
			'articleWriter'   => 'int',
			'createdNew' => 'string'

        ]);
        // main site data


		$jsonData = [];
		$jsonData['main_area'] = $data['main_area'];
		if($data['area']){
			$jsonData['main_area'] = "";
			$jsonData['area'] = $data['area'];

		}
		if($data['city']){
			$jsonData['main_area'] = "";
			$jsonData['area'] = "";
			$jsonData['city'] = $data['city'];

		}

		$jsonData['attr'] = $data['attributes'];
		$jsonData['type'] = $data['type'];
		if($data['dateChoose']==2){
			$jsonData['date1'] = $data['startDate'];
			$jsonData['date2'] = $data['endDate'];
		}

		$jsonData = array_filter($jsonData);
		ksort($jsonData);

		$jsonItems = [];
		if($data['ads']){
			$jsonItems['nid'] = $data['ads'];
		}

        $siteData = [
            'ifShow' => $data['ifShow'],
            'promoteArt' => $data['promoteArt'],
			'langID'    => $langID,
			'domainID'    => $domainID,
			'articleSubjectOldID'   => $data['category'],
			'articleTitle'  => $data['mainPageTitle'],
			'articleText'  => $data['html_text'],
			'articleTextTop'  => $data['articleTextTop'],
			'shortDesc'  => $data['shortDesc'],
			'articleFooter'  => $data['articleFooter'],
			'articleWriter'  => $data['articleWriter'],
			'createdNew' => DateTime::createFromFormat("d/m/Y", $data['createdNew'])->format("Y-m-d H:i:s")

        ];
		$siteData['articleTags']  = ($jsonData?json_encode($jsonData, JSON_NUMERIC_CHECK):"");
		$siteData['articleItems'] = ($jsonItems?json_encode($jsonItems, JSON_NUMERIC_CHECK):"");

		$photo = pictureUpload('picture',"../../../gallery/");
		if($photo){
			$siteData["picture"] = $photo[0]['file'];
		}


/*


		if($_FILES['picture']['size']){
			$file = new Core\Files\Optimizer($_FILES['picture']);
			$resultAws = $file->saveToAWS('ssd/sites/'.$siteID);
			//$resultAws1 = $file->setSize(604)->saveToAWS('ssd/sites/'.$siteID.'/604');
			if($resultAws){
				$que = "SELECT `folderID` FROM `folder` WHERE `siteID`=".$siteID." AND `isMain`=1";
				$galID = udb::single_value($que);
				$fileArr=Array();
				$fileArr['src']='https://images.hapisga.co.il/'.$resultAws['ssd_db_path'];
				$fileArr['table']="folder";
				$fileArr['ref']=$galID;
				$file = udb::insert("files", $fileArr);
				if($file){
					$update = ['pic_winter' => $file];
					 udb::update('sites', $update, '`siteID` = ' . $siteID);
				}
			}
		}


*/


		$que = "UPDATE `articles` SET `promoteArt`=0 WHERE `promoteArt`=1";
		udb::query($que);

        if (!$pageID){      // opening new
            $pageID = udb::insert('articles', $siteData);
        } else {
            udb::update('articles', $siteData, '`nid` = ' . $pageID);
        }
//		udb::query("DELETE FROM `selecteArticles` WHERE `currentArtID`=".$pageID);
//		foreach($data['customArt'] as $cust){
//			udb::insert('selecteArticles',['currentArtID' => $pageID,'selectArtID' => $cust]);
//
//		}


		$data = typemap($_POST, [
			'seoTitle'   => 'string',
			'seoH1'   => 'string',
			'seoLink'   => 'string',
			'seoKeyword'   =>'string',
			'seoDesc'   => 'string'

		]);
		$data['ref']=$pageID;
		$data['table']="articles";
		$data['LEVEL1'] = globalLangSwitch($langID);
		$data['LEVEL2']= $articlesFolder;




		if($_POST['level3']!=$pageID && $_POST['level3']!=0){
			$data['LEVEL3']=intval($_POST['level3']);
		}else{
			$data['LEVEL3']=$pageID;
		}


		$siteSeoData = [
			'domainID'  => $domainID,
			'langID'    => $langID,
			'title'  => ($data['seoTitle']?$data['seoTitle']:$siteData['articleTitle']),
			'h1'  => $data['seoH1'],
			'description'  => $data['seoDesc'],
			'keywords'  => $data['seoKeyword'],
			'ref'  => $data['ref'],
			'table'  => $data['table'],
			'LEVEL1' => $data['LEVEL1'],
			'LEVEL2' => $data['LEVEL2'],
			'LEVEL3' => $data['LEVEL3'],
		];
		if(!$_POST['seoid']){
			udb::insert('alias_text', $siteSeoData);
		}else{
			udb::update('alias_text', $siteSeoData , "id=".intval($_POST['seoid']));
		}
	}
    catch (LocalException $e){
        // show error
    } ?>

	<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}



$allArticles = udb::full_list("SELECT * FROM `articles` WHERE langID=$langID AND domainID=$domainID AND ifShow=1 ORDER BY `showOrder`");
if ($pageID){
    $site = udb::single_row("SELECT * FROM `articles` WHERE `nid`=".$pageID." AND langID=$langID AND domainID=$domainID");
	$que = "SELECT * FROM `alias_text` WHERE `ref`=".$pageID." AND `table`='articles'";
	$seo = udb::single_row($que);
	$fieldData = json_decode($site['articleTags'],true);
	$facilites = $fieldData['attr'];
	$itemsData = json_decode($site['articleItems'],true);
	$items = $itemsData['nid'];
	$allArticles = udb::full_list("SELECT * FROM `articles` WHERE `nid`!=".$pageID." AND langID=$langID AND domainID=$domainID AND ifShow=1 ORDER BY `showOrder`");
}



$categoriesArt = udb::full_list("SELECT * FROM `menu` WHERE menuType=5 AND menuParent!=0 AND LangID=".$langID);

$mainareas = udb::full_list("SELECT `main_areaID`, `TITLE` FROM `main_areas` WHERE 1 ORDER BY `TITLE`");
$areas = udb::full_list("SELECT `areaID`, `TITLE` FROM `areas` WHERE 1 ORDER BY `TITLE`");
$cities = udb::full_list("SELECT * FROM `settlements` WHERE 1 ORDER BY `TITLE`");

$categories = udb::key_row("SELECT * FROM `attributes_categories` WHERE `active` = 1 AND `domainID` = " . $domainID . " ORDER BY showOrder" , 'categoryID');
$attributes = udb::key_row("SELECT d.*, a.defaultName FROM `attributes` AS `a` INNER JOIN `attributes_domains` AS `d` USING(`attrID`) WHERE d.active = 1 ORDER BY showOrder" , ['categoryID', 'attrID']);

$types = udb::full_list("SELECT * FROM `roomTypes` WHERE 1");
$reviewsWriters = udb::full_list("SELECT * FROM `reviewsWriters` WHERE `active` = 1");

$siteAds = udb::full_list("SELECT `sites`.`siteName`, `sites_article_ads`.*  FROM `sites_article_ads` INNER JOIN `sites` USING (siteID)");

?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
.hide{display:none}
</style>
<div class="editItems">
    <h1><?=$site['articleTitle']?outDb($site['articleTitle']):"הוספת מאמר חדש"?></h1>
	<?//print_r($site);?>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >

			<div class="inputLblWrap">
				<div class="labelTo">כותרת מאמר</div>
				<input type="text" placeholder="כותרת מאמר" name="mainPageTitle" value="<?=js_safe($site['articleTitle'])?>" />
			</div>
			<div class="inputLblWrap">
				<div class="switchTtl">מוצג</div>
				<label class="switch">
					<input type="checkbox" name="ifShow" value="1" <?=($site['ifShow'] ? 'checked="checked"' : '')?> />
					<span class="slider round"></span>
				</label>
			</div>
			<div class="inputLblWrap">
				<div class="switchTtl">מאמר מקודם</div>
				<label class="switch">
					<input type="checkbox" name="promoteArt" value="1" <?=($site['promoteArt'] ? 'checked="checked"' : '')?> />
					<span class="slider round"></span>
				</label>
			</div>

			<div class="inputLblWrap">
				<div class="labelTo">תאריך המאמר</div>
				<input type="text" value="<?=($site['createdNew']?date("d/m/Y", strtotime($site['createdNew'])):date("d/m/Y"))?>" name="createdNew" class="datePick" />
			</div>

			<div class="inputLblWrap">
				<div class="labelTo">כותב מאמר</div>
				<select name="articleWriter">
					<option value="0">-</option>
					<?php foreach($reviewsWriters as $writer) { ?>
					<option value="<?=$writer['writerID']?>" <?=($writer['writerID']==$site['articleWriter']?"selected":"")?> ><?=$writer['writerName']." - (".$writer['writerID'].")"?></option>
					<?php } ?>
				</select>
			</div>

			<div class="inputLblWrap">
				<div class="labelTo">קטגוריית המאמר</div>
				<select name="category">
					<option value="0">-</option>
					<?php foreach($categoriesArt as $category) { ?>
					<option value="<?=$category['menuID']?>" <?=($category['menuID']==$site['articleSubjectOldID']?"selected":"")?> ><?=$category['menuTitle']?></option>
					<?php } ?>
				</select>
			</div>

			<div class="section txtarea big">
				<div class="inptLine">
					<div class="label noFloat">כותרת עליונה</div>
					<textarea  name="articleTextTop"><?=outDb($site['articleTextTop'])?></textarea>
				</div>
			</div>
			
			<div class="section txtarea big" style="display:none">
				<div class="inptLine">
					<div class="label noFloat">טקסט עליון</div>
					<textarea class="textEditor" name="shortDesc"><?=outDb($site['shortDesc'])?></textarea>
				</div>
			</div>
			<div class="section txtarea big">
				<div class="inptLine">
					<div class="label noFloat">טקסט מרכזי</div>
					<textarea class="textEditor" name="html_text"><?=outDb($site['articleText'])?></textarea>
				</div>
			</div>
			<div class="section txtarea big"  style="display:none">
				<div class="inptLine">
					<div class="label noFloat">טקסט תחתון</div>
					<textarea class="textEditor" name="articleFooter"><?=outDb($site['articleFooter'])?></textarea>
				</div>
			</div>
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה: </div>
						<input type="file" name="picture" class="inpt" value="<?=$site['picture']?>">
					</div>
				</div>
				<?php if($site['picture']){ ?>
				<div class="section">
					<div class="inptLine">

						<img src="<?=picturePath($site['picture'],"/gallery/")?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>


			<div style="clear:both;"></div>
			<div class="inputLblWrap">
				<div class="labelTo">אזור ראשי</div>
				<select name="main_area">
					<option value="0">- - בחר אזור ראשי - -</option>
					<?php foreach($mainareas as $mainarea) { ?>
					<option value="<?=$mainarea['main_areaID']?>" <?=$fieldData['main_area']==$mainarea['main_areaID']?"selected":""?>><?=$mainarea['TITLE']?></option>
					<?php } ?>
				</select>
			</div>

			<div class="inputLblWrap">
				<div class="labelTo">אזור</div>
				<select name="area">
					<option value="0">- - בחר אזור - -</option>
					<?php foreach($areas as $area) { ?>
					<option value="<?=$area['areaID']?>" <?=$fieldData['area']==$area['areaID']?"selected":""?>><?=$area['TITLE']?></option>
					<?php } ?>
				</select>
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">ישוב</div>
				<select name="city" title="ישוב">
					<option value="0" >- - בחר ישוב - -</option>
					<?php foreach($cities as $city) { ?>
					<option value="<?=$city['settlementID']?>" <?=$fieldData['city']==$city['settlementID']?"selected":""?>><?=$city['TITLE']?></option>
					<?php } ?>
				</select>
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">סוג</div>
				<select name="type" title="סוג">
					<option value="0">סוג</option>
					<?php foreach($types as $type) { ?>
					<option value="<?=$type['id']?>" <?=$fieldData['type']==$type['id']?"selected":""?>><?=$type['roomType']?></option>
					<?php } ?>
				</select>
			</div>

            <?php foreach($categories as $category) { ?>
                <div class="catName" style="cursor:pointer" onclick="$('#category<?=$category['categoryID']?>').toggleClass('hide')"><?=$category['categoryName']?></div>
                <div class="checksWrap hide" id="category<?=$category['categoryID']?>" class="hide">
                    <?php
                    if($attributes[$category['categoryID']]){
                        Translation::attributes(array_keys($attributes[$category['categoryID']]), '*', $langID, $domainID)->apply($attributes[$category['categoryID']]);

                        foreach($attributes[$category['categoryID']] as $attribute) {
                            ?>
                            <div class="checkLabel checkIb">
                                <div class="checkBoxWrap">
                                    <input class="checkBoxGr" type="checkbox" name="attributes[]" <?=($facilites?(in_array($attribute['attrID'],$facilites)?"checked":""):"")?> value="<?=$attribute['attrID']?>" id="<?=$attribute['attrID']?>">
                                    <label for="<?=$attribute['attrID']?>"></label>
                                </div>
                                <label for="<?=$attribute['attrID']?>"><?=$attribute['defaultName']?></label>
                            </div>
                        <?php } } ?>
                </div>
                <?php
            }
            ?>
            <!--<div class="items">
				<div class="itemsTitle">מודעות צימרים</div>
				<input type="text" placeholder="סינון" id="filter">
				<div class="adsCont">
				<?php
                //if($siteAds) {
				//foreach($siteAds as $ad) { ?>
					<div class="checkLabel checkIb adsWrap" data-name="<?//=$ad['adTItle']." - ".$ad['siteName']?>">
						<div class="checkBoxWrap">
							<input class="checkBoxGr" type="checkbox" name="ads[]"
							<?//=($items?(in_array($ad['adID'],$items)?"checked":""):"")?>
							value="<?//=$ad['adID']?>" id="ad<?//=$ad['adID']?>">
							<label for="ad<?//=$ad['adID']?>"></label>
						</div>
						<label for="ad<?//=$ad['adID']?>"><?//=$ad['adTItle']." - ".$ad['siteName']?></label>
					</div>
				<?php //}} ?>
				</div>
			</div>
			<div class="catName">הצגת כתבות נבחרות</div>
            <div class="checksWrap">
                <?php
                //$selecteArticles = udb::single_column("SELECT selectArtID FROM `selecteArticles` WHERE currentArtID=".$pageID);
                //foreach($allArticles as $art) { ?>
                    <div class="checkLabel checkIb">
                        <div class="checkBoxWrap">
                            <input class="checkBoxGr" type="checkbox" name="customArt[]" <?//=($selecteArticles?(in_array($art['nid'],$selecteArticles)?"checked":""):"")?> value="<?//=$art['nid']?>" id="<?//=$art['nid']?>">
                            <label for="<?//=$art['nid']?>"></label>
                        </div>
                        <label for="<?//=$art['nid']?>"><?//=$art['articleTitle']?></label>
                    </div>
                <?php //} ?>
            </div>-->

		</div>

		<div class="mainSectionWrapper">
			<input type="hidden" name="seoid" value="<?=intval($seo['id'])?>">
			<input type="hidden" name="level3" value="<?=intval($seo['LEVEL3'])?>">
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
					<textarea name="seoDesc"><?=htmlspecialchars_decode($seo['description'])?></textarea>
				</div>
			</div>

		</div>


		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$site['nid']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>

<script src="../../app/tinymce/tinymce.min.js"></script>
<script type="text/javascript">


$('#filter').keyup(function () {

    var filter = this.value.toLowerCase();  // no need to call jQuery here

    $('.adsWrap').each(function() {
        var _this = $(this);
        var title = _this.data('name').toLowerCase();
        if (title.indexOf(filter) < 0) {
            _this.hide();
        }else{
			 _this.show();
		}
    });
});


	tinymce.init({
	  selector: 'textarea.textEditor' ,
	  height: 500,
	  directionality : "rtl",
	  paste_data_images: true,
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

	$(".datePick").datepicker({
		format:"dd/mm/yyyy",
		changeMonth:true
	});
</script>