<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if('POST' == $_SERVER['REQUEST_METHOD']) {
	
	$cp=Array();
	$cp['MainPageTitle'] = inDb($_POST['MainPageTitle']);
	$cp["ifShow"] = intval($_POST['ifShow'])?"1":"0";
	$cp["ShortDesc"] = inDb($_POST['ShortDesc']);
	$cp["MainPageType"] = intval($pageType);
	$cp["LangID"] = intval($_POST['LangID']);

	if($pageID){
		udb::update("MainPages", $cp, "MainPageID =".$pageID);
	} else {
		$cp["createDay"] = date("Y-m-d");
		$pageID = udb::insert("MainPages", $cp);
	}

	foreach($_POST['ids'][1] as $key=>$val){
		$price=Array();
		$price['type'] = intval($pageID);
		$price["langID"] = 1;
		$price["col1"] = $_POST['col1'][1][$key];
		$price["col2"] = $_POST['col2'][1][$key];
		$price["col3"] = $_POST['col3'][1][$key];
		if($key){
			udb::update("prices", $price, "ID =".$key);
		} else {
			if($price["col1"] || $price['col2'] || $price['col3']){
				$key = udb::insert("prices", $price);
			}
		}	
	}


	if(isset($_POST['lang'])){
		foreach($_POST['lang'] as $lng=>$val){
			$cpLang = Array();
			$cpLang['MainPageID'] = $pageID;
			$cpLang['LangID'] = $lng;
			$cpLang['MainPageTitle'] = $_POST['lang_MainPageTitle'][$lng];
			$cpLang['ShortDesc'] = $_POST['lang_ShortDesc'][$lng];
			$cpLang["ifShow"] = intval($_POST['lang_ifShow'][$lng])?"1":"0";

			$que="SELECT MainPageID, LangID, MainPageTitle FROM MainPages_text WHERE MainPageID=".$pageID." AND LangID=".$lng;
			$checkTest = udb::single_row($que);
			if($checkTest){
				udb::update("MainPages_text", $cpLang, "MainPageID=".$pageID." AND LangID=".$lng);
			} else {
				udb::insert("MainPages_text", $cpLang);
			}


			foreach($_POST['ids'][$lng] as $key=>$val){
				$price=Array();
				$price['type'] = intval($pageID);
				$price["langID"] = $lng;
				$price["col1"] = inDb($_POST['col1'][$lng][$key]);
				$price["col2"] = inDb($_POST['col2'][$lng][$key]);
				$price["col3"] = inDb($_POST['col3'][$lng][$key]);
				if($key){
					udb::update("prices", $price, "ID =".$key." AND langID=".$lng);
				} else {
					if($price["col1"] || $price['col2'] || $price['col3']){
						$key = udb::insert("prices", $price);
					}
				}
			}
		}
	}
?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php
	
}

$position=1;
$menu = include "pages_menu.php";

$que = "SELECT * FROM `settlements`	WHERE 1 ORDER BY `TITLE`";
$setts= udb::full_list($que);



if($pageID){
	$que="SELECT * FROM `MainPages` WHERE MainPageID=".$pageID." ";
	$page= udb::single_row($que);
	$fac = explode(",",$page['tags']);
	$que="SELECT * FROM `alias` WHERE `table`='MainPages' AND ref=".$pageID." ";
	$alias= udb::single_row($que);
	$que = "SELECT * FROM `prices` WHERE type=".$page['MainPageID'];
	$prices = udb::key_row($que, Array("type", "langID", "ID"));

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
			<div class="section txtarea">
				<div class="inptLine">
					<div class="label">תיאור קצר: </div>
					<textarea name="ShortDesc"><?=outDb($page['ShortDesc'])?></textarea>
				</div>
			</div>

	
				<div class="tbl">
					<div class="tblRow head">
						<div class="tblCell"><?=Dictionary::translate("כותרת")?></div>
						<div class="tblCell center"><?=Dictionary::translate('אמצ"ש')?></div>
						<div class="tblCell"><?=Dictionary::translate('סופ"ש')?></div>
						<div class="tblCell"></div>
					</div>
					<input type="hidden" name="type" value="<?=$page['MainPageID']?>">
					<?php if($prices[$page['MainPageID']][1]) { ?>
					<?php foreach($prices[$page['MainPageID']][1] as $price) { ?>
						<input type="hidden" name="ids[1][<?=$price['ID']?>]" value="<?=$price['ID']?>" >
						<div class="tblRow">
							<div class="tblCell"><input type="text" name="col1[1][<?=$price['ID']?>]" value='<?=outDb($price['col1'])?>'></div>
							<div class="tblCell"><input type="text" name="col2[1][<?=$price['ID']?>]" value='<?=outDb($price['col2'])?>'></div>
							<div class="tblCell"><input type="text" name="col3[1][<?=$price['ID']?>]" value='<?=outDb($price['col3'])?>'></div>
							<div class="tblCell"><div class="addBtn" onclick="if(confirm('אתה בטוח??')){deleteThis(<?=$price['ID']?>,this)}">מחק</div></div>	
						</div>
					<?php } } ?>
					<input type="hidden" name="ids[1][0]">
					<div class="tblRow">
						<div class="tblCell"><input type="text" name="col1[1][0]"></div>
						<div class="tblCell"><input type="text" name="col2[1][0]"></div>
						<div class="tblCell"><input type="text" name="col3[1][0]"></div>
						<div class="tblCell"></div>	
					</div>
				</div>
	
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
				<div class="tbl">
					<div class="tblRow head">
						<div class="tblCell"><?=Dictionary::translate("כותרת")?></div>
						<div class="tblCell center"><?=Dictionary::translate('אמצ"ש')?></div>
						<div class="tblCell"><?=Dictionary::translate('סופ"ש')?></div>
						<div class="tblCell"></div>
					</div>					
					<?php if($prices[$page['MainPageID']][$lang['LangID']]) { ?>
					<?php foreach($prices[$page['MainPageID']][$lang['LangID']] as $price) { ?>
						<input type="hidden" name="ids[<?=$lang['LangID']?>][<?=$price['ID']?>]" value="<?=$price['ID']?>">
						<div class="tblRow">
							<div class="tblCell"><input type="text" name="col1[<?=$lang['LangID']?>][<?=$price['ID']?>]" value="<?=$price['col1']?>"></div>
							<div class="tblCell"><input type="text" name="col2[<?=$lang['LangID']?>][<?=$price['ID']?>]" value="<?=$price['col2']?>"></div>
							<div class="tblCell"><input type="text" name="col3[<?=$lang['LangID']?>][<?=$price['ID']?>]" value="<?=$price['col3']?>"></div>
							<div class="tblCell"><div class="addBtn" onclick="deleteThis(<?=$price['ID']?>)">מחק</div></div>	
						</div>
					<?php } } ?>
					<input type="hidden" name="ids[<?=$lang['LangID']?>][0]">
					<div class="tblRow">
						<div class="tblCell"><input type="text" name="col1[<?=$lang['LangID']?>][0]"></div>
						<div class="tblCell"><input type="text" name="col2[<?=$lang['LangID']?>][0]"></div>
						<div class="tblCell"><input type="text" name="col3[<?=$lang['LangID']?>][0]"></div>
						<div class="tblCell"></div>	
					</div>
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


	function deleteThis(id,elem){
		
	
		$.post('js_delPrices.php',{id:id},function(result){

			$(elem).parent().parent().remove();
			//window.location.reload();

		});



	}


	$(".general .lngtab").click(function(){
		$(".general .lngtab").removeClass("active");
		$(this).addClass("active");

		var ptID = $(this).data("langid");
		$(".frm").css("display","none");

		$("#langTab"+ptID).css("display","block");
	});

$(function() {
	$( ".datepicker" ).datepicker({
		dateFormat: 'yy/mm/dd'
	});
});
</script>

<style type="text/css">
	.tbl{max-width:1000px;width:100%;display:table;margin:20px auto}
	.tbl .tblRow{margin-top: 10px;}
	.tbl .tblRow.head .tblCell{}
	.tbl .tblRow .tblCell{display:inline-block;width:24%;}
	.tbl .tblRow .tblCell .addBtn{width:50px;margin:0 auto;height:20px;line-height:20px;border-radius:5px;background:#2fc2eb;cursor:pointer;text-align:center;color:#fff}
</style>