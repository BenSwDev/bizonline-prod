<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";

const BASE_LANG_ID = 1;


$langID   = LangList::active();
$domainID  = intval($_GET['domainID']);
$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['pageType']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;

include "../../seo/seo.php";
if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
	    $data = typemap($_POST, [
            'mainPageTitle'   => ['int' => 'string'],
            'shortDesc'   => ['int' => 'html'],
            'html_text'   => ['int' => 'html'],
            'html_text2'   => ['int' => 'html'],
            'html_text3'   => ['int' => 'html'],
			'!ifShow'    => 'int',
			'pageType'    => 'int',
            'addForm'  => ['int' => 'int'],
            'locDom' => 'int'
        ]);

		if (!$data['mainPageTitle'][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');


        // main site data
        $siteData = [
            'mainPageTitle' => $data['mainPageTitle'][BASE_LANG_ID],
            'ifShow' => $data['ifShow'] ?? 0,
            'mainPageType' => $data['pageType']
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

        udb::query("DELETE FROM `MainPages_text` WHERE `mainPageID` = " . $pageID);

        // saving data per domain
        foreach(LangList::get() as $lid => $lang){
            udb::insert('MainPages_text', [
                'mainPageID'    => $pageID,
                'domainID'  => $data['locDom'],
                'langID'    => $lid,
                'ifShow'   => $data['ifShow'] ?? 0,
                'mainPageTitle'  => $data['mainPageTitle'][$lid],
                'shortDesc'  => $data['shortDesc'][$lid],
                'addForm'  => $data['addForm'][$lid],
                'html_text'  => $data['html_text'][$lid],
                'html_text2'  => $data['html_text2'][$lid],
                'html_text3'  => $data['html_text3'][$lid]
            ], true);
        }


		$dataSeo = typemap($_POST, [
			'seoTitle'   =>['int' => 'string'],
			'seoH1'   => ['int' => 'string'],
			'seoKeyword'   => ['int' => 'string'],
			'seoDesc'   => ['int' => 'string'],
			'LEVEL2'   => ['int' => 'string']
		]);

		$dataSeo['ref']=$pageID;
		$dataSeo['table']="MainPages";

		$que = "SELECT `id`,`LEVEL2`,`domainID`,`langID` FROM alias_text WHERE `ref`=$pageID AND `table`='MainPages'" ;
		$checkId = udb::key_row($que, array('domainID','langID'));

        // temp solution need to replace whole thing
        $realDomain = $data['locDom'] ?: 1;
        udb::query("DELETE FROM `alias_text` WHERE `domainID` <> " . $realDomain . " AND `table` = 'MainPages' AND `ref` = " . $pageID);

		// saving data per domain
        foreach(LangList::get() as $lid => $lang){
            $did = $realDomain;

            $siteDataSeo = [
                'domainID'  => $did,
                'langID'    => $lid,
                'title'  => $dataSeo['seoTitle'][$lid]?$dataSeo['seoTitle'][$lid]:$data['mainPageTitle'][$lid],
                'h1'  => $dataSeo['seoH1'][$lid],
                'description'  => $dataSeo['seoDesc'][$lid],
                'keywords'  => $dataSeo['seoKeyword'][$lid],
                'ref'  => $dataSeo['ref'],
                'table'  => $dataSeo['table']
            ];

            $siteDataSeo['LEVEL1'] = globalLangSwitch($lid);
            $siteDataSeo['LEVEL2'] = $dataSeo['LEVEL2'][1] ? $dataSeo['LEVEL2'][1] : ($dataSeo['link'][$lid]?$dataSeo['link'][$lid]:($dataSeo['seoTitle'][$lid]?$dataSeo['seoTitle'][$lid]:$data['mainPageTitle'][$lid]));

            //$siteDataSeo['LEVEL2'] = $siteDataSeo['LEVEL2']?str_replace(' ', '_', $siteDataSeo['LEVEL2']).".html":"";
            $fileExt = "";
            //if($did != 10) $fileExt = ".html";
            $siteDataSeo['LEVEL2'] = $siteDataSeo['LEVEL2']? $siteDataSeo['LEVEL2'].$fileExt:"";


            if(!$checkId[$did]){
                udb::insert('alias_text', $siteDataSeo);
            }else{
                udb::update('alias_text', $siteDataSeo, "`domainID`=$did AND `langID`=$lid AND `ref`=".$dataSeo['ref']." AND `table`='".$dataSeo['table']."'");
            }
        }
	}



    catch (LocalException $e){
        // show error
    } ?>

	<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}

$site = [];

if ($pageID){
    $site = udb::single_row("SELECT * FROM `MainPages` WHERE `MainPageID`=".$pageID);
    $site['domainID'] = udb::single_value("SELECT `domainID` FROM `alias_text` WHERE `table` = 'MainPages' AND `ref` = " . $pageID);

    $siteLangs = udb::key_row("SELECT * FROM `MainPages_text` WHERE `domainID`=".$domainID." AND `MainPageID` = " . $pageID, 'langID');
}


$domainURL = udb::single_value("SELECT `domainURL` FROM `domains` WHERE `domainID` = " . $domainID);

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
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="pageType" value="<?=$pageType?>">
        <input type="hidden" name="locDom" value="<?=$domainID?>">
		<div class="frm" >
            <div class="inputLblWrap">
                <div class="switchTtl">מוצג</div>
                <label class="switch">
                    <input type="checkbox" name="ifShow" value="1" <?=($site['ifShow'] ? 'checked="checked"' : '')?> />
                    <span class="slider round"></span>
                </label>
            </div>

<?php
    foreach(LangList::get() as $lid => $lang){
?>
            <div class="language" data-id="<?=$lid?>">
                <div class="inputLblWrap">
                    <div class="labelTo">כותרת דף</div>
                    <input type="text" placeholder="כותרת דף" name="mainPageTitle" value="<?=js_safe($siteLangs[$lid]['mainPageTitle'])?>" />
                </div>
                <div class="inputLblWrap" style="display: none;">
                    <div class="labelTo">דומיין</div>

                </div>
                <div class="inputLblWrap">
                    <div class="labelTo">טופס</div>
                    <select name="addForm" class="locDom" >
                        <option value="0">ללא</option>
                        <option value="1" <?=$siteLangs[$lid]['addForm'] == 1 ? ' selected ' : '';?>>קשר</option>
                        <option value="2" <?=$siteLangs[$lid]['addForm'] == 2 ? ' selected ' : '';?>>הצטרפות לאתר</option>
                        </select>
                </div>
                <?php if($pageType != 2) { ?>
                <div class="section txtarea big">
                    <div class="inptLine">
                        <div class="label noFloat">תאור קצר</div>
                        <textarea class="" name="shortDesc"><?=$siteLangs[$lid]['shortDesc']?></textarea>
                    </div>
                </div>
                <?php } ?>
                <div class="section txtarea big">
                    <div class="inptLine">
                        <div class="label noFloat">טקסט</div>
                        <textarea class="textEditor" name="html_text"><?=$siteLangs[$lid]['html_text']?></textarea>
                    </div>
                </div>
                <?php if($pageType != 2) { ?>
                <div class="section txtarea big">
                    <div class="inptLine">
                        <div class="label noFloat">טקסט</div>
                        <textarea class="textEditor" name="html_text2"><?=$siteLangs[$lid]['html_text2']?></textarea>
                    </div>
                </div>
                <div class="section txtarea big">
                    <div class="inptLine">
                        <div class="label noFloat">טקסט</div>
                        <textarea class="textEditor" name="html_text3"><?=$siteLangs[$lid]['html_text3']?></textarea>
                    </div>
                </div>
                <?php } ?>
            </div>
<?php
    }
?>
			<?php if($pageType != 2) { ?>
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
<?php
    $seo = udb::key_row("SELECT * FROM `alias_text` WHERE `table` = 'MainPages' AND `ref` = '" . $pageID . "'", 'langID');
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

                    <div class="inputLblWrap">
                        <div class="labelTo">קישור</div>
                        <input type="text" placeholder="קישור" name="LEVEL2" value="<?=js_safe($seo[$lid]['LEVEL2'])?>" />
                    </div>

                </div>
                <?php } ?>
        </div>

        <?php } ?>
		<div style="clear:both;"></div>
		<?php if($pageID) { ?>
		<a class="showLinkSeo" href="https://www.
<?php
		$newLink = "";
		if($newLink = ActivePage::showAlias('MainPages', $pageID, 1, $domainID)){
			$newLink = str_replace("+","_",$newLink);
			$newLink = str_replace(" ","_",$newLink);
			echo $domainURL . $newLink;
        }

		?>" target="_blank">קישור : https://www.<?=($domainURL . $newLink)?></a>
		<?php } ?>
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