<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";

const BASE_LANG_ID = 1;

$adID = intval($_GET['pageID']);
$siteID = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);
$siteName = $_GET['siteName'];



if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {

        $data = typemap($_POST, [
            '!ifShow'    => 'int',
            'title'   => ['int' => 'string'],
			'text'		 => ['int' => 'html'],
			'desc'		 => ['int' => 'html'],
			'langID'	 => 'int'

        ]);


		$siteData = [
			'siteID'    => $siteID,
			'active'	=> $data['ifShow'],
			'adTItle'		=> $data['title'][BASE_LANG_ID]

		];

		$photo = pictureUpload('picture',"../../../../gallery/");
		if($photo){
			$siteData["adPic"] = $photo[0]['file'];
		}


		if(!$adID){
			$adID = udb::insert('sites_article_ads', $siteData, true);
		}
		else{
		
			udb::update('sites_article_ads', $siteData,"adID=".$adID);
		}
		// saving data per domain / language
		foreach(LangList::get() as $lid => $lang){
			// inserting/updating data in domains table
			udb::insert('sites_article_ads_langs', [
				'adID'    => $adID,
				'langID'    => $lid,
				'adTitle' => $data['title'][$lid],
				'adDesc' => $data['desc'][$lid],
				'adText' => $data['text'][$lid]
			], true);
		}
    }
    catch (LocalException $e){
        // show error
    } ?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}

$data = [];
$domainID = DomainList::active();
$langID   = LangList::active();

if ($adID){
    $adData  = udb::single_row("SELECT * FROM `sites_article_ads` WHERE `adID` = " . $adID);
	$adLengsData =udb::key_row("SELECT * FROM `sites_article_ads_langs` WHERE `adID` = " . $adID, ['langID']);
}



?>

<style type="text/css">
	.sectionWrap .selectWrap{width: 22%;}
	.inputLblWrap{margin: 1%;}
</style>

<div class="editItems">
	<div class="frameContent">
		<div class="siteMainTitle"><?=$siteName?></div>
		<div class="inputLblWrap langsdom">
			<div class="labelTo">שפה</div>
			<?=LangList::html_select()?>
		</div>

		<form action="" method="post" enctype="multipart/form-data">
			<div class="inputLblWrap">
				<div class="switchTtl">מוצג</div>
				<label class="switch">
				  <input type="checkbox" name="ifShow" value="1" <?=($adData['active']==1)?"checked":""?> <?=($adID==0)?"checked":""?>/>
				  <span class="slider round"></span>
				</label>
			</div>
			<?php 
			foreach(LangList::get() as $lid => $lang){ ?>
				<div class="language" data-id="<?=$lid?>">
					<div class="inputLblWrap">
						<div class="labelTo">כותרת</div>
						<input type="text" placeholder="כותרת" value="<?=js_safe($adLengsData[$lid]['adTitle'])?>" name="title" />
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">תיאור תמונה</div>
						<input type="text" placeholder="תיאור תמונה" value="<?=js_safe($adLengsData[$lid]['adDesc'])?>" name="desc" />
					</div>
					<div class="sectionWrap">
						<div class="section txtarea big">
							<div class="label">טקסט</div>
							<textarea name="text" class="textEditor"><?=$adLengsData[$lid]['adText']?></textarea>
						</div>
					</div>
				</div>
			<?php } ?>
			<!-- <div class="tokensBox">
				<table class="sticky-table">
				 <thead><tr><th>Token</th><th>שם</th> </tr></thead>
					<tbody>
					 <tr class="odd"><td><a href="#">[had-parent:link]</a></td><td>Node Link</td> </tr>
					 <tr class="even"><td><a href="#">[had-parent:settlement]</a></td><td>ישוב</td> </tr>
					 <tr class="odd"><td><a href="#">[had-parent:area]</a></td><td>Area</td> </tr>
					 <tr class="even"><td><a href="#">[had-parent:zone]</a></td><td>אזור</td> </tr>
					 <tr class="odd"><td><a href="#">[had-parent:score]</a></td><td>ציון</td> </tr>
					 <tr class="even"><td><a href="#">[had-parent:price-range]</a></td><td>טווח מחירים</td> </tr>
					 <tr class="odd"><td><a href="#">[had-parent:price-min]</a></td><td>Price Min</td> </tr>
					 <tr class="even"><td><a href="#">[had-parent:price-max]</a></td><td>Price Max</td> </tr>
					</tbody>
				</table>
			</div> -->
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה</div>
						<input type="file" name="picture" class="inpt" value="<?=$adData['adPic']?>">
					</div>
				</div>
				<?php if($adData['adPic']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="<?=picturePath($adData['adPic'],"../../../../")?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>			
			<div class="clear"></div>
			<input type="submit" value="שמור" class="submit">
		</form>	
	</div>
</div>



<script src="../../../app/tinymce/tinymce.min.js"></script>
<script>


	$(function(){

	tinymce.init({
	  selector: 'textarea.textEditor' ,
	  height: 300,  
	 plugins: [
		"advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
		"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
		"table contextmenu directionality emoticons template textcolor paste  textcolor colorpicker textpattern"
	  ],
	  fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
	  toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
	  toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor ",
	  toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking template pagebreak restoredraft",
	  toolbar4: "NodeLink City Area MainArea Score priceRange priceMin priceMax",
	  setup: function (editor) {
		editor.addButton('NodeLink', {
		  text: "Node Link",
		  onclick: function () {
			 editor.insertContent('[had-parent:link]');
		  }
		});
		editor.addButton('City', {
		  text: "City",
		  onclick: function () {
			 editor.insertContent('[had-parent:settlement]');
		  }
		});
		editor.addButton('Area', {
		  text: "Area",
		  onclick: function () {
			 editor.insertContent('[had-parent:area]');
		  }
		});
		editor.addButton('MainArea', {
		  text: "Main Area",
		  onclick: function () {
			 editor.insertContent('[had-parent:zone]');
		  }
		});
		editor.addButton('Score', {
		  text: "Score",
		  onclick: function () {
			 editor.insertContent('[had-parent:score]');
		  }
		});
		editor.addButton('priceRange', {
		  text: "Price Range",
		  onclick: function () {
			 editor.insertContent('[had-parent:price-range]');
		  }
		});
		editor.addButton('priceMin', {
		  text: "Price Min",
		  onclick: function () {
			 editor.insertContent('[had-parent:price-min]');
		  }
		});
		editor.addButton('priceMax', {
		  text: "Price Max",
		  onclick: function () {
			 editor.insertContent('[had-parent:price-max]');
		  }
		});
	  }

	});
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


	$(".datePick").datepicker({
		format:"dd/mm/yyyy",
		changeMonth:true
	});

        $('.sticky-table a').unbind().click(function(){

            var textarea = $(this).closest('.textEditor').find('textarea, :input');
            if(textarea.length){
              start = textarea[0].selectionStart,
              text = textarea.val();
              text = text.substr(0,start) + $(this).text() + text.substr(start);
              textarea.val(text);
              textarea.focus()
            } 
		});

});
</script>