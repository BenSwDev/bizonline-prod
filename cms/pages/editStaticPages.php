<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if('POST' == $_SERVER['REQUEST_METHOD']) {
	
	if($_POST['facilites']){
		$facilites = implode(",",$_POST['facilites']);
	}
	$cp=Array();
	$cp['MainPageTitle'] = inDb($_POST['MainPageTitle']);
	$cp["ifShow"] = intval($_POST['ifShow'])?"1":"0";
	$cp["showInHome"] = intval($_POST['showInHome'])?"1":"0";
	$cp["ShortDesc"] = inDb($_POST['ShortDesc']);
	$cp["link"] = inDb($_POST['link']);
	$cp["tags"] = $facilites;
	$cp["html_text"] = ($_POST['html_text'] == "<br>") ? "" : $_POST['html_text'];
	$cp["MainPageType"] = intval($pageType);
	$cp["LangID"] = intval($_POST['LangID']);

	if($_POST['articleDate']){
	$date=explode("/", $_POST['articleDate']);
	$date=$date[2]."-".$date[1]."-".$date[0];
	$cp["articleDate"] = $date;
	}

/*	if($_POST['attrCity']){
		$que="SELECT TITLE FROM settlements WHERE settlementID=".intval($_POST['attrCity'])." ";
		$city=udb::single_row($que);
	}

	if($_POST['attrAddress']){
		$location=getLocationNumbers(inDB($_POST['attrAddress']).($city['TITLE']?", ".$city['TITLE']:""));
		$cp["gps_lat"] = $location['lat'];
		$cp['gps_long'] = $location['long'];
	} else if($city) {
		$location=getLocationNumbers(inDB($city['TITLE']));
		$cp["gps_lat"] = $location['lat'];
		$cp['gps_long'] = $location['long'];
	}
	
	$cp["attrAddress"] = inDb($_POST['attrAddress']);
	$cp["attrWebsite"] = inDb($_POST['attrWebsite']);
	$cp["attrPhone"] = inDb($_POST['attrPhone']);
	$cp["attrType"] = intval($_POST['attrType']);
	$cp["attrCity"] = intval($_POST['attrCity']);
	$cp["periodPage"] = intval($_POST['periodPage']);

*/


	$photo = pictureUpload('picture',"../../gallery/");
	if($photo){
		$cp["picture"] = $photo[0]['file'];
	}
	$photo2 = pictureUpload('picture2',"../../gallery/");
	if($photo2){
		$cp["picture2"] = $photo2[0]['file'];
	}

	if($pageID){
		udb::update("MainPages", $cp, "MainPageID =".$pageID);
	} else {
		$cp["createDay"] = date("Y-m-d");
		$pageID = udb::insert("MainPages", $cp);
	}
	if(isset($_POST['lang'])){
		foreach($_POST['lang'] as $lng=>$val){
			$cpLang = Array();
			$cpLang['MainPageID'] = $pageID;
			$cpLang['LangID'] = $lng;
			$cpLang['MainPageTitle'] = $_POST['lang_MainPageTitle'][$lng];
			$cpLang['ShortDesc'] = $_POST['lang_ShortDesc'][$lng];
			$cpLang['html_text'] = $_POST['lang_html_text'][$lng];
			$cpLang["ifShow"] = intval($_POST['lang_ifShow'])?"1":"0";

			$que="SELECT MainPageID, LangID, MainPageTitle FROM MainPages_text WHERE MainPageID=".$pageID." AND LangID=".$lng;
			$checkTest = udb::single_row($que);
			if($checkTest){
				udb::update("MainPages_text", $cpLang, "MainPageID=".$pageID." AND LangID=".$lng);
			} else {
				udb::insert("MainPages_text", $cpLang);
			}

		}
	}
?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php
	
}

$position=1;
$menu = include "pages_menu.php";



if($pageID){
	$que="SELECT * FROM `MainPages` WHERE MainPageID=".$pageID." ";
	$page= udb::single_row($que);
	$fac = explode(",",$page['tags']);
	$que="SELECT * FROM `alias` WHERE `table`='MainPages' AND ref=".$pageID." ";
	$alias= udb::single_row($que);

}

$que="SELECT LangID, LangName FROM language WHERE LangID!=1";
$languages = udb::full_list($que);

if($pageType==12){ 
	$que="SELECT MainPageID,MainPageTitle,picture,ShortDesc FROM `MainPages` WHERE MainPageType=5";
	$facili= udb::full_list($que);
	

}

?>
<div class="editItems">
    <h1><?=$page['MainPageTitle']?outDb($page['MainPageTitle']):"הוספת דף חדש"?></h1>
	<?php if($page['MainPageID']){ ?>
	<div class="miniTabs general" style="margin-right:50px;">
		<div class="tab lngtab active" data-langid="1"><p>עברית</p></div>
		<?php $i=2;
		foreach($languages as $lang){ ?>
			<div class="tab lngtab" data-langid="<?=$lang['LangID']?>"><p><?=$lang['LangName']?></p></div>
		<?php $i++; } ?>
	</div>
	<?php } ?>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<input type="hidden" name="LangID" value="<?=$langID?>">
		<div class="frm" id="langTab1">
			<div class="section">
				<div class="inptLine">
					<div class="label">כותרת: </div>
					<input type="text" value="<?=outDb($page['MainPageTitle'])?>" name="MainPageTitle" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">מוצג באתר: </div>
					<div class="chkBox">
						<input type="checkbox" value="1" <?=$page['ifShow']?"checked":""?> name="ifShow" id="ifShow_<?=$siteID?$siteID:0?>">
						<label for="ifShow_<?=$siteID?$siteID:0?>"></label>
					</div>
				</div>
			</div>

			<?php 
			if($pageType==588){ ?>
			<div class="section">
				<div class="inptLine">
					<div class="label">מוצג בדף ראשי: </div>
					<div class="chkBox">
						<input type="checkbox" value="1" <?=$page['showInHome']?"checked":""?> name="showInHome" id="showInHome_<?=$siteID?$siteID:0?>">
						<label for="showInHome_<?=$siteID?$siteID:0?>"></label>
					</div>
				</div>
			</div>
			<?php } ?>
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
						<img src="../../gallery/<?=$page['picture']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
			<?php if($pageType==25){ ?>
				<div class="section">
					<div class="inptLine">
						<div class="label">תאריך</div>
						<input type="text" value='<?=outDb($page['day'])?>' name="day" class="inpt datepicker">
					</div>
				</div>
			<?php } ?>
			<?php if($pageType==101){ ?>
				<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;float:left">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה לדף פנימי</div>
						<input type="file" name="picture2" class="inpt" value="<?=$page['picture2']?>">
					</div>
				</div>
				<?php if($page['picture2']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../gallery/<?=$page['picture2']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
			<?php } ?>
			<?php if($pageType==7){ ?>
			<div  style="clear:both;"></div>
			<div class="section">
				<div class="inptLine">
					<div class="label">קישור: </div>
					<input type="text" value="<?=outDb($page['link'])?>" name="link" class="inpt">
				</div>
			</div>
			<?php } ?>
			<div  style="clear:both;"></div>
			<div class="section txtarea">
				<div class="inptLine">
					<div class="label">תיאור קצר: </div>
					<textarea name="ShortDesc"><?=outDb($page['ShortDesc'])?></textarea>
				</div>
			</div>
			<?php if($pageType!=5){ ?>
			<div style="clear:both;"></div>
			<div class="section txtarea big">
				<textarea name="html_text" class="summernote"><?=outDb($page['html_text'])?></textarea>
			</div>
			<?php }  ?>
		</div>
		<div style="clear:both;"></div>
		<?php if($page['MainPageID']){

			$que="SELECT MainPageID, LangID, MainPageTitle, ShortDesc, ifShow, html_text 
				  FROM MainPages_text 
				  WHERE MainPageID=".$pageID;
			$langCont = udb::key_row($que, "LangID");

			foreach($languages as $lang){ ?>
			<div class="frm" style="display:none" id="langTab<?=$lang['LangID']?>">
				<input type="hidden" name="lang[<?=$lang['LangID']?>]" value="<?=$lang['LangID']?>">
				<div class="section">
					<div class="inptLine">
						<div class="label">כותרת </div>
						<input type="text" value='<?=outDb($langCont[$lang['LangID']]['MainPageTitle'])?>' name="lang_MainPageTitle[<?=$lang['LangID']?>]" class="inpt">
					</div>
				</div>
				<div class="section">
					<div class="inptLine">
						<div class="label">מוצג באתר: </div>
						<div class="chkBox">
							<input type="checkbox" value="1" <?=$langCont[$lang['LangID']]['ifShow']?"checked":""?> name="lang_ifShow[<?=$lang['LangID']?>]" id="ifShow_lang<?=$lang['LangID']?$lang['LangID']:0?>">
							<label for="ifShow_lang<?=$lang['LangID']?$lang['LangID']:0?>"></label>
						</div>
					</div>
				</div>
				<div class="section txtarea">
					<div class="inptLine">
						<div class="label">תיאור קצר: </div>
						<textarea name="lang_ShortDesc[<?=$lang['LangID']?>]"><?=outDb($langCont[$lang['LangID']]['ShortDesc'])?></textarea>
					</div>
				</div>
				<div  style="clear:both;"></div>
				<div class="section txtarea big">
					<textarea name="lang_html_text[<?=$lang['LangID']?>]" class="summernote"><?=outDb($langCont[$lang['LangID']]['html_text'])?></textarea>
				</div>
			</div>
			<?php }
		} ?>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$page['MainPageID']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>
<link rel="stylesheet" href="../app/bootstrap.css">
<link rel="stylesheet" href="../app/dist/summernote.css">
<script src="../app/bootstrap.min.js"></script>
<script src="../app/dist/summernote.js?v=<?=time()?>"></script>

<script>
	$(".general .lngtab").click(function(){
		$(".general .lngtab").removeClass("active");
		$(this).addClass("active");

		var ptID = $(this).data("langid");
		$(".frm").css("display","none");

		$("#langTab"+ptID).css("display","block");
	});


	var addAlt = function (context) {
		var ui = $.summernote.ui;
		var button = ui.button({
			contents: '<i class="fa fa-paperclip"/> Alt',
			tooltip: 'הוספת תגית Alt',
			click: function () {
				var theAlt = prompt("הזן תגית Alt", "");

				if (theAlt != null) {
					$(context.layoutInfo.editable.data('target')).attr("alt",theAlt);
					$(context.layoutInfo.editor.data('target')).attr("alt",theAlt);
					$(context.layoutInfo.note.data('target')).attr("alt",theAlt);
					context.layoutInfo.note.val(context.invoke('code'));
					context.layoutInfo.note.change();
				}
			}
		});
		return button.render();
	};
document.addEventListener('DOMContentLoaded', function(){       
	$('.summernote').summernote({
		toolbar: [
		['style', ['bold', 'italic', 'underline', 'clear']],
		['fontname', ['fontname']],
		['fontsize', ['fontsize']],
		['para', ['ul', 'ol', 'paragraph']],
		['height', ['height']],
		['insert', ['picture', 'link','video']],
		['view', ['codeview']]
		],
		popover: {
			image: [
				['alt', ['addAlt']],
				['imagesize', ['imageSize100', 'imageSize50', 'imageSize25']],
				['float', ['floatLeft', 'floatRight', 'floatNone']],
				['remove', ['removeMedia']]
			]},

		height: 300
	});
});

$(function() {
	$( ".datepicker" ).datepicker({
		dateFormat: 'yy/mm/dd'
	});
});
</script>