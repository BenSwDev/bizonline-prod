<?php
include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "../../_globalFunction.php";

const BASE_LANG_ID = 1;

$domainID = DomainList::active();
$langID   = LangList::active();

$pageID=intval($_GET['pageID']);
$searchPageID=intval($_GET['searchPageID']);
$pageType=intval($_GET['pageType']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;

if($searchPageID){
	$que = "SELECT `data` FROM `search` WHERE id=".$searchPageID;
	$searchData = udb::single_value($que);
	$que = "SELECT `id` FROM `category_articles` WHERE `searchField`='".$searchData."'";
	$categoryID = udb::single_value($que);
	$pageID = ($categoryID?$categoryID:0);

}


if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
	
	    $data = typemap($_POST, [
            'mainPageTitle'   => ['int' => ['int' => 'string']],
           // 'shortDesc'   => ['int' => ['int' => 'html']],
            'html_text'   => ['int' => ['int' => 'html']],
			//'main_area'  => 'int',
            //'area'		 => 'int',
            //'city'		 => 'int',
			//'type'		 => 'int',
			'!ifShow'    => ['int' => ['int' => 'int']]
			//'attributes'   => ['int' => 'int']

        ]);
/*
		$jsonData = [];
		$jsonData['city'] = $data['main_area'];
		if($data['area']){
			$jsonData['city'] = $data['area'];
		
		}
		if($data['city']){
			$jsonData['city'] = $data['city'];
		
		}

		$jsonData['attr'] = $data['attributes'];
		$jsonData['type'] = $data['type'];

		$jsonData = array_filter($jsonData);
		ksort($jsonData);
		
		if (!$data['mainPageTitle'][0][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');
*/

        // main site data
        $siteData = [
            'title'		 => $data['mainPageTitle'][0][BASE_LANG_ID],
            'searchField' => $searchData,
            'status'	 => $data['ifShow'][0][BASE_LANG_ID] ?? 0
          
        ];

		/*
		$photo = pictureUpload('picture',"../../../gallery/");
		if($photo){
			$siteData["picture"] = $photo[0]['file'];
		}
		*/
        if (!$pageID){      // opening new
            $pageID = udb::insert('category_articles', $siteData);
        } else {
            udb::update('category_articles', $siteData, '`id` = ' . $pageID);
        }

	    // saving data per domain
        foreach(DomainList::get(0) as $did => $dom){
			foreach(LangList::get() as $lid => $lang){
				udb::insert('category_articles_langs', [
					'id'    => $pageID,
					'domainID'  => $did,
					'langID'    => $lid,
					'title'  => $data['mainPageTitle'][$did][$lid],
					'text'  => $data['html_text'][$did][$lid],
					'status'   => $data['ifShow'][$did][$lid] ?? 0
					//'shortDesc'  => $data['shortDesc'][$did][$lid],
				], true);
				udb::insert('category_articles_langs', [
					'id'    => $pageID,
					'domainID'  => 1,
					'langID'    => $lid,
					'title'  => $data['mainPageTitle'][$did][$lid],
					'text'  => $data['html_text'][$did][$lid],
					'status'   => $data['ifShow'][$did][$lid] ?? 0
					//'shortDesc'  => $data['shortDesc'][$did][$lid],
				], true);
			}
		}


		$dataSeo = typemap($_POST, [
			'seoTitle'   => ['int' => ['int' => 'string']],
			'seoH1'   => ['int' => ['int' => 'string']],
			'seoKeyword'   => ['int' => ['int' => 'string']],
			'seoDesc'   => ['int' => ['int' => 'string']]
		]);



		$dataSeo['ref']=$pageID;
		$dataSeo['table']="category_articles";
		
		$dataSeo['LEVEL2']="art";
		$dataSeo['LEVEL3']=$pageID;
	
		$checkId = false;
		if($pageID){
		$que = "SELECT `id` FROM alias_text WHERE `ref`=$pageID AND `table`='".$dataSeo['table']."'" ;
		$checkId = udb::single_value($que);
		}

		foreach(DomainList::get(0) as $did => $dom){
			foreach(LangList::get() as $lid => $lang){
				$dataSeo['LEVEL1'] = globalLangSwitch($lid);

				$siteSeoData = [
					'domainID'  => $did,
					'langID'    => $lid,
					'title'  => ($dataSeo['seoTitle'][$did][$lid]?$dataSeo['seoTitle'][$did][$lid]:$data['mainPageTitle'][$did][$lid]),
					'h1'  => $dataSeo['seoH1'][$did][$lid],
					'description'  => $dataSeo['seoDesc'][$did][$lid],
					'keywords'  => $dataSeo['seoKeyword'][$did][$lid],
					'ref'  => $dataSeo['ref'],
					'table'  => $dataSeo['table'],
					'LEVEL1' => $dataSeo['LEVEL1'],
					'LEVEL2' => $dataSeo['LEVEL2'],
					'LEVEL3' => $dataSeo['LEVEL3']
				];


				if(!$checkId){
					udb::insert('alias_text', $siteSeoData);
				}else{
					udb::update('alias_text', $siteSeoData, "`domainID`=$did AND `langID`=$lid AND `ref`=".$dataSeo['ref']." AND `table`='".$dataSeo['table']."'");
				}

/*copy*/


				$siteSeoData = [
					'domainID'  => 1,
					'langID'    => $lid,
					'title'  => ($dataSeo['seoTitle'][$did][$lid]?$dataSeo['seoTitle'][$did][$lid]:$data['mainPageTitle'][$did][$lid]),
					'h1'  => $dataSeo['seoH1'][$did][$lid],
					'description'  => $dataSeo['seoDesc'][$did][$lid],
					'keywords'  => $dataSeo['seoKeyword'][$did][$lid],
					'ref'  => $dataSeo['ref'],
					'table'  => $dataSeo['table'],
					'LEVEL1' => $dataSeo['LEVEL1'],
					'LEVEL2' => $dataSeo['LEVEL2'],
					'LEVEL3' => $dataSeo['LEVEL3']
				];


				if(!$checkId){
					udb::insert('alias_text', $siteSeoData);
				}else{
					udb::update('alias_text', $siteSeoData, "`domainID`=$did AND `langID`=$lid AND `ref`=".$dataSeo['ref']." AND `table`='".$dataSeo['table']."'");
				}

/*copy*/



			}
		}
	}

	

    catch (LocalException $e){
        // show error
    } ?>

	<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}



if ($pageID){
    $site = udb::single_row("SELECT * FROM `category_articles` WHERE `id`=".$pageID);
    $siteLangs = udb::key_row("SELECT * FROM `category_articles_langs` WHERE `id` = " . $pageID, ['domainID','langID']);
	$que = "SELECT * FROM `alias_text` WHERE `ref`=$pageID AND `table`='category_articles'";
	$seo = udb::key_row($que, ['domainID','langID']);

/*
	if($site){
		$fieldData = json_decode($site['searchField'],true);
		$facilites = $fieldData['attr'];
	}
*/
}

/*
$mainareas = udb::full_list("SELECT `main_areaID`, `TITLE` FROM `main_areas` WHERE 1 ORDER BY `TITLE`");
$areas = udb::full_list("SELECT `areaID`, `TITLE` FROM `areas` WHERE 1 ORDER BY `TITLE`");
$cities = udb::full_list("SELECT * FROM `settlements` WHERE 1 ORDER BY `TITLE`");
$categories = udb::key_row("SELECT * FROM `attributes_categories` WHERE 1 AND `categoryID`!=258 ORDER BY showOrder" , 'categoryID');
$attributes = udb::key_list("SELECT * FROM `attributes` WHERE 1 ORDER BY showOrder" , 'categoryID');
$types = udb::full_list("SELECT * FROM `roomTypes` WHERE 1");
*/

?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
.articleBtn{background: #fff;display: inline-block;padding: 10px;font-weight: bold;border-radius: 8px 8px 0 0px;box-shadow: 0px -2px 1px 0px rgba(0,0,0,0.2);}
.editItems form{margin-top:0px;}
</style>
<div class="editItems">
    <h1><?=$site['title']?outDb($site['title']):"הוספת דף חדש"?></h1>
	<div class="articleBtn"><a href="../main/search/frame.php?pageID=<?=$searchPageID?>">דף חיפוש</a></div>
	<div class="articleBtn active"><a href="../categoryArticles/frame.php?searchPageID=<?=$searchPageID?>">כתבה דף חיפוש</a></div>



	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="inputLblWrap langsdom">
			<div class="labelTo">שפה</div>
			<?=LangList::html_select()?>
		</div>
		<div class="inputLblWrap langsdom domainsHide">
			<div class="labelTo">דומיין</div>
			<?=DomainList::html_select()?>
		</div>
		<input type="hidden" name="pageType" value="<?=$pageType?>">
		<div class="frm" >
		<?php
			foreach(DomainList::get() as $did => $dom){ 
				foreach(LangList::get() as $lid => $lang){ ?>
				<div class="domain" data-id="<?=$did?>">
					<div class="language" data-id="<?=$lid?>">
						<div class="inputLblWrap">
							<div class="switchTtl">מוצג</div>
							<label class="switch">
								<input type="checkbox" name="ifShow" value="1" <?=($siteLangs[$did][1]['status'] ? 'checked="checked"' : '')?> <?=($siteLangs[$did][1]['status']==1 && $id==0)?"checked":""?> />
								<span class="slider round"></span>
							</label>
						</div>
					</div>
				</div>
			<?php } } ?>
			<?php 
			foreach(DomainList::get() as $did => $dom){
				foreach(LangList::get() as $lid => $lang){ ?>
					<div class="domain" data-id="<?=$did?>">
						<div class="language" data-id="<?=$lid?>">
							<div class="inputLblWrap">
								<div class="labelTo">כותרת דף</div>
								<input type="text" placeholder="כותרת דף" name="mainPageTitle" value="<?=js_safe($siteLangs[$did][$lid]['title'])?>" />
							</div>
							<?php /*?>
							<div class="section txtarea big">
								<div class="inptLine">
									<div class="label noFloat">תאור קצר</div>
									<textarea class="" name="shortDesc"><?=js_safe($siteLangs[$did][$lid]['shortDesc'])?></textarea>
								</div>
							</div>
							<?php */ ?>
							<div class="section txtarea big">
								<div class="inptLine">
									<div class="label noFloat">טקסט</div>
									<textarea class="textEditor" name="html_text"><?=js_safe($siteLangs[$did][$lid]['text'])?></textarea>
								</div>
							</div>
						</div>
					</div>
			<?php } } ?>
			<?/*
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
						<img src="../../../gallery/<?=$site['picture']?>" style="width:100%">
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
					<option value="<?=$mainarea['main_areaID']?>" <?=$fieldData['city']==$mainarea['main_areaID']?"selected":""?>><?=$mainarea['TITLE']?></option>
					<?php } ?>
				</select>
			</div>

			<div class="inputLblWrap">
				<div class="labelTo">אזור</div>
				<select name="area">
					<option value="0">- - בחר אזור - -</option>
					<?php foreach($areas as $area) { ?>
					<option value="<?=$area['areaID']?>" <?=$fieldData['city']==$area['areaID']?"selected":""?>><?=$area['TITLE']?></option>
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
				<div class="catName"><?=$category['categoryName']?></div>
				<div class="checksWrap">
					<?php foreach($attributes[$category['categoryID']] as $attribute) { ?>
					<div class="checkLabel checkIb">
						<div class="checkBoxWrap">
							<input class="checkBoxGr" type="checkbox" name="attributes[]" <?=($facilites?(in_array($attribute['attrID'],$facilites)?"checked":""):"")?> value="<?=$attribute['attrID']?>" id="<?=$attribute['attrID']?>">
							<label for="<?=$attribute['attrID']?>"></label>
						</div>
						<label for="<?=$attribute['attrID']?>"><?=$attribute['defaultName']?></label>
					</div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
		*/?>
		<div class="mainSectionWrapper">
			<div class="sectionName">SEO</div>

			<?php foreach(DomainList::get() as $did => $dom){
					foreach(LangList::get() as $lid => $lang){ ?>
						<div class="domain" data-id="<?=$did?>">
							<div class="language" data-id="<?=$lid?>">
								<input type="hidden" name="seoid" value="<?=intval($seo[$did][$lid]['id'])?>">
								<input type="hidden" name="LEVEL4" value="<?=intval($seo[$did][$lid]['LEVEL4'])?>">
								<div class="inputLblWrap">
									<div class="labelTo">כותרת עמוד</div>
									<input type="text" placeholder="כותרת עמוד" name="seoTitle" value="<?=js_safe($seo[$did][$lid]['title'])?>" />
								</div>
								<div class="inputLblWrap">
									<div class="labelTo">H1</div>
									<input type="text" placeholder="H1" name="seoH1" value="<?=js_safe($seo[$did][$lid]['h1'])?>" />
								</div>
								<div class="section txtarea">
									<div class="inptLine">
										<div class="label">מילות מפתח</div>
										<textarea name="seoKeyword"><?=js_safe($seo[$did][$lid]['keywords'])?></textarea>
									</div>
								</div>
								<div class="section txtarea">
									<div class="inptLine">
										<div class="label">תאור דף</div>
										<textarea name="seoDesc"><?=js_safe($seo[$did][$lid]['description'])?></textarea>
									</div>
								</div>
								<?php /* ?>
								<div class="inputLblWrap">
									<div class="labelTo">קישור</div>
									<input type="text" placeholder="קישור" name="LEVEL2" value="<?=js_safe($seo[$did][$lid]['LEVEL2'])?>" />
								</div>
								<?php */ ?>
							</div>
						</div>
			<?php } } ?>
		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$site['id']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>

<script src="../../app/tinymce/tinymce.min.js"></script>
<script type="text/javascript">

$(function(){
    $.each({domain: <?=$domainID?>, language: <?=$langID?>}, function(cl, v){
        $('.' + cl).hide().each(function(){
            var id = $(this).data('id');
            $(this).find('input, select, textarea').each(function(){
                this.name = this.name + '[' + id + ']';
            });
        }).filter('[data-id="' + v + '"]').show();

        $('.' + cl + 'Selector').on('change', function(){
            $('.' + cl, $(this).data('cont')).hide().filter('[data-id="' + this.value + '"]').show();
        });
    });


});


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