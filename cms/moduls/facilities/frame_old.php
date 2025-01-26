<?php

include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "../../_globalFunction.php";

/*$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;*/

$langID   = typemap($_GET['LangID'], 'int') ?: 1;
$attrID   = typemap($_GET['pageID'], 'int');
$domainID = $attrID ? ($_SESSION['cms']['domainID'] ?: 0) : 0;



if($_GET['attrdel'] != 0){

	udb::query("DELETE FROM `attributes` WHERE attrID=".intval($_GET['attrdel']));
	udb::query("DELETE FROM `attributes_langs` WHERE attrID=".intval($_GET['attrdel']));
	udb::query("DELETE FROM `attributes_domains` WHERE attrID=".intval($_GET['attrdel']));
	//Delete all other uses
	udb::query("DELETE FROM `rooms_attributes` WHERE attrID=".intval($_GET['attrdel']));
	udb::query("DELETE FROM `sites_attributes` WHERE attrID=".intval($_GET['attrdel']));
	

?>

<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php
}



if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
        $attrID = typemap($_POST['attrID'], 'int');
        $data = typemap($_POST, [
            'defaultName'   => 'string',
			'sumAble'		=> 'int',
            'fontCode'   => 'string',
            '!active'       => 'int',
            '!popular'       => 'int',
            '!promotedSEO'       => 'int',
            '!activeFilter'  => 'int',
            '!activeAutoSuggest'	=> 'int',
            '!categoryID'   => 'int',
            'category'   => 'int',
            'domActive'     => ['int' => 'int'],
            'attrName'      => ['int' => ['int' => 'string']],
            'attrDesc'      => ['int' => ['int' => 'text']],
            'attrHeadTitle' => ['int' => ['int' => 'string']],
            'filterName' => ['int' => ['int' => 'string']],
            'attrHeadKeys'  => ['int' => ['int' => 'string']],
            'attrHeadDesc'  => ['int' => ['int' => 'text']],
            'parentCategory' => 'int'
        ]);
		
        if (!$data['defaultName'])
            throw new LocalException('חייב להיות שם');
        if (!$data['categoryID'] && !$data['category']){
            $data['categoryID'] = 1;
			//udb::single_value("SELECT `categoryID` FROM `attributes_categories` WHERE `active` = 1 ORDER BY `categoryID` DESC LIMIT 1");      // last added category
		}
		else if($data['category']){
			 $data['categoryID'] = $data['category'];
		}
		
		$photo = pictureUpload('picture',"../../../../gallery/");
		

        $que = ['active' => $data['active'],'categoryID' => $data['categoryID'] , 'activeFilter' => max($data['activeFilter'], $data['promotedSEO']), 'activeAutoSuggest' => $data['activeAutoSuggest'], 'popular' => $data['popular'], 'promotedSEO' => $data['promotedSEO'],'sumAble' => $data['sumAble'], 'defaultName' => $data['defaultName'], 'fontCode' => $data['fontCode'],'parentCategory'=>$data['parentCategory']];
		if($photo){
			$que["iconImage"] = $photo[0]['file']; 
		}
        if ($attrID){
            udb::update('attributes', $que, '`attrID` = ' . $attrID);
        } else {
            udb::query("LOCK TABLES `attributes` WRITE");
            $que['showOrder'] = udb::single_value("SELECT IFNULL(MAX(`showOrder`) + 1, 1) FROM `attributes` WHERE `categoryID` = " . $data['categoryID']);
            $attrID = udb::insert('attributes', $que);
            udb::query("UNLOCK TABLES");
        }
        //Update room Attributes
        if(intval($data['parentCategory'])) {
            $sql = "update rooms_attributes set relAttr=".intval($data['parentCategory'])." where attrID=".$attrID;
            udb::query($sql);
        }

        // ends ***

        $dlist = [];
        $doms  = udb::single_column("SELECT `domainID` FROM `domains` WHERE 1", 0);
        $langs = udb::single_column("SELECT `langID` FROM `language` WHERE 1", 0);
        foreach(DomainList::get() as $did => $dom){
			
            $dlist[] = "(" . $attrID . ", " . $did . ", " . intval($data['domActive'][$did]) . ")";

            $list = [];
            foreach($langs as $lid)
                $list[] = "(" . $attrID . ", " . $did .  ", " . $lid . "
			, '" . udb::escape_string($data['attrName'][$did][$lid]) . "'
			, '" . udb::escape_string($data['filterName'][$did][$lid]) . "'
			, '" . udb::escape_string($data['attrDesc'][$did][$lid]) . "'
            , '" . udb::escape_string($data['attrHeadTitle'][$did][$lid]) . "'
			, '" . udb::escape_string($data['attrHeadDesc'][$did][$lid]) . "'
			, '" . udb::escape_string($data['attrHeadKeys'][$did][$lid]) . "')";
			
            count($list) and udb::query("INSERT INTO `attributes_langs`(`attrID`, `domainID`, `langID`, `defaultName`, `filterName`, `attrDesc`, `attrHeadTitle`, `attrHeadDesc`, `attrHeadKeys`) 
					   VALUES" . implode(',', $list) . " ON DUPLICATE KEY UPDATE `defaultName` = VALUES(`defaultName`), filterName = VALUES(`filterName`) ,`attrDesc` = VALUES(`attrDesc`), `attrHeadDesc` = VALUES(`attrHeadDesc`), `attrHeadTitle` = VALUES(`attrHeadTitle`), `attrHeadKeys` = VALUES(`attrHeadKeys`)");
        }
        count($dlist) and udb::query("INSERT INTO `attributes_domains`(`attrID`, `domainID`, `active`) VALUES" . implode(',', $dlist) . " ON DUPLICATE KEY UPDATE `active` = VALUES(`active`)");


		$dom = udb::single_row("SELECT * FROM `attributes_domains` WHERE `domainID` = 1 AND `attrID` = " . $attrID);
		$dom['domainID'] = 1;
		udb::insert('attributes_domains', $dom, true);

		$doms = udb::single_list("SELECT * FROM `attributes_langs` WHERE `domainID` = 1 AND `attrID` = " . $attrID);
		foreach($doms as $dom){
			$dom['domainID'] = 1;
			udb::insert('attributes_langs', $dom, true);
		}

        //reloadParent();
    }
    catch (LocalException $e){
        // show error
    } ?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php 

}
 

$base = udb::single_row("SELECT * FROM `attributes` WHERE `attrID` = " . $attrID);
$d_attr = udb::key_row("SELECT * FROM `attributes_domains` WHERE `attrID` = " . $attrID, 'domainID');
$l_attr = udb::key_row("SELECT * FROM `attributes_langs` WHERE `attrID` = " . $attrID, ['domainID', 'langID']);
$categories = udb::full_list("SELECT `categoryID`, `categoryName` FROM `attributes_categories` WHERE 1 ORDER BY `showOrder`");

?>

<div class="editItems">
	<form method="POST" id="myform" enctype="multipart/form-data">
        <input type="hidden" name="attrID" value="<?=$attrID?>" />
        <input type="hidden" name="categoryID" value="<?=($base['categoryID']?$base['categoryID']:1)?>" />
		<div class="topInput">
			<div class="inputLblWrap">
				<div class="labelTo">שם מאפיין : </div>
				<input type="text" placeholder="שם מערכת" name="defaultName" value="<?=js_safe($base['defaultName'])?>" />
			</div>
			
				<div class="checkLabel">
					<label for="sumAble">מאפיין נסכם:</label> 
					<div class="checkBoxWrap">
					<input type="checkbox" name="sumAble" id="sumAble" value="1" <?=$base['sumAble'] ? 'checked="checked"' : ''?> />
					<label for="sumAble"></label> 
					</div>
				</div>
			
			<div class="inputLblWrap" style="position: relative;">
				<div class="labelTo">קוד פונט</div>
				<?php if($base["fontCode"]) { ?>
					<span style="position: absolute;left: 0;bottom: 2px;"><span class="iconx-small" aria-hidden="true" data-icon="&#x<?=$base["fontCode"]?>"></span></span>	
				<?php } ?>
				<input type="text" placeholder="קוד פונט" name="fontCode" value="<?=js_safe($base['fontCode'])?>" />
			</div>
			<div class="checkLabel">
				<label for="active">מוצג</label>
				<div class="checkBoxWrap">
                    <input type="checkbox" name="active" id="active" value="1" <?=(($base['active'] || !$attrID) ? 'checked="checked"' : '')?> title="" />
					<label for="active"></label>
				</div>
			</div>
			<div class="checkLabel">
				<label for="activeFilter">מוצג בסינונים</label>
				<div class="checkBoxWrap">
                    <input type="checkbox" name="activeFilter" id="activeFilter" value="1" <?=(($base['activeFilter'] || !$attrID) ? 'checked="checked"' : '')?> title="" />
					<label for="activeFilter"></label>
				</div>
			</div>
			<div class="checkLabel">
				<label for="activeAutoSuggest">מוצג בהצעה אוטומטית</label>
				<div class="checkBoxWrap">
                    <input type="checkbox" name="activeAutoSuggest" id="activeAutoSuggest" value="1" <?=(($base['activeAutoSuggest'] || !$attrID) ? 'checked="checked"' : '')?> title="" />
					<label for="activeAutoSuggest"></label>
				</div>
			</div>
			<div class="checkLabel">
				<label for="show2">פופולרי</label>
				<div class="checkBoxWrap">
                    <input type="checkbox" name="popular" id="show2" value="1" <?=(($base['popular']) ? 'checked="checked"' : '')?>/>
					<label for="show2"></label>
				</div>
			</div>
			<div class="checkLabel">
				<label for="show2">דף SEO</label>
				<div class="checkBoxWrap">
                    <input type="checkbox" name="promotedSEO" id="promotedSEO" value="1" <?=(($base['promotedSEO']) ? 'checked="checked"' : '')?> onclick="this.checked&&$('#activeFilter').prop('checked', true)"/>
					<label for="promotedSEO"></label>
				</div>
			</div>
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה: </div>
						<input type="file" name="picture" class="inpt" value="<?=$accessData['iconImage']?>">
					</div>
				</div>
				<?php if($accessData['iconImage']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../../../gallery/<?=$accessData['iconImage']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">שייך לקטגוריה</div>
				<select name="category">
<?php
    foreach($categories as $cid => $cname)
        echo '<option value="' . $cname['categoryID'] . '" ' . (($cname['categoryID'] == $base['categoryID']) ? 'selected="selected"' : '') . '>' . $cname['categoryName'] . '</option>';
?>
				</select>
			</div>
		</div>
        <div class="inputLblWrap">
				<div class="labelTo">קישור למאפיין</div>
                    <select name="parentCategory">
                        <option value="0">ללא שיוך</option>
                        <?php
                        foreach($categories as  $cid=> $cname){
                            echo '<option value="' . $cname['categoryID'] . '" disabled>' . $cname['categoryName'] . '</option>';
                            $allAttributes  = udb::full_list("SELECT categoryID,attrID,defaultName FROM `attributes` WHERE active=1 and categoryID=".$cname['categoryID']." order by defaultName" );
                            foreach($allAttributes as $aid=>$aname) {
                                echo '<option value="' . $aname['attrID'] . '" ' . (($aname['attrID'] == $base['parentCategory']) ? 'selected="selected"' : '') . '>--' . $aname['defaultName'] . '</option>';
                            }

                        }
                        ?>
                    </select>
                </div>
		    </div>

<?php
    $domains = domainTabs($domainID);
?>

		<div class="domainTabsWrapper">
<?php
    foreach($domains as $did => $domainName){
        $dom = $d_attr[$did];
?>
			<div class="iconPicWrap domain <?=(($domainID == $did) ? 'active' : '')?>" data-id="<?=$did?>">
				<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
					<div class="section">
						<div class="inptLine">
							<div class="label">איקון: </div>
							<input type="file" name="icon[<?=$did?>]" class="inpt" value="<?=$dom['attrIcon']?>" />
						</div>
					</div>
<?php
        if($dom['attrIcon']){
?>
					<div class="section">
						<div class="inptLine">
							<img src="/gallery/<?=$dom['attrIcon']?>" style="width:100%" />
						</div>
					</div>
<?php
        }
?>
				</div>
				<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
					<div class="section">
						<div class="inptLine">
							<div class="label">תמונה: </div>
							<input type="file" name="picture[<?=$did?>]" class="inpt" value="<?=$dom['attrPic']?>" />
						</div>
					</div>
<?php
        if($dom['attrPic']){
?>
					<div class="section">
						<div class="inptLine">
							<img src="/gallery/<?=$dom['attrPic']?>" style="width:100%" />
						</div>
					</div>
<?php
        }
?>
				</div>	
				<div class="checkLabel">
					<label for="checkthis<?=$did?>">מוצג בדומיין זה</label>
					<div class="checkBoxWrap">
                        <input type="checkbox" name="domActive[<?=$did?>]" value="1" <?=(($dom['active'] || !$attrID) ? 'checked="checked"' : '')?> title="" id="checkthis<?=$did?>" />
						<label for="checkthis<?=$did?>"></label>
					</div>
				</div>
			</div>
<?php
    }
?>
		</div>

<?php
    $langs = languagTabs($langID);
?>

		<div class="allLangsDomainWrap">
<?php
    foreach($domains as $did => $domainName){
?>
			<div class="domainLangs domain <?=(($domainID == $did) ? 'active' : '')?>" data-id="<?=$did?>">
<?php
        foreach($langs as $lid => $langName){
            $attr = $l_attr[$did][$lid];
?>
				<div class="frmWrapSelect language <?=(($lid == $langID) ? 'active' : '')?>" data-id="<?=$lid?>">
					<div class="section">
						<div class="inptLine">
							<div class="label">שם מאפיין : </div>
							<input type="text" value="<?=js_safe($attr['defaultName'])?>" name="attrName[<?=$did?>][<?=$lid?>]" class="inpt" />
						</div>
					</div>
					<div class="section">
						<div class="inptLine">
							<div class="label">כותרת SEO : </div>
                            <input type="text" value="<?=js_safe($attr['attrHeadTitle'])?>" name="attrHeadTitle[<?=$did?>][<?=$lid?>]" class="inpt" />
						</div>
					</div>
					
					<div class="section">
						<div class="inptLine">
							<div class="label">שם לתצוגה בסינונים : </div>
                            <input type="text" value="<?=js_safe($attr['filterName'])?>" name="filterName[<?=$did?>][<?=$lid?>]" class="inpt" />
						</div>
					</div>
					<div style="clear:both;"></div>
                    <div class="section txtarea">
                        <div class="inptLine">
                            <div class="label">מילות מפתח : </div>
                            <textarea name="attrHeadKeys[<?=$did?>][<?=$lid?>]"><?=$attr['attrHeadKeys']?></textarea>
                        </div>
                    </div>
                    <div class="section txtarea">
                        <div class="inptLine">
                            <div class="label">תיאור SEO : </div>
                            <textarea name="attrHeadDesc[<?=$did?>][<?=$lid?>]"><?=$attr['attrHeadDesc']?></textarea>
                        </div>
                    </div>
					<div style="clear:both;"></div>
					<div class="section txtarea big">
						<div class="summerTtl">מידע נוסף</div>
						<textarea name="attrDesc" class="summernote"><?=$attr['attrDesc']?></textarea>
					</div>
				</div>
<?php
        }
?>
			</div>
<?php
    }
?>
				
		</div>

		<div class="section sub">
			<div class="inptLine">
				<?php if($attrID) { ?>
				<div class="deleteBtn" onclick="if(confirm('האם את/ה בטוח/ה שברצונך למחוק את הפריט?')){location.href='?attrdel=<?=$attrID?>';}">מחק</div>
				<?php } ?>
				<input type="submit" value="<?=($attrID ? "שמור" : "הוסף")?>" class="submit" /> 
			</div>
		</div>
	</form>
</div>
<!-- <link rel="stylesheet" href="../app/bootstrap.css">
<link rel="stylesheet" href="../app/dist/summernote.css">
<script src="../app/bootstrap.min.js"></script>
<script src="../app/dist/summernote.js?v=<?=time()?>"></script> -->

<script>
/*
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
*/
</script>
