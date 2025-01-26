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
            'treatmentName'   => ['int' => 'string'],
            'treatmentDesc'   => ['int' => 'html'],
            'treatmentDesc2'   => ['int' => 'html']

        ]);
		if (!$data['treatmentName'][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');


        // main site data
        $siteData = [
            'treatmentName' => $data['treatmentName'][BASE_LANG_ID],
            'specialPage' => 1
          
        ];

		$photo = pictureUpload('picture',"../../../../gallery/");
		if($photo){
			$siteData["treatmentPic"] = $photo[0]['file'];
		}

        if (!$pageID){      // opening new
            $pageID = udb::insert('treatments', $siteData);
        } else {
            udb::update('treatments', $siteData, '`treatmentID` = ' . $pageID);
        }


		foreach(LangList::get() as $lid => $lang){
			udb::insert('treatmentsLangs', [
				'treatmentID'    => $pageID,
				'langID'    => $lid,
				'treatmentName'  => $data['treatmentName'][$lid],
				'treatmentDesc'  => $data['treatmentDesc'][$lid],
				'treatmentDesc2'  => $data['treatmentDesc2'][$lid]
			], true);
		}

		$photos = pictureUpload('images',"../../../../gallery/");
		if(isset($photos)){
			foreach($photos as $key=>$photo){	
				$fileArr=Array();
				$fileArr['src']=$photo['file'];
				$fileArr['table']="treatments";
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
    $site    = udb::single_row("SELECT * FROM treatments WHERE treatmentID=".$pageID);
    $siteLangs   = udb::key_row("SELECT * FROM `treatmentsLangs` WHERE `treatmentID` = " . $pageID, ['langID']);

	$que = "SELECT * FROM `files` WHERE `table`='treatments' AND `ref`=".$pageID;
	$pictures = udb::full_list($que);


}



?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$site['treatmentName']?outDb($site['treatmentName']):"הוספת טיפול חדש"?></h1>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
        <?=LangList::html_select()?>
	</div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
			<?php foreach(LangList::get() as $id => $lang){ ?>
			<div class="language" data-id="<?=$id?>">
				<div class="inputLblWrap">
					<div class="labelTo">כותרת</div>
					<input type="text" placeholder="כותרת" name="treatmentName" value="<?=js_safe($siteLangs[$id]['treatmentName'])?>" />
				</div>
			</div>
			<?php } ?>
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה: </div>
						<input type="file" name="picture" class="inpt" value="<?=$site['treatmentPic']?>">
					</div>
				</div>
				<?php if($site['treatmentPic']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../../../gallery/<?=$site['treatmentPic']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>

			<?php foreach(LangList::get() as $id => $lang){ ?>
			<div class="language" data-id="<?=$id?>">
				<div class="section txtarea big">
					<div class="inptLine">
						<div class="label noFloat">טקסט 1</div>
						<textarea class="textEditor" name="treatmentDesc"><?=outDb($siteLangs[$id]['treatmentDesc'])?></textarea>
					</div>
				</div>
				<div class="section txtarea big">
					<div class="inptLine">
						<div class="label noFloat">טקסט 2</div>
						<textarea class="textEditor" name="treatmentDesc2"><?=outDb($siteLangs[$id]['treatmentDesc2'])?></textarea>
					</div>
				</div>
			</div>
			<?php } ?>
			<div class="mainSectionWrapper">
				<div class="sectionName">גלריית תמונות</div>
				<div class="uploadLabelBtnWrap">
					<label for="imagesUpload" class="uploadLabelBtn">העלאת תמונות</label>
					<input type="file" id="imagesUpload" name="images[]" multiple style="visibility: hidden;">
				</div>
				<div class="imagWrap" style="height: auto;">
					<?php foreach ($pictures as $pic){ ?>
					<div class="imgGalFr" id="imageBox_<?=$pic['id']?>">
						<div class="pic"><a href="<?=picturePath($pic['src'],"../../../")?>" data-lightbox="image-1"><img src="<?=picturePath($pic['src'],"../../../")?>"></a></div>
						<div class="remove" onclick="removeThis('<?=$pic['id']?>')"><i class="fa fa-trash-o" aria-hidden="true"></i></div>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$site['treatmentID']?"שמור":"הוסף"?>" class="submit">
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