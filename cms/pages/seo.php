<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$position=3;


$pageID=intval($_GET['pageID']);
$pageType = intval($_GET['type']);



if('POST' == $_SERVER['REQUEST_METHOD']) { 


	$alias=Array();

	$que="SELECT LangCode FROM `language` WHERE LangID=".intval($_POST['LangID']);
	$langCode = udb::single_value($que);
	$alias['LEVEL1']=$langCode;
	$alias['LEVEL2']=preg_replace("/(['\"])+/", "",inDb($_POST['LEVEL2'])?inDb($_POST['LEVEL2']):inDB($_POST['MainPageTitle']));
	$alias['h1']=inDb($_POST['h1'])?inDb($_POST['h1']):inDB($_POST['MainPageTitle']);
	$alias['title']=inDb($_POST['title'])?inDb($_POST['title']):inDB($_POST['MainPageTitle']);
	$alias['keywords']=inDb($_POST['keywords']);
	$alias['description']=inDb($_POST['description']);
	$alias['ref']=$pageID;
	$alias['table']='MainPages';


	$que="SELECT * FROM `alias` WHERE `table`='MainPages' AND ref=".$pageID." ";
	$checkAlias= udb::single_row($que);
	if($checkAlias){
		udb::update("alias", $alias, "id=".$checkAlias['id']."");
	} else {
		udb::insert("alias", $alias);
	}



	if(isset($_POST['lang'])){

		$que="SELECT LangID, LangCode FROM language WHERE 1";
		$lngs = udb::key_row($que,"LangID");


		foreach($_POST['lang'] as $lng=>$val){

			$cpLang=Array();
			$cpLang['id']=$checkAlias['id'];
			$cpLang['LEVEL1']=$lngs[$lng]['LangCode'];
			$cpLang['LangID']=$lng;
			$cpLang['title']=$_POST['lang_title'][$lng];
			$cpLang['h1']=$_POST['lang_title'][$lng];
			$cpLang['LEVEL2']=$_POST['lang_LEVEL2'][$lng];
			$cpLang['keywords']=$_POST['lang_keywords'][$lng];
			$cpLang['description']=$_POST['lang_description'][$lng];
			$cpLang['ref']=$pageID;
			$cpLang['table']='MainPages';

			$que="SELECT id, ref FROM `alias_text` WHERE id=".$checkAlias['id']." AND LangID=".$lng." AND `table`='MainPages' AND `ref`='".$pageID."' ";
			$checkAliasPortal= udb::single_row($que);
			if($checkAliasPortal){
				udb::update("alias_text", $cpLang, "id=".$checkAlias['id']." AND `LangID`='".$lng."' AND `table`='MainPages' AND `ref`='".$pageID."'");
			} else {
				udb::insert("alias_text", $cpLang);
			}

		}
	}
	
	
}


$menu = include "pages_menu.php";

$que="SELECT * FROM `MainPages` WHERE MainPageID=".$pageID."";
$page= udb::single_row($que);

$que="SELECT * FROM `alias` WHERE `table`='MainPages' AND ref=".$pageID." ";
$alias= udb::single_row($que);


$que="SELECT LangID, LangName FROM language WHERE LangID!=1";
$languages = udb::full_list($que);

?>
<div class="popRoom">
	<div class="popRoomContent"></div>
</div>
<div class="editItems">
    <h1><?=outDb($page['MainPageTitle'])?></h1>
	<div class="miniTabs">
		<?php foreach($menu as $men){ 
		if($men['position']==$position && $men['sub']){
			$subMenu = $men['sub'];
		}
		?>
		<div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>?pageID=<?=$pageID?>&type=<?=$pageType?>'"><p><?=$men['name']?></p></div>
		<?php } ?>
	</div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="LangID" value="<?=$page['LangID']?>">
		<b>ערוך SEO</b>
		<div class="miniTabs general" style="margin-right:50px;">
			<div class="tab lngtab active" data-langid="1"><p>עברית</p></div>
			<?php $i=2;
			foreach($languages as $lang){ ?>
				<div class="tab lngtab" data-langid="<?=$lang['LangID']?>"><p><?=$lang['LangName']?></p></div>
				<?php $i++; } ?>
		</div>
		<div style="clear:both;"></div>
		<div class="frm" id="langTab1">
			<div class="section">
				<div class="inptLine">
					<div class="label">כותרת עמוד: </div>
					<input type="text" value='<?=stripslashes(htmlspecialchars($alias['title'], ENT_QUOTES))?>' name="title" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">קישור: </div>
					<input type="text" value='<?=outDb($alias['LEVEL2'])?>' name="LEVEL2" class="inpt">
				</div>
			</div>
			<div style="clear:both;"></div>
			<?php $link = outDb(urldecode(showAlias("MainPages", $page['MainPageID']))); ?>
			<a href='<?=$link?>' target="_blank" style="direction:ltr;text-align:left;display:block"><?=$link?></a>
			<div style="clear:both;"></div>
			<div class="section txtarea">
				<div class="inptLine">
					<div class="label">keywords: </div>
					<textarea name="keywords"><?=outDb($alias['keywords'])?></textarea>
				</div>
			</div>
			<div class="section txtarea">
				<div class="inptLine">
					<div class="label">תיאור קצר: </div>
					<textarea name="description"><?=outDb($alias['description'])?></textarea>
				</div>
			</div>
		</div>
		<?php
		$que="SELECT *
				  FROM alias_text 
				  WHERE id=".$alias['id']." ";
		$langCont = udb::key_row($que, "LangID");

		foreach($languages as $lang){ ?>
		<div class="frm" style="display:none" id="langTab<?=$lang['LangID']?>">
			<input type="hidden" name="lang[<?=$lang['LangID']?>]" value="<?=$lang['LangID']?>">
			<div class="section">
				<div class="inptLine">
					<div class="label">כותרת עמוד: </div>
					<input type="text" value="<?=outDb($langCont[$lang['LangID']]['title'])?>" name="lang_title[<?=$lang['LangID']?>]" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">קישור: </div>
					<input type="text" value='<?=outDb($langCont[$lang['LangID']]['LEVEL2'])?>' name="lang_LEVEL2[<?=$lang['LangID']?>]" class="inpt">
				</div>
			</div>
			<div style="clear:both;"></div>
			<?php
			$link = outDb(urldecode(showAliasLang("MainPages", $page['MainPageID'], $lang['LangID'])));
			?>
			<a href='<?=$link?>' target="_blank" style="direction:ltr;text-align:left;display:block"><?=$link?></a>
			<div style="clear:both;"></div>
			<div class="section txtarea">
				<div class="inptLine">
					<div class="label">keywords: </div>
					<textarea name="lang_keywords[<?=$lang['LangID']?>]"><?=outDb($langCont[$lang['LangID']]['keywords'])?></textarea>
				</div>
			</div>
			<div class="section txtarea">
				<div class="inptLine">
					<div class="label">תיאור קצר: </div>
					<textarea name="lang_description[<?=$lang['LangID']?>]"><?=outDb($langCont[$lang['LangID']]['description'])?></textarea>
				</div>
			</div>
		</div>
		<?php } ?>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="שמור" class="submit">
			</div>
		</div>
	</form>
</div>
</section>
<div id="alerts">
    <div class="container">
        <div class="closer"></div>
        <div class="title"></div>
        <div class="body"></div>
    </div>
</div>
<script src="<?=$root;?>/app/jquery-ui.min.js"></script>
<script>
	$(".general .lngtab").click(function(){
		$(".general .lngtab").removeClass("active");
		$(this).addClass("active");

		var ptID = $(this).data("langid");
		$(".frm").css("display","none");

		$("#langTab"+ptID).css("display","block");
	});
</script>
</body>
</html>