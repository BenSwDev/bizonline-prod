<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";

const BASE_LANG_ID = 1;

$domainID = DomainList::active();
$langID   = LangList::active();

$pageID=intval($_GET['pageID']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
	        $data = typemap($_POST, [
            'writerName'   => ['int' => 'string'],
            'writerDesc'   => ['int' => 'html'],
			'!active'    => 'int'

        ]);
		if (!$data['writerName'][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');


        // main site data
        $siteData = [
            'writerName' => $data['writerName'][BASE_LANG_ID],
            'active' => $data['active']
          
        ];

		$photo = pictureUpload('picture',"../../../../gallery/");
		if($photo){
			$siteData["writerPic"] = $photo[0]['file'];
		}

        if (!$pageID){      // opening new
            $pageID = udb::insert('reviewsWriters', $siteData);
        } else {
            udb::update('reviewsWriters', $siteData, '`writerID` = ' . $pageID);
        }

	
		foreach(LangList::get() as $lid => $lang){
			udb::insert('reviewsWritersLang', [
				'writerID'    => $pageID,
				'langID'    => $lid,
				'writerName'  => $data['writerName'][$lid],
				'writerDesc'  => $data['writerDesc'][$lid]
			], true);
		}

	}


    catch (LocalException $e){
        // show error
    } ?>

	<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}

if ($pageID){
    $site    = udb::single_row("SELECT * FROM reviewsWriters WHERE writerID=".$pageID);
    $siteLangs   = udb::key_row("SELECT * FROM `reviewsWritersLang` WHERE `writerID` = " . $pageID, ['langID']);
}


?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$site['writerName']?outDb($site['writerName']):"הוספת כותב חדש"?></h1>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
        <?=LangList::html_select()?>
	</div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
			<div class="inputLblWrap">
				<div class="switchTtl">מוצג</div>
				<label class="switch">
				  <input type="checkbox" name="active" value="1" <?=($site['active']==1 || !$pageID)?"checked":""?> />
				  <span class="slider round"></span>
				</label>
			</div>
			<?php foreach(LangList::get() as $id => $lang){ ?>
			<div class="language" data-id="<?=$id?>">
				<div class="inputLblWrap">
					<div class="labelTo">שם מלא</div>
					<input type="text" placeholder="שם מלא" name="writerName" value="<?=js_safe($siteLangs[$id]['writerName'])?>" />
				</div>
			</div>
			<?php } ?>
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה: </div>
						<input type="file" name="picture" class="inpt" value="<?=$site['writerPic']?>">
					</div>
				</div>
				<?php if($site['writerPic']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../../../gallery/<?=$site['writerPic']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>

			<?php foreach(LangList::get() as $id => $lang){ ?>
			<div class="language" data-id="<?=$id?>">
				<div class="section txtarea big">
					<div class="inptLine">
						<div class="label noFloat">תאור קצר</div>
						<textarea class="textEditor" name="writerDesc"><?=outDb($siteLangs[$id]['writerDesc'])?></textarea>
					</div>
				</div>
			</div>
			<?php } ?>

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