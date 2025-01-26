<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if('POST' == $_SERVER['REQUEST_METHOD']) {


	if($_POST['cats']){
		$cats = implode(",",$_POST['cats']);
	}
	$cp=Array();
	$cp['MainPageTitle'] = ($_POST['MainPageTitle']);
	$cp["ifShow"] = intval($_POST['ifShow'])?"1":"0";
	$cp["showInHome"] = intval($_POST['showInHome'])?"1":"0";
	$cp["showInMap"] = intval($_POST['showInMap'])?"1":"0";
	$cp["roomsCount"] = intval($_POST['roomsCount']);
	$cp["ownerName"] = ($_POST['ownerName']);
	$cp["ShortDesc"] = ($_POST['ShortDesc']);
	$cp["phoneNum"] = ($_POST['phoneNum']);
	$cp["address"] = ($_POST['address']);
	if($cp["address"]!=""){
		$geocode = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=ישראל,'.str_replace(" ","%20",$cp["address"]).'&sensor=false');
		$output= json_decode($geocode);

		$cp["lat"] = $output->results[0]->geometry->location->lat;
		$cp["long"] = $output->results[0]->geometry->location->lng;
	}
	$cp["link"] = ($_POST['link']);
	$cp["tags"] = $cats;
	$cp["html_text"] = ($_POST['html_text'] == "<br>") ? "" : $_POST['html_text'];
	$cp["html_text2"] = ($_POST['html_text2'] == "<br>") ? "" : $_POST['html_text2'];
	$cp["MainPageType"] = intval($pageType);
	$cp["LangID"] = intval($_POST['LangID']);

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

			$alias=Array();
			$que="SELECT LangCode FROM `language` WHERE LangID=".intval($_POST['LangID']);
			$langCode = udb::single_value($que);
			$alias['LEVEL1']=$langCode;
			$alias['LEVEL2']=($_POST['LEVEL2'])?(stripslashes(preg_replace('/\'|"/', '', $_POST['LEVEL2'], -1))):(stripslashes(preg_replace('/\'|"/', '', $_POST['MainPageTitle'], -1)));
			$alias['h1']=($_POST['h1'])?($_POST['h1']):($_POST['MainPageTitle']);
			$alias['title']=($_POST['title'])?($_POST['title']):($_POST['MainPageTitle']);
			$alias['keywords']=($_POST['keywords']);
			$alias['description']=($_POST['description']);
			$alias['ref']=$pageID;
			$alias['table']='MainPages';
			udb::insert("alias", $alias);
	}
	if(isset($_POST['lang'])){
		foreach($_POST['lang'] as $lng=>$val){
			$cpLang = Array();
			$cpLang['MainPageID'] = $pageID;
			$cpLang['LangID'] = $lng;
			$cpLang['MainPageTitle'] = $_POST['lang_MainPageTitle'][$lng];
			$cpLang['ShortDesc'] = $_POST['lang_ShortDesc'][$lng];
			$cpLang['html_text'] = $_POST['lang_html_text'][$lng];
			$cpLang["ifShow"] = intval($_POST['lang_ifShow'][$lng])?"1":"0";

			$que="SELECT MainPageID, LangID, MainPageTitle FROM MainPages_text WHERE MainPageID=".$pageID." AND LangID=".$lng;
			$checkTest = udb::single_row($que);
			if($checkTest){
				udb::update("MainPages_text", $cpLang, "MainPageID=".$pageID." AND LangID=".$lng);
			} else {
				udb::insert("MainPages_text", $cpLang);
			}


			$que="SELECT * FROM `alias` WHERE `table`='MainPages' AND ref=".$pageID." ";
			$checkAlias= udb::single_row($que);

			$que="SELECT LangID, LangCode FROM language WHERE 1";
			$lngs = udb::key_row($que,"LangID");

			$cpLang2=Array();
			$cpLang2['id']=$checkAlias['id'];
			$cpLang2['LEVEL1']=$lngs[$lng]['LangCode'];
			$cpLang2['LangID']=$lng;
			$cpLang2['title']=$_POST['lang_MainPageTitle'][$lng];
			$cpLang2['h1']=$_POST['lang_MainPageTitle'][$lng];
			$cpLang2['LEVEL2']=preg_replace("/(['\"])+/", "",$_POST['lang_MainPageTitle'][$lng]);
			$cpLang2['ref']=$pageID;
			$cpLang2['table']='MainPages';

			$que="SELECT id, ref FROM `alias_text` WHERE id=".$checkAlias['id']." AND LangID=".$lng." AND `table`='MainPages' AND `ref`='".$pageID."' ";
			$checkAliasPortal= udb::single_row($que);
			if($checkAliasPortal){
				udb::update("alias_text", $cpLang2, "id=".$checkAlias['id']." AND `LangID`='".$lng."' AND `table`='MainPages' AND `ref`='".$pageID."'");
			} else {
				udb::insert("alias_text", $cpLang2);
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
	$categ = explode(",",$page['tags']);
	$catPro = explode(",",$page['categories']);
	$que="SELECT * FROM `alias` WHERE `table`='MainPages' AND ref=".$pageID." ";
	$alias= udb::single_row($que);

}

$que="SELECT LangID, LangName FROM language WHERE LangID!=1";
$languages = udb::full_list($que);

/*
$que="SELECT * FROM categories WHERE menuParent=0 AND `menuType`=1 AND LangID=".$langID." ORDER BY menuOrder";
$categories=udb::full_list($que);*/

?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$page['MainPageTitle']?outDb($page['MainPageTitle']):"הוספת דף חדש"?></h1>
	<?php if($page['MainPageID']){ ?>
	<div class="miniTabs">
		<?php foreach($menu as $men){
		if($men['position']==$position && $men['sub']){
			$subMenu = $men['sub'];
		}
		?>
		<div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>?pageID=<?=$pageID?>&type=<?=$pageType?>'"><p><?=$men['name']?></p></div>
		<?php  } ?>
	</div>
	<?php if($page['MainPageID']){ ?>
	<div class="miniTabs general" style="margin-right:50px;">
		<div class="tab lngtab active" data-langid="1"><p>עברית</p></div>
		<?php $i=2;
		foreach($languages as $lang){ ?>
			<div class="tab lngtab" data-langid="<?=$lang['LangID']?>"><p><?=$lang['LangName']?></p></div>
		<?php $i++; } ?>
	</div>
	<?php } ?>
	<?php } ?>

	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<input type="hidden" name="LangID" value="<?=$langID?>">
		<div class="frm" id="langTab1">
			<div class="section">
				<div class="inptLine">
					<div class="label">כותרת: </div>
					<input type="text" value='<?=stripslashes(htmlspecialchars($page['MainPageTitle'], ENT_QUOTES))?>' name="MainPageTitle" class="inpt">
				</div>
			</div>
			<?php if($pageType==10 || $pageType==25){ ?>
			<div class="section">
				<div class="inptLine">
					<div class="label">טלפון </div>
					<input type="text" value='<?=outDb($page['phoneNum'])?>' name="phoneNum" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">כתובת </div>
					<input type="text" value='<?=outDb($page['address'])?>' name="address" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">מספר חדרים</div>
					<select name="roomsCount">
						<?php for($i=1; $i<=10; $i++){ ?>
						<option value="<?=$i?>"<?=($page['roomsCount']==$i?' selected':'')?> ><?=$i?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">שם בעלים</div>
					<input type="text" value='<?=outDb($page['ownerName'])?>' name="ownerName" class="inpt">
				</div>
			</div>
			<?php } ?>
			<div class="section">
				<div class="inptLine">
					<div class="label">מוצג באתר: </div>
					<div class="chkBox">
						<input type="checkbox" value="1" <?=$page['ifShow']?"checked":""?><?=(!$pageID?"checked":"")?> name="ifShow" id="ifShow_<?=$siteID?$siteID:0?>">
						<label for="ifShow_<?=$siteID?$siteID:0?>"></label>
					</div>
				</div>
			</div>

			<?php 
			if($pageType==10){ ?>
			<div class="section">
				<div class="inptLine">
					<div class="label">מוצג בדף ראשי: </div>
					<div class="chkBox">
						<input type="checkbox" value="1" <?=$page['showInHome']?"checked":""?> name="showInHome" id="showInHome_<?=$siteID?$siteID:0?>">
						<label for="showInHome_<?=$siteID?$siteID:0?>"></label>
					</div>
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">הצג סימון במפה</div>
					<div class="chkBox">
						<input type="checkbox" value="1" <?=$page['showInMap']?"checked":""?> name="showInMap" id="showInHome_<?=$siteID?$siteID:0?>">
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
			<?php if($pageType==1){ ?>
				<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
					<div class="section">
						<div class="inptLine">
							<div class="label">תמונת רקע </div>
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
			<?php if($pageType==99){ ?>
				<div class="section">
					<div class="inptLine">
						<div class="label">תאריך</div>
						<input type="text" value='<?=outDb($page['day'])?>' name="day" class="inpt datepicker">
					</div>
				</div>
			<?php } ?>

			<?php if($pageType==99){ ?>
			<div  style="clear:both;"></div>
			<div class="section">
				<div class="inptLine">
					<div class="label">קישור: </div>
					<input type="text" value="<?=outDb($page['link'])?>" name="link" class="inpt">
				</div>
			</div>
			<?php } ?>
			<div style="clear:both;"></div>
			<div class="section txtarea">
				<div class="inptLine">
					<div class="label">תיאור קצר: </div>
					<textarea name="ShortDesc"><?=outDb($page['ShortDesc'])?></textarea>
				</div>
			</div>
			<div style="clear:both;"></div>
			<div class="section txtarea big">
				<div class="summerTtl">מידע נוסף</div>
				<textarea name="html_text"  class="summernote"><?=outDb($page['html_text'])?></textarea>
			</div>
			<?php if($pageType==10 || $pageType==25 ) { ?>
			<div class="section txtarea big">
				<div class="summerTtl">למי מתאים</div>
				<textarea name="html_text2"  class="summernote"><?=outDb($page['html_text2'])?></textarea>
			</div>
			<?php } ?>

		<div style="clear:both;"></div>
		<?php if($pageType==10 || $pageType==25 ) { ?>
		<div class="catWrap">
			<div class="catTtl">מתקנים שנמצאים בוילה</div>
		<?php $que="SELECT * FROM `facilites` WHERE 1"; 
			  $facilities=udb::full_list($que); 
			  foreach($facilities as $facilite){ 
			  ?>
				<div class="matWrap">
					<input type="checkbox" <?php if($categ) {if(in_array($facilite['ID'], $categ)){ echo 'checked'; }}?>  name="cats[<?=$facilite['ID']?>]" value="<?=$facilite['ID']?>">
					<span class="matName"><?=outDb($facilite['facName'])?></span>
				</div>
				<?php } ?>
		</div>

		<?php } ?>
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

	var insertPop = function (context) {
		var ui = $.summernote.ui;
		var button = ui.button({
			contents: '<i class="fa fa-paperclip"/> Alt',
			tooltip: 'הוספת תגית Alt',
			click: function () {
				console.log('a');
			}
		});
		return button.render();
	};
document.addEventListener('DOMContentLoaded', function(){       
	$('.summernote').summernote({
		toolbar: [
		['style', ['bold', 'italic', 'underline', 'clear']],
		['fontname', ['fontname']],
		['color', ['color']],
		['fontsize', ['fontsize']],
		['para', ['ul', 'ol', 'paragraph']],
		['height', ['height']],
		['insert', ['picture', 'link','video']],
		['view', ['codeview']],
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