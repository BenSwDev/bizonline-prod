<?php
include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "../../_globalFunction.php";
//LINE 52 REMOVED
const BASE_LANG_ID = 1;

$domainID = DomainList::active();
$langID   = LangList::active();

$pageID=intval($_GET['pageID']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
	
	    $data = typemap($_POST, [
            'TITLE'   => ['int' => 'string'],
            'html_text'   => ['int' => 'html'],
			'activeAutoSuggest' => 'int',
			'mar' => 'int'

        ]);
		
		
		//print_r($_POST);

		if (!$data['TITLE'][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');
		
		
        // main site data
        $siteData = [
            'TITLE' => $data['TITLE'][BASE_LANG_ID],
			'main_areaID' => $data['mar'],
			'activeAutoSuggest' => $data['activeAutoSuggest']?? 0,
			'picture' => $data['picture']
        ];
		//print_r($data);
		
		$photo = pictureUpload('picture',"../../../../gallery/");
		if($photo){
			$siteData['picture'] = $photo[0]['file']; 
		}
        if (!$pageID){      // opening new
            $pageID = udb::insert('areas', $siteData);
			foreach(LangList::get() as $lid => $lang){
				$names[$lid] = $data['TITLE'][$lid];
			}
			$newSerach =  new SearchFiller;
			//$newSerach->newArea($pageID, $names);
        } else {
            udb::update('areas', $siteData, '`areaID` = ' . $pageID);
        }

		// saving data per domain

		foreach(LangList::get() as $lid => $lang){
			udb::insert('areas_text', [
				'areaID'    => $pageID,
				'LangID'    => $lid,
				'TITLE'  => $data['TITLE'][$lid],
				'html_text'  => $data['html_text'][$lid]
			], true);
		}
	}

	

    catch (LocalException $e){
        // show error
    } ?>

	<script>//window.parent.location.reload(); //window.parent.closeTab();</script>
<?php

}

$que="SELECT `main_areaID`,`TITLE` FROM `main_areas` WHERE 1 ORDER BY `main_areaID`";
$main_areas= udb::key_row($que, "main_areaID" );

if ($pageID){
    $site = udb::single_row("SELECT * FROM `areas` WHERE `areaID`=".$pageID);
    $siteLangs = udb::key_row("SELECT * FROM `areas_text` WHERE `areaID` = " . $pageID, ['LangID']);

}

?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
.domainsHide, .langsdom {
    display: block !important;
}
.editItems form .section.txtarea.big {
    display: none !important;
	}
	.language {display: block !important;}
</style>
<div class="editItems">
    <h1><?=$site['TITLE']?outDb($site['TITLE']):"הוספת איזור חדש"?></h1>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
        <?=LangList::html_select()?>
	</div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
			<div class="inputLblWrap">
				<div class="switchTtl">מוצג בהצעה אוטומטית</div>
				<label class="switch">
					<input type="checkbox" name="activeAutoSuggest" value="1" <?=($site['activeAutoSuggest'] ? 'checked="checked"' : '')?> />
					<span class="slider round"></span>
				</label>
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">איזור ראשי</div>
				<select name="mar" id="mar">
					<option value="0">בחר...</option>
					<?
					foreach($main_areas as $main){ ?>
						<option <?=($site["main_areaID"]==$main["main_areaID"] ?"selected " : "")?>value="<?=$main['main_areaID']?>"><?=$main['TITLE']?></option>
					<?php }
					?>
				</select>
			</div>
			<div  class="inputLblWrap">
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
						<img src="/gallery/<?=$page['picture']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
			</div>
			<?php 
			foreach(LangList::get() as $lid => $lang){ ?>
				<div class="language" data-id="<?=$lid?>">
					<div class="inputLblWrap">
						<div class="labelTo">איזור</div>
						<input type="text" placeholder="איזור" name="TITLE" value="<?=js_safe($siteLangs[$lid]['TITLE'])?>" />
					</div>
					<div class="section txtarea big">
						<div class="inptLine">
							<div class="label noFloat">טקסט</div>
							<textarea class="textEditor" name="html_text"><?=outDb($siteLangs[$lid]['html_text'])?></textarea>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$site['areaID']?"שמור":"הוסף"?>" class="submit">
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


});




</script>