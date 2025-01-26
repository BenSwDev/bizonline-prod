<?php
include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "../../_globalFunction.php";

const BASE_LANG_ID = 1;

$domainID = DomainList::active();
$langID   = LangList::active();

$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['pageType']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


$que = "SELECT * FROM main_areas WHERE 1";
$mainAreas = udb::full_list($que);

$que = "SELECT * FROM areas WHERE 1 ORDER BY main_areaID";
$areas = udb::key_list($que,'main_areaID');

$que = "SELECT * FROM settlements WHERE 1 ORDER BY areaID";
$settlements = udb::key_list($que,'areaID');


if ('POST' == $_SERVER['REQUEST_METHOD']){

    try {
	
	    $data = typemap($_POST, [
            'restaurantTitle'   => ['int' => 'string'],
            'restaurantCity'   =>  'int',
            'restaurantArea'   =>  'int',
			'restaurantEmail'    => 'string',
			'restaurantWebUrl'    => 'string',
			'restaurantPhone1'    => 'string',
			'restaurantPhone2'    => 'string',
			'restaurantPhone3'    => 'string',
			'reastaurantFax'    => 'string',
			'!paid'    => 'int',
			'!ifShow'    => ['int' => 'int'],
			'restaurantDesc'    => ['int' => 'html'],
			'restaurantOwner'    => ['int' => 'string'],
			'restaurantAddress'    => ['int' => 'string'],
			'restaurantWebTitle'    => ['int' => 'string']

        ]);
		
		if (!$data['restaurantTitle'][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');


        // main site data
        $siteData = [
            'restaurantTitle' => $data['restaurantTitle'][BASE_LANG_ID],
            'restaurantCity'   => $data['restaurantCity'],
            'restaurantArea'   =>  $data['restaurantArea'],
			'restaurantEmail'    => $data['restaurantEmail'],
			'restaurantWebUrl'    => $data['restaurantWebUrl'],
			'restaurantPhone1'    => $data['restaurantPhone1'],
			'restaurantPhone2'    => $data['restaurantPhone2'],
			'restaurantPhone3'    => $data['restaurantPhone3'],
			'reastaurantFax'    => $data['reastaurantFax'],
			'paid'    => $data['paid'],
			'ifShow'    => $data['ifShow'][BASE_LANG_ID] ?? 0
          
        ];
		/*
		$photo = pictureUpload('picture',"../../../../gallery/");
		if($photo){
			$siteData["picture"] = $photo[0]['file'];
		}*/
	
        if (!$pageID){      // opening new
            $pageID = udb::insert('restaurants', $siteData);
        } else {
            udb::update('restaurants', $siteData, '`restaurantID` = ' . $pageID);
        }

	        // saving data per domain
    
		foreach(LangList::get() as $lid => $lang){
			udb::insert('restaurants_langs', [
				'restaurantID'    => $pageID,
				'langID'    => $lid,
				'ifShow'   => $data['ifShow'][$lid] ?? 0,
				'restaurantTitle'  => $data['restaurantTitle'][$lid],
				'restaurantDesc'  => $data['restaurantDesc'][$lid],
				'restaurantOwner'  => $data['restaurantOwner'][$lid],
				'restaurantAddress'  => $data['restaurantAddress'][$lid],
				'restaurantWebTitle'  => $data['restaurantWebTitle'][$lid]
			], true);
		}
		

		$dataSeo = typemap($_POST, [
			'seoTitle'   => ['int' => 'string'],
			'seoH1'		 => ['int' => 'string'],
			'seoKeyword' => ['int' => 'string'],
			'seoDesc'	 => ['int' => 'string']
		]);
		$dataSeo['ref']=$pageID;
		$dataSeo['table']="restaurants";

		$que = "SELECT `id` FROM alias_text WHERE `ref`=$pageID AND `table`='restaurants'" ;
		$checkId = udb::single_value($que);
		// saving data per lang
	
			foreach(LangList::get() as $lid => $lang){
				$siteDataSeo = 
				[
					'langID'    => $lid,
					'title'  => ($dataSeo['seoTitle'][$lid]?$dataSeo['seoTitle'][$lid]:$data['restaurantTitle'][$lid]),
					'h1'  => $dataSeo['seoH1'][$lid],
					'description'  => $dataSeo['seoDesc'][$lid],
					'keywords'  => $dataSeo['seoKeyword'][$lid],
					'ref'  => $dataSeo['ref'],
					'table'  => $dataSeo['table']
				];
			
				$siteDataSeo['LEVEL1'] = globalLangSwitch($lid);
				$siteDataSeo['LEVEL2'] = "rest";
				$siteDataSeo['LEVEL3'] = $pageID;
			
				if(!$checkId){
					udb::insert('alias_text', $siteDataSeo);
				}else{
					udb::update('alias_text', $siteDataSeo, "`langID`=$lid AND `ref`=".$dataSeo['ref']." AND `table`='".$dataSeo['table']."'");
				}

			}


			$photos = pictureUpload('images',"../../../gallery/");

			if(isset($photos)){
				foreach($photos as $key=>$photo){	
					$fileArr=Array();
					$fileArr['src']=$photo['file'];
					$fileArr['table']="restaurant";
					$fileArr['ref']=$pageID;
					$file = udb::insert("files", $fileArr);
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
    $site = udb::single_row("SELECT * FROM `restaurants` WHERE `restaurantID`=".$pageID);
    $siteLangs = udb::key_row("SELECT * FROM `restaurants_langs` WHERE `restaurantID` = " . $pageID, ['langID']);

	$que = "SELECT * FROM `files` WHERE `table`='restaurant' AND `ref`=".$pageID;
	$pictures = udb::full_list($que);


	$que = "SELECT * FROM `alias_text` WHERE `ref`=$pageID AND `table`='restaurants'";
	$seo = udb::key_row($que, ['langID']);
}

?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$site['restaurantTitle']?outDb($site['restaurantTitle']):"הוספת מסעדה חדשה"?></h1>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
        <?=LangList::html_select()?>
	</div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="pageType" value="<?=$pageType?>">
		<div class="frm" >
		<?php foreach(LangList::get() as $lid => $lang){ ?>
			<div class="language" data-id="<?=$lid?>">
				<div class="inputLblWrap">
					<div class="switchTtl">מוצג</div>
					<label class="switch">
						<input type="checkbox" name="ifShow" value="1" <?=($siteLangs[$lid]['ifShow'] ? 'checked="checked"' : '')?> />
						<span class="slider round"></span>
					</label>
				</div>
			</div>
			<?php } ?>
			<?php 
				foreach(LangList::get() as $lid => $lang){ ?>
					<div class="language" data-id="<?=$lid?>">
						<div class="inputLblWrap">
							<div class="labelTo">שם המסעדה</div>
							<input type="text" placeholder="שם המסעדה" name="restaurantTitle" value="<?=js_safe($siteLangs[$lid]['restaurantTitle'])?>" />
						</div>
						<div class="section txtarea big">
							<div class="inptLine">
								<div class="label noFloat">טקסט</div>
								<textarea class="textEditor" name="restaurantDesc"><?=outDb($siteLangs[$lid]['restaurantDesc'])?></textarea>
							</div>
						</div>
						<div class="inputLblWrap">
							<div class="labelTo">בעלים</div>
							<input type="text" placeholder="בעלים" name="restaurantOwner" value="<?=js_safe($siteLangs[$lid]['restaurantOwner'])?>" />
						</div>
						<div class="inputLblWrap">
							<div class="labelTo">כתובת</div>
							<input type="text" placeholder="כתובת" name="restaurantAddress" value="<?=js_safe($siteLangs[$lid]['restaurantAddress'])?>" />
						</div>
						<div class="inputLblWrap">
							<div class="labelTo">כותרת אתר</div>
							<input type="text" placeholder="כותרת אתר" name="restaurantWebTitle" value="<?=js_safe($siteLangs[$lid]['restaurantWebTitle'])?>" />
						</div>
					</div>
			<?php } ?>
					<div class="inputLblWrap">
						<div class="labelTo">טלפון 1</div>
						<input type="text" placeholder="טלפון 1" name="restaurantPhone1" value="<?=js_safe($site['restaurantPhone1'])?>" />
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">טלפון 2</div>
						<input type="text" placeholder="טלפון 2" name="restaurantPhone2" value="<?=js_safe($site['restaurantPhone2'])?>" />
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">טלפון 3</div>
						<input type="text" placeholder="טלפון 3" name="restaurantPhone3" value="<?=js_safe($site['restaurantPhone3'])?>" />
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">אתר URL</div>
						<input type="text" placeholder="אתר URL" name="restaurantWebUrl" value="<?=js_safe($site['restaurantWebUrl'])?>" />
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">מייל</div>
						<input type="text" placeholder="מייל" name="restaurantEmail" value="<?=js_safe($site['restaurantEmail'])?>" />
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">FAX</div>
						<input type="text" placeholder="FAX" name="restaurantEmail" value="<?=js_safe($site['restaurantEmail'])?>" />
					</div>
					<div class="inputLblWrap">
						<div class="switchTtl">משלם</div>
						<label class="switch">
							<input type="checkbox" name="paid" value="1" <?=($site['paid'] ? 'checked="checked"' : '')?> />
							<span class="slider round"></span>
						</label>
					</div>
					<div class="inputLblWrap">
						<div class="inputLblWrap">
							<div class="labelTo">יישוב</div>
							<select name="restaurantCity">
								<option value="0">-</option>
								<?php foreach($mainAreas as $mainArea) { ?>
									<option value="<?=$mainArea['main_areaID']?>" <?=($site['restaurantCity']==$mainArea['main_areaID']?'selected':"")?>><?=$mainArea['TITLE']?></option>
									<?php foreach($areas[$mainArea['main_areaID']] as $area) { ?>
									<option <?=($site['restaurantCity']==$area['areaID']?'selected':"")?> value="<?=$area['areaID']?>">-<?=$area['TITLE']?></option>

										<?php foreach($settlements[$area['areaID']] as $settlement) { ?>
										<option value="<?=$settlement['settlementID']?>" <?=($site['restaurantCity']==$settlement['settlementID']?'selected':"")?>>--<?=$settlement['TITLE']?></option>
										<?php } ?>

									<?php } ?>
								<?php } ?>
							</select>
						</div>
					</div>
					<div class="inputLblWrap">
						<div class="inputLblWrap">
							<div class="labelTo">site</div>
							<select name="restaurantArea">
								<option value="0">-</option>
								<?php foreach($mainAreas as $mainArea) { ?>
									<option value="<?=$mainArea['main_areaID']?>" <?=($site['restaurantArea']==$mainArea['main_areaID']?'selected':"")?>><?=$mainArea['TITLE']?></option>
									<?php foreach($areas[$mainArea['main_areaID']] as $area) { ?>
									<option <?=($site['restaurantArea']==$area['areaID']?'selected':"")?> value="<?=$area['areaID']?>">-<?=$area['TITLE']?></option>

										<?php foreach($settlements[$area['areaID']] as $settlement) { ?>
										<option value="<?=$settlement['settlementID']?>" <?=($site['restaurantArea']==$settlement['settlementID']?'selected':"")?>>--<?=$settlement['TITLE']?></option>
										<?php } ?>

									<?php } ?>
								<?php } ?>
							</select>
						</div>
					</div>



			<?php if(1==0) { ?>
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
						<img src="../../gallery/<?=$site['picture']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
			<?php } ?>
		</div>
		<div class="mainSectionWrapper">
			<div class="sectionName">גלריית תמונות</div>
			<div class="uploadLabelBtnWrap">
				<label for="imagesUpload" class="uploadLabelBtn">העלאת תמונות</label>
				<input type="file" id="imagesUpload" name="images[]" multiple style="visibility: hidden;">
			</div>
			<div class="imagWrap" style="height: auto;">
				<?php foreach ($pictures as $pic){ ?>
				<div class="imgGalFr" id="imageBox_<?=$pic['id']?>">
					<div class="pic"><a href="<?=picturePath($image['src'],"../../../")?>" data-lightbox="image-1"><img src="<?=picturePath($pic['src'],"../../../")?>"></a></div>
					<div class="remove" onclick="removeThis('<?=$pic['id']?>')"><i class="fa fa-trash-o" aria-hidden="true"></i></div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="mainSectionWrapper">
			<div class="sectionName">SEO</div>
			<?php 
				foreach(LangList::get() as $lid => $lang){ ?>
					<div class="language" data-id="<?=$lid?>">
						<div class="inputLblWrap">
							<div class="labelTo">כותרת עמוד</div>
							<input type="text" placeholder="כותרת עמוד" name="seoTitle" value="<?=outDb($seo[$lid]['title'])?>" />
						</div>
						<div class="inputLblWrap">
							<div class="labelTo">H1</div>
							<input type="text" placeholder="H1" name="seoH1" value="<?=outDb($seo[$lid]['h1'])?>" />
						</div>
						<div class="section txtarea">
							<div class="inptLine">
								<div class="label">מילות מפתח</div>
								<textarea name="seoKeyword"><?=outDb($seo[$lid]['keywords'])?></textarea>
							</div>
						</div>
						<div class="section txtarea">
							<div class="inptLine">
								<div class="label">תאור דף</div>
								<textarea name="seoDesc"><?=outDb($seo[$lid]['description'])?></textarea>
							</div>
						</div>
						<?php /* ?>
						<div class="inputLblWrap">
							<div class="labelTo">קישור</div>
							<input type="text" placeholder="קישור" name="LEVEL2" value="<?=js_safe($seo[$lid]['LEVEL2'])?>" />
						</div>
						<?php */ ?>
					</div>
			<?php } ?>
		</div>
		
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$site['restaurantID']?"שמור":"הוסף"?>" class="submit">
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

	function removeThis(id){
		if(confirm("האם אתה רוצה למחוק תמונה זו?")){
			$("#imageBox_"+id).remove();
			 $.ajax({
				url: 'js_del_picture.php',
				type: 'POST',
				data: {picID:id},
				async: false,
				success: function (myData) {
					console.log(myData);
				}
			});
		}
	}


</script>