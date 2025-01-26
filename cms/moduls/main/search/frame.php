<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";
include_once "../../../classes/class.ActivePage.php";

$pageID=intval($_GET['pageID']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;
$domainID=intval($_GET['domainID'])?intval($_GET['domainID']):1;
$currDomainID=intval($_GET['domainID'])?intval($_GET['domainID']):1;
$searchLevel2 = udb::single_value("SELECT `searchLevel2` FROM `domains` WHERE domainID=".$domainID);
if(!$searchLevel2) $searchLevel2 = 's';
$metaTitleExt = udb::single_value("SELECT `metaTitleExt` FROM `domains` WHERE domainID=".$domainID);
const BASE_LANG_ID = 1;
$langs = udb::full_list("SELECT `langID`, `LangName` FROM `language` WHERE 1");
function galGetUrl($uurl,$data = []){
    $url = $uurl;
    $curlSend = curl_init();
    curl_setopt($curlSend, CURLOPT_URL, $url);
    curl_setopt($curlSend, CURLOPT_POST, 1);
    curl_setopt($curlSend, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curlSend, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curlSend, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curlSend, CURLOPT_POSTFIELDS,
    http_build_query($data));
    $curlResult = curl_exec($curlSend);
    $curlStatus = curl_getinfo($curlSend, CURLINFO_HTTP_CODE);

    curl_close($curlSend);


    if ($curlStatus === 200)
        return $curlResult;
    else
        return $curlResult;

}
$domains = udb::key_row("SELECT `domainID`, `domainName` ,`domainURL` FROM `domains` WHERE  domainMenu=1", "domainID");

if('POST' == $_SERVER['REQUEST_METHOD']) {

    try {

        $data = typemap($_POST, [
			'!active'    => 'int',
            'title'		 => ['int' =>'string'],
            'barTitle'	 => ['int' =>'string'],
            'main_area'  => 'int',
            'area'		 => 'int',
            'city'		 => 'int',
            'maxPrice'		 => 'int',
			'holiday'	=> 'int',
			'type'		 => ['int' => 'int'],
          //  'dateChoose'   => 'int',
          //  'startDate'   => 'string',
          //  'endDate'   => 'string',
            'startPrice'   => 'string',
            'text'   => ['int' =>'html'],
            'attributes'   => ['int' => 'int'],
            'promoted' => 'string'
        ]);


		$jsonData = [];
		if($data['city']){
			$jsonData['marea'] = "";
			$jsonData['area'] = "";
			$jsonData['city'] = $data['city'];
		}
		else {
			if($data['area']){
				$jsonData['marea'] = "";
				$jsonData['area'] = $data['area'];
			}
			else {
				if($data['main_area']){
					$jsonData['marea'] = $data['main_area'];
				}
			}
		}
        if($data['promoted']) {
            $jsonData['promoted'] = $data['promoted'];
        }
		if($data['attributes']) {
			$jsonData['attr'] = $data['attributes'];
			ksort($jsonData['attr']);
		}

		if($data['type']) {
			$jsonData['roomTypes'] = $data['type'];
			ksort($jsonData['roomTypes']);
			ksort($jsonData['roomTypes']);
		}
        if($data['maxPrice']) {
            $jsonData['maxPrice'] = $data['maxPrice'];
        }
		if($data['holiday']){
			$jsonData['holiday'] = $data['holiday'];
		}
		/*if($data['dateChoose']==2){
			$jsonData['date1'] = $data['startDate'];
			$jsonData['date2'] = $data['endDate'];
		}*/

		$jsonData = array_filter($jsonData);
		ksort($jsonData);
		foreach ($jsonData as &$item) {
            is_numeric(key($item)) ? sort($item, SORT_NUMERIC) : ksort($item);   // sorting by key or (in case of regular array) by value
        }
        $searchLevel2 = udb::single_value("SELECT `searchLevel2` FROM `domains` WHERE domainID=".$domainID);
        $siteData = [
            'active'       => $data['active'],
			'domainID'    => $domainID,
			'title'    => $data['title'][BASE_LANG_ID],
            'barTitle'    => $data['barTitle'][BASE_LANG_ID],
			//'dateChoose'    => $data['dateChoose'],
			'data'    => json_encode($jsonData, JSON_NUMERIC_CHECK)

        ];


		$photo = pictureUpload('picture',"../../../../gallery/");
		if($photo){
			$siteData["picture"] = $photo[0]['file'];
		}

		$que = "SELECT `id` FROM search WHERE domainID=".$currDomainID." and `data`='".$siteData['data']."' ";
		$ifExists = udb::single_value($que);

		if($ifExists){
			$pageID = $ifExists;
		}

        $dataSeo = typemap($_POST, [
            'seoTitle'   => ['int' => 'string'],
            'seoH1'		 => ['int' => 'string'],
            'seoKeywords' => ['int' => 'string'],
            'seoDescription'	 => ['int' => 'string']
        ]);
		if($dataSeo[1]['seoH1']) {
            $siteData['title'] = $dataSeo[1]['seoH1'];
        }

        if (!$pageID){      // opening new site
            $pageID = udb::insert('search', $siteData);
        } else {
            udb::update('search', $siteData, '`id` = ' . $pageID);
        }

		foreach(LangList::get() as $lid => $lang){
			udb::insert('search_langs', [
				'id'    => $pageID,
				'langID'    => $lid,
				'title'   => $data['title'][$lid],
                'barTitle' => $data['barTitle'][$lid],
				'text'   => $data['text'][$lid]
			], true);
		}


		$dataSeo['ref']=$pageID;
		$dataSeo['table']="search";

		$que = "SELECT `id` FROM alias_text WHERE `ref`=$pageID AND `table`='search'" ;
		$checkId = udb::single_value($que);
		// saving data per lang

			foreach(LangList::get() as $lid => $lang){
				$siteDataSeo =
				[
					'langID'    => $lid,
					'domainID'    => $domainID,
					'title'  => (($dataSeo['seoTitle'][$lid])?$dataSeo['seoTitle'][$lid]:$data['title'][$lid]),
					'h1'  => $dataSeo['seoH1'][$lid],
					'description'  => $dataSeo['seoDescription'][$lid],
					'keywords'  => $dataSeo['seoKeywords'][$lid],
					'ref'  => $dataSeo['ref'],
					'table'  => $dataSeo['table']
				];

				$siteDataSeo['LEVEL1'] = globalLangSwitch($lid);

				$siteDataSeo['LEVEL2'] = $searchLevel2;//'s';//$siteDataSeo['title']? $siteDataSeo['title'].".html": ""; // removed by gal
				//TODO Level3  sett,area,mainarea or israel all english+id
				if($jsonData['city']){
					$titleEn = udb::single_value("select TITLE from settlements_text where LangID=2 and settlementID=".$jsonData['city']);
					if($titleEn){
						$siteDataSeo['LEVEL3'] = $titleEn; //new Line by gal
					}
					else {
						$siteDataSeo['LEVEL3'] = $pageID; //new Line by gal
					}


				}elseif($jsonData['area']){
					$titleEn = udb::single_value("select TITLE from areas_text where LangID=2 and areaID=".$jsonData['area']);
					if($titleEn){
						$siteDataSeo['LEVEL3'] = $titleEn; //new Line by gal
					}
					else {
						$siteDataSeo['LEVEL3'] = $pageID; //new Line by gal
					}
				} elseif($jsonData['marea']){
					$titleEn = udb::single_value("select TITLE from main_areas_text where LangID=2 and main_areaID=".$jsonData['marea']);
					if($titleEn){
						$siteDataSeo['LEVEL3'] = $titleEn; //new Line by gal
					}
					else {
						$siteDataSeo['LEVEL3'] = $pageID; //new Line by gal
					}
				}
				else {
					$siteDataSeo['LEVEL3'] = "israel";
				}
				$siteDataSeo['LEVEL4'] = $pageID; //new Line by gal


				if(!$checkId){
					udb::insert('alias_text', $siteDataSeo);
				}else{
					udb::update('alias_text', $siteDataSeo, "`langID`=$lid AND `ref`=".$dataSeo['ref']." AND `table`='".$dataSeo['table']."' and domainID=".$domainID);
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
    $Data    = udb::single_row("SELECT * FROM `search` WHERE id=".$pageID);
    $DataLangs = udb::key_row("SELECT * FROM `search_langs` WHERE `id` = " . $pageID, ['langID']);
    $titleSeo = null;
	if($Data){
	    if($domainID == 6)
            include "fuVii.php";
	    else
            include "fu.php";
        TempActivePage::changeDomoain($domainID);
        if(!$DataLangs[1]['title']) $title = searchPageName($data,0);
        if($domainID == 1 || $Data['active'] == 1) {
            $title = $DataLangs[1]['title'];
            $data = json_decode($Data['data'],true);
        }
        else {
            $data = json_decode($Data['data'],true);
            $title = $DataLangs[1]['title'];//searchPageName($data,1);
            $titleSeo = $title;
            $DataLangs[1]['title'] = $title;
        }
		    $seo = udb::key_row("SELECT * FROM `alias_text` WHERE `domainID`=".$domainID." AND `table`='search' AND ref=".$pageID,"langID");

            $title = searchPageName($data,0);
            switch($domainID) {
                case 6:
                    $seoDataFromDestionation = galGetUrl("https://www." . $domains[$domainID]['domainURL'] . "/viiapi.php?key=bizonline1025&type=searchseo&searchID=".$pageID , $data);
                    $seoDataFromDestionation = json_decode($seoDataFromDestionation,true);
                    break;
                case 105:
                case 106:
                case 107:
                case 108:
                case 111:
                case 121:
                case 122:
                case 123:
                case 10:
                    $seoDataFromDestionation = galGetUrl("https://" . $domains[$domainID]['domainURL'] ."/byhoursapi.php?key=bizonline1025&type=searchseo&searchID=".$pageID, $data);
                    $seoDataFromDestionation = json_decode($seoDataFromDestionation,true);
                    break;
                case 109:
                    $domains[$domainID]['domainURL'] = "www." . $domains[$domainID]['domainURL'];
                    $seoDataFromDestionation = galGetUrl("https://".$domains[$domainID]['domainURL']."/byhoursapi2.php?key=bizonline1025&type=searchseo&searchID=".$pageID, $data);
					$seoDataFromDestionation = json_decode($seoDataFromDestionation,true);
                    break;
                case 110:
                    $seoDataFromDestionation = galGetUrl("https://www.".$domains[$domainID]['domainURL']."/viiapi.php?key=bizonline1025&type=searchseo&searchID=".$pageID, $data);
                    $seoDataFromDestionation = json_decode($seoDataFromDestionation,true);
                    break;
                case 120:
                    $seoDataFromDestionation = galGetUrl("https://ssd:VillotSSD123@villot.c-ssd.com/viiapi.php?key=bizonline1025&type=searchseo&searchID=".$pageID, $data);
                    $seoDataFromDestionation = json_decode($seoDataFromDestionation,true);
                    break;
                case 125:
                    $seoDataFromDestionation = galGetUrl("https://ssd:ssdSSD123!!@loftplus.c-ssd.com/viiapi.php?key=bizonline1025&type=searchseo&searchID=".$pageID, $data);
                    $seoDataFromDestionation = json_decode($seoDataFromDestionation,true);
                    break;
            }



        if(!$seo ) {
            $seo[1]['title'] = outDb($DataLangs[1]['title']);
            $seo[1]['keywords'] = "";
            $seo[1]['description'] = "";

            if($titleSeo) {
                $seo[1]['h1'] = $seoDataFromDestionation['h1'] ?: $seoDataFromDestionation['title'];
            }
            else {
                $seo[1]['h1'] = outDb($DataLangs[$lid]['h1']) ?: outDb($DataLangs[$lid]['title']);
            }

        }
		$fieldData = json_decode($Data['data'],true);
		$facilites = $fieldData['attr'];
		$promoted = $fieldData['promoted'];
		if(!is_array($fieldData['roomTypes'])) {
			$temp = $fieldData['roomTypes'];
			$fieldData['roomTypes'] = [];
			$fieldData['roomTypes'][] = $temp;
		}

	}

}
else {
	$fieldData = [];
	$fieldData['roomTypes'] = [];
}


$domainID = DomainList::active($domainID);
$langID   = LangList::active($langID);

$mainareas = udb::full_list("SELECT `main_areaID`, `TITLE` FROM `main_areas` WHERE 1 ORDER BY `TITLE`");
$areas = udb::full_list("SELECT `areaID`, `TITLE` FROM `areas` WHERE 1 ORDER BY `TITLE`");
$cities = udb::full_list("SELECT * FROM `settlements` WHERE 1 ORDER BY `TITLE`");
$categories = udb::key_row("SELECT * FROM `attributes_categories` WHERE `active` = 1 AND `domainID` = " . $domainID . " ORDER BY showOrder" , 'categoryID');
$attributes = udb::key_row("SELECT d.*, a.defaultName FROM `attributes` AS `a` INNER JOIN `attributes_domains` AS `d` USING(`attrID`) WHERE d.active = 1 ORDER BY showOrder" , ['categoryID', 'attrID']);
$types = udb::full_list("SELECT * FROM `roomTypes` WHERE 1");

$que="SELECT * FROM `holidays` WHERE 1 ORDER BY `dateStart`";
$hot_periods = udb::full_list($que);

$domainList = udb::key_row("select * from domains","domainID");



?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
.articleBtn{background: #fff;display: inline-block;padding: 10px;font-weight: bold;border-radius: 8px 8px 0 0; box-shadow: 0 -2px 1px 0 rgba(0,0,0,0.2);}
.editItems form{margin-top:0;}
</style>
<div class="editItems">
    <h1><?=$Data['title']?outDb($Data['title']):"הוספת דף חדש"?></h1>
	<?php if($pageID) { ?>
	<div class="articleBtn active"><a href="../../main/search/frame.php?pageID=<?=$pageID?>">דף חיפוש</a></div>
	<!--<div class="articleBtn"><a href="../../categoryArticles/frame.php?searchPageID=<?=$pageID?>">כתבה דף חיפוש</a></div>-->

	<?php } ?>

	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="inputLblWrap langsdom">
			<div class="labelTo">שפה</div>
			<?=LangList::html_select()?>
		</div>
		<div class="frm" >
			<div class="inputLblWrap">
				<div class="switchTtl">מוצג</div>
				<label class="switch">
				  <input type="checkbox" name="active" value="1" <?=($Data['active']==1)?"checked":""?> />
				  <span class="slider round"></span>
				</label>
			</div>
			<?php
			foreach(LangList::get() as $lid => $lang){
                if(!$DataLangs[$lid]['title']) $DataLangs[$lid]['title'] = searchPageName($data,0);
                ?>
			<div class="language" data-id="<?=$lid?>">
				<div class="inputLblWrap" >
					<div class="labelTo">כותרת</div>
                    <span><?=outDb($DataLangs[$lid]['title'])?></span>
					<input type="text" value="<?=outDb($DataLangs[$lid]['title'])?>" name="title" style="display:none;"/>
				</div>
			</div>
            <!--<div class="language" data-id="<?=$lid?>">
				<div class="inputLblWrap">
					<div class="labelTo">כותרת בר</div>
					<input type="text" value="<?=outDb($DataLangs[$lid]['barTitle'])?>" name="barTitle"/>
				</div>
			</div>-->
			<?php } ?>
			<div style="clear:both;"></div>
			<div class="inputLblWrap">
				<div class="labelTo">אזור ראשי</div>
				<select name="main_area">
					<option value="0">- - בחר אזור ראשי - -</option>
					<?php foreach($mainareas as $mainarea) { ?>
					<option value="<?=$mainarea['main_areaID']?>" <?=$fieldData['marea']==$mainarea['main_areaID']?"selected":""?>><?=$mainarea['TITLE']?></option>
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
				<select name="type[]" title="סוג" multiple style="height:70px">
					<option value="0">סוג</option>
					<?php foreach($types as $type) { ?>
					<option value="<?=$type['id']?>" <?=(in_array($type['id'],$fieldData['roomTypes'])) ?"selected":""
					?>><?=$type['roomType']?></option>
					<?php } ?>
				</select>
			</div>
            <div class="inputLblWrap">
                <div class="labelTo">עד מחיר</div>
                <input type="text" name="maxPrice" value="<?=intval($fieldData['maxPrice']) ?:''; ?>">
            </div>
			<?php /*
			<div class="sepLineWrap">
				<div class="inputLblWrap">
					<div class="radioWrap">
						<input type="radio" name="dateChoose"  onclick="showDates(0)"  <?=($Data['dateChoose']==0?"checked":"")?> value="0" id="radio1">
						<label for="radio1">ללא תאריך</label>
					</div>

				</div>
				<div class="inputLblWrap">
					<div class="radioWrap">
						<input type="radio" onclick="showDates(1)" name="dateChoose" <?=($Data['dateChoose']==2?"checked":"")?> value="2" id="radio3">
						<label for="radio3">בחירת תאריכים</label>
					</div>
				</div>
			</div>
			<div class="sepLineWrap" style="<?=$Data['dateChoose']==2?"display:block":"display:none"?>" id="datesCh">
				<div class="inputLblWrap">
					<div class="labelTo">תאריך התחלה</div>
					<input type="text" value="<?=$fieldData['date1']?>" name="startDate" class="datePick" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">תאריך סיום</div>
					<input type="text" value="<?=$fieldData['date2']?>" name="endDate" class="datePick" />
				</div>
			</div>

			<div class="sepLineWrap">
				<div class="inputLblWrap">
					<div class="labelTo">ממחיר</div>
					<input type="text" value="<?=$fieldData['startPrice']?>" name="startPrice"/>
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">עד מחיר</div>
					<input type="text" value="<?=$fieldData['endPrice']?>" name="endPrice" />
				</div>
			</div>
			*/?>
			<div class="inputLblWrap">
				<div class="labelTo">תקופה חמה</div>
				<select name="holiday" title="תקופה חמה">
					<option value="0">בחר תקופה</option>
					<?php foreach($hot_periods as $type) { ?>
					<option value="<?=$type['holidayID']?>" <?=(intval($type['holidayID']) == intval($fieldData['holiday'])) ?"selected":""
					?>><?=$type['holidayName']?>  <?=date("d/m/y",strtotime($type['dateStart']))?> - <?=date("d/m/y",strtotime($type['dateEnd']))?></option>
					<?php } ?>
				</select>
			</div>
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה: </div>
						<input type="file" name="picture" class="inpt" value="<?=$Data['picture']?>">
					</div>
				</div>
				<?php if($Data['picture']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../../../gallery/<?=$Data['picture']?>" style="max-width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
            <div class="catName">מקודמים</div>
            <div class="checksWrap">
                <div class="checkLabel checkIb">
                    <div class="checkBoxWrap">
                        <input class="checkBoxGr" type="checkbox" name="promoted" id="hotest" value="hotest" <?=$promoted == 'hotest' ? ' checked ' : '';?> >
                        <label for="hotest"></label>
                    </div>
                    <label for="promoted">הכי חמים</label>
                </div>
            </div>

			<input type="hidden" name="facilities" value="">
			<?php foreach($categories as $category) { ?>
				<div class="catName"><?=$category['categoryName']?></div>
				<div class="checksWrap">
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
			<?php
			foreach(LangList::get() as $lid => $lang){ ?>
				<div class="language" data-id="<?=$lid?>">
					<div class="section txtarea big">
						<div class="label">טקסט</div>
						<textarea name="text" id="text<?=$lid?>" class="textEditor"><?=$DataLangs[$lid]['text']?></textarea>
					</div>
				</div>
			<?php } ?>

			<div class="seoSection">
				<div class="miniTitle">עריכת SEO</div>
				<?php
				foreach(LangList::get() as $lid => $lang) { ?>
				<div class="language" data-id="<?=$lid?>">
                    <?php
                    $showTitle = htmlspecialchars($seo[$lid]['title'], ENT_QUOTES | ENT_XHTML, 'UTF-8', false);
                    $showTitle = str_replace(" - " . $metaTitleExt,"",$showTitle);
                    $showTitle = str_replace(" - צימר קארד","",$showTitle);
                    $showTitle = str_replace("- צימר קארד","",$showTitle);
                    $showTitle = str_replace(",",", ",$showTitle);
                    $showTitle = preg_replace('/\s/', ' ', $showTitle);
                    $seoTitleshow = $seoDataFromDestionation['title'];
                    $seoTitleshow = str_replace(" - " . $metaTitleExt,"",$seoTitleshow);
                    $seoTitleshow = str_replace(" - צימר קארד","",$seoTitleshow);
                    $seoTitleshow = str_replace("- צימר קארד","",$seoTitleshow);
                    $seoTitleshow = str_replace(",",", ",$seoTitleshow);
                    $seoTitleshow = preg_replace('/\s/', ' ', $seoTitleshow);
                    ?>
					<div class="section">
						<div class="inptLine">
							<div class="label">כותרת עמוד</div>
							<input type="text" value='<?=$showTitle?>' name="seoTitle" class="inpt">
                            <div onclick="$(this).parent().find('input').val($(this).html())" title="לחץ להעתקה" style="max-height:120px;overflow:auto;background:#ff;border:1px #ccc solid;padding:10px;cursor:pointer"><?=$seoTitleshow?></div>
						</div>
					</div><?php
                    $showh1 = htmlspecialchars($seo[$lid]['h1'], ENT_QUOTES | ENT_XHTML, 'UTF-8', false);
                    $showh1 = str_replace(" - " . $metaTitleExt,"",trim($showh1));
                    $showh1 = str_replace(" - צימר קארד","",$showh1);
                    $showh1 = str_replace("- צימר קארד","",$showh1);
                    $showh1 = str_replace(",",", ",$showh1);
                    $showh1 = preg_replace('/\s/', ' ', $showh1);
                    ?>
					<div class="section">
						<div class="inptLine">
							<div class="label">H1</div>
							<input type="text" value='<?=$showh1?>' name="seoH1" class="inpt">
						</div>
					</div>
					<div style="clear:both;"></div>
					<div class="section txtarea"><?
                        $keyWords = htmlspecialchars($seo[$lid]['keywords'], ENT_QUOTES | ENT_XHTML, 'UTF-8', false);
                        $keyWords= preg_replace('/\s/', ' ', $keyWords);
                        $keyWords= str_replace(' ,', ',', $keyWords);
                        $seokeys = $seoDataFromDestionation['autokeywords'] ? $seoDataFromDestionation['autokeywords'] : $seoDataFromDestionation['keywords'];
                        $seokeys= str_replace(' ,', ',', $seokeys);
                        $seokeys= preg_replace('/\s/', ' ', $seokeys);
                        ?>
						<div class="label">Keywords:</div>
						<textarea name="seoKeywords"><?=$keyWords?></textarea>
                        <div onclick="$(this).parent().find('textarea').html($(this).html())" title="לחץ להעתקה" style="max-height:120px;overflow:auto;background:#ff;border:1px #ccc solid;padding:10px;cursor:pointer"><?=$seokeys?></div>
					</div>
					<div class="section txtarea"><?php
                        $description = htmlspecialchars($seo[$lid]['description'], ENT_QUOTES | ENT_XHTML, 'UTF-8', false);
                        $description= preg_replace('/\s/', ' ', $description);
                        $seoDesc = $seoDataFromDestionation['autodescription'] ? $seoDataFromDestionation['autodescription'] : $seoDataFromDestionation['description'];
                        $seoDesc= preg_replace('/\s/', ' ', $seoDesc);
                        ?>
						<div class="label">Description:</div>
						<textarea name="seoDescription"><?=$description?></textarea>
                        <div onclick="$(this).parent().find('textarea').html($(this).html())" title="לחץ להעתקה" style="max-height:120px;overflow:auto;background:#ff;border:1px #ccc solid;padding:10px;cursor:pointer"><?=$seoDesc?></div>
					</div>
					<div><?php
                    if($pageID) {
                        $newLink = "";
                        $baseUrl = $domainList[$currDomainID]['domainURL'];
                        if($currDomainID == 110) {
                            $baseUrl = "www.".$baseUrl;
                        }
                        if(ActivePage::showAlias('search', $pageID , $langID , $currDomainID)){
                            $newLink = $baseUrl . str_replace("+","_",ActivePage::showAlias('search', $pageID, $langID , $currDomainID));
                            if(strlen(str_replace($baseUrl,"",$newLink)) > 4) {

                                if($domainList[$currDomainID]['searchNumberExt']) {
                                    $newLinkArray = explode("/",$newLink);
                                    $newLinkArray[count($newLinkArray)-1] = $domainList[$currDomainID]['searchNumberExt'] .$newLinkArray[count($newLinkArray)-1];
                                    $newLink = implode("/",$newLinkArray);
                                }

                                echo '<a class="showLinkSeo" href="https://'.$newLink.'" target="_blank">קישור : https://'.$newLink.'</a>';
                            }

                         } ?>
                    <?php } ?></div>
                </div>
              <?php } ?>
			</div>
		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$pageID?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>

<!-- <script src="../../../app/tinymce/tinymce.min.js"></script> -->
<script src="/ckeditor/ckeditor.js?v=<?=time()?>"></script>
	<script>
        if ( CKEDITOR.env.ie && CKEDITOR.env.version < 9 )
	CKEDITOR.tools.enableHtml5Elements( document );

	CKEDITOR.config.height = 150;
CKEDITOR.config.width = 'auto';

var initSample = ( function() {
	var wysiwygareaAvailable = isWysiwygareaAvailable();

	return function() {
			CKEDITOR.replace( 'text1' );
		
	};

	function isWysiwygareaAvailable() {
		if ( CKEDITOR.revision == ( '%RE' + 'V%' ) ) {
			return true;
		}
		return !!CKEDITOR.plugins.get( 'wysiwygarea' );
	}
} )();


document.addEventListener("DOMContentLoaded", function(event) {
	initSample();
});


</script>


<script type="text/javascript">


	function showDates(num){

		if(num){
			$('#datesCh').show();
		}
		else{
			$('#datesCh').hide();
		}
	}

$(function(){

    $.each({language: <?=$langID?>}, function(cl, v){

        $('.' + cl).hide().each(function(){
            var id = $(this).data('id');
            console.log(id);
            $(this).find('input, select, textarea').each(function(){
                this.name = this.name + '[' + id + ']';
            });
        }).filter('[data-id="' + v + '"]').show();

        $('.' + cl + 'Selector').on('change', function(){
            $('.' + cl, $(this).data('cont')).hide().filter('[data-id="' + this.value + '"]').show();
        });
    });



	// tinymce.init({
	//   selector: 'textarea.textEditor' ,
	//   height: 300,
	//  plugins: [
	// 	"advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
	// 	"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
	// 	"table contextmenu directionality emoticons template textcolor paste  textcolor colorpicker textpattern"
	//   ],
	//   directionality :"rtl",
	//   toolbar: "ltr rtl",
	//   fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
	//   toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
	//   toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor",
	//   toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking template pagebreak restoredraft"

	// });

	//facilities save to one input
	var hidenInputFac = $("input[name='facilities']");
	var facilArr = [];
	if(hidenInputFac.val()){
		facilArr = [hidenInputFac.val()];
	}
	$('.checkBoxGr').bind('change', function(){

		if($(this).is(':checked')){
			facilArr.push($(this).attr('id'));
		}
		else{
			facilArr.splice($.inArray($(this).attr('id')), 1 );
		}
		hidenInputFac.val(facilArr);
	});

	$('.checkBoxGr').each(function(){

		if($(this).is(':checked')){
			facilArr.push($(this).attr('id'));
		}
		hidenInputFac.val(facilArr);
	});

	$(".datePick").datepicker({
		"minDate":0
	});

});

</script>
