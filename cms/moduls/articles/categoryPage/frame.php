<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";

const BASE_LANG_ID = 1;

$domainID = DomainList::active();
$langID   = LangList::active();

$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['pageType']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;



if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
	
	    $data = typemap($_POST, [
            'mainPageTitle'   => ['int' => ['int' => 'string']],
            'shortDesc'   => ['int' => ['int' => 'html']],
            'html_text'   => ['int' => ['int' => 'html']],
			'!ifShow'    => ['int' => 'int'],
			'pageType'    => 'int'

        ]);

		if (!$data['mainPageTitle'][0][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');


        // main site data
        $siteData = [
            'mainPageTitle' => $data['mainPageTitle'][0][BASE_LANG_ID],
            'ifShow' => $data['ifShow'][0] ?? 0,
            'mainPageType' => 2,
            'include' => "inc_magazineSubject.php"
          
        ];
		
		$photo = pictureUpload('picture',"../../../../gallery/");
		if($photo){
			$siteData["picture"] = $photo[0]['file'];
		}
	
        if (!$pageID){      // opening new
            $pageID = udb::insert('MainPages', $siteData);
        } else {
            udb::update('MainPages', $siteData, '`mainPageID` = ' . $pageID);
        }

	    // saving data per domain
        foreach(DomainList::get(0) as $did => $dom){
			foreach(LangList::get() as $lid => $lang){
				udb::insert('MainPages_text', [
					'mainPageID'    => $pageID,
					'domainID'  => $did,
					'langID'    => $lid,
					'ifShow'   => $data['ifShow'][$did] ?? 0,
					'mainPageTitle'  => $data['mainPageTitle'][$did][$lid],
					'shortDesc'  => $data['shortDesc'][$did][$lid],
					'html_text'  => $data['html_text'][$did][$lid]
				], true);
				udb::insert('MainPages_text', [
					'mainPageID'    => $pageID,
					'domainID'  => 1,
					'langID'    => $lid,
					'ifShow'   => $data['ifShow'][$did] ?? 0,
					'mainPageTitle'  => $data['mainPageTitle'][$did][$lid],
					'shortDesc'  => $data['shortDesc'][$did][$lid],
					'html_text'  => $data['html_text'][$did][$lid]
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
		$dataSeo['table']="MainPages";



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
					'LEVEL2' => ($dataSeo['seoTitle'][$did][$lid]?$dataSeo['seoTitle'][$did][$lid]:$data['mainPageTitle'][$did][$lid]).".html"
				];


				if(!$checkId[$did]){
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
					'LEVEL2' => ($dataSeo['seoTitle'][$did][$lid]?$dataSeo['seoTitle'][$did][$lid]:$data['mainPageTitle'][$did][$lid]).".html"
				];


				if(!$checkId[1]){
					udb::insert('alias_text', $siteSeoData);
				}else{
					udb::update('alias_text', $siteSeoData, "`domainID`=1 AND `langID`=$lid AND `ref`=".$dataSeo['ref']." AND `table`='".$dataSeo['table']."'");
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
    $site = udb::single_row("SELECT * FROM `MainPages` WHERE `MainPageID`=".$pageID);
    $siteLangs = udb::key_row("SELECT * FROM `MainPages_text` WHERE `MainPageID` = " . $pageID, ['domainID','langID']);
	$que = "SELECT * FROM `alias_text` WHERE `ref`=$pageID AND `table`='MainPages'";
	$seo = udb::key_row($que, ['domainID','langID']);
	

}

?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$site['mainPageTitle']?outDb($site['mainPageTitle']):"הוספת דף חדש"?></h1>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
        <?=LangList::html_select()?>
	</div>
	<div class="inputLblWrap langsdom domainsHide">
		<div class="labelTo">דומיין</div>
        <?=DomainList::html_select()?>
	</div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="pageType" value="<?=$pageType?>">
		<div class="frm" >
		<?php
			foreach(DomainList::get() as $did => $dom){ ?>
				<div class="domain" data-id="<?=$did?>">
					<div class="inputLblWrap">
						<div class="switchTtl">מוצג</div>
						<label class="switch">
							<input type="checkbox" name="ifShow" value="1" <?=($siteLangs[$did][1]['ifShow'] ? 'checked="checked"' : '')?> <?=($siteLangs[$did][1]['ifShow']==1 && $id==0)?"checked":""?> />
							<span class="slider round"></span>
						</label>
					</div>
				</div>
			<?php } ?>
			<?php 
			foreach(DomainList::get() as $did => $dom){
				foreach(LangList::get() as $lid => $lang){ ?>
					<div class="domain" data-id="<?=$did?>">
						<div class="language" data-id="<?=$lid?>">
							<div class="inputLblWrap">
								<div class="labelTo">כותרת דף</div>
								<input type="text" placeholder="כותרת דף" name="mainPageTitle" value="<?=js_safe($siteLangs[$did][$lid]['mainPageTitle'])?>" />
							</div>
							<div class="section txtarea big">
								<div class="inptLine">
									<div class="label noFloat">תאור קצר</div>
									<textarea class="" name="shortDesc"><?=js_safe($siteLangs[$did][$lid]['shortDesc'])?></textarea>
								</div>
							</div>
							<div class="section txtarea big">
								<div class="inptLine">
									<div class="label noFloat">טקסט</div>
									<textarea class="textEditor" name="html_text"><?=js_safe($siteLangs[$did][$lid]['html_text'])?></textarea>
								</div>
							</div>
						</div>
					</div>
			<?php } } ?>
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
						<img src="../../../../gallery/<?=$site['picture']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
		</div>

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

<script src="../../../app/tinymce/tinymce.min.js"></script>
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