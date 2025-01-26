<?php

include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "../../_globalFunction.php";

/*$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;*/

$langID   = typemap($_GET['LangID'], 'int') ?: 1;
$ansID   = typemap($_GET['pageID'], 'int');
$domainID = $ansID ? ($_SESSION['cms']['domainID'] ?: 0) : 0;

if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
        $ansID = typemap($_POST['ansID'], 'int');
        $data = typemap($_POST, [
            'active'       => 'int',
            'answerTitle'      => ['int' => ['int' => 'string']],
            'answerText'      => ['int' => ['int' => 'text']],
			'defaultTitle'      => 'string',
            'defaultText'      => 'text',
			'questionID' => 'int'
        ]);
		
        // if (!$data['answerTitle'])
            // throw new LocalException('חייב להיות שם');
        if (!$data['questionID']){
            $data['questionID'] = 1;
			//udb::single_value("SELECT `questionID` FROM `questions` WHERE `active` = 1 ORDER BY `questionID` DESC LIMIT 1");      // last added category
		}

        $que = ['active' => $data['active'],'questionID' => $data['questionID'] ,  'answerTitle' => $data['defaultTitle'],  'answerText' => $data['defaultText']];
		
		
		
        if ($ansID){
            udb::update('answers', $que, '`ansID` = ' . $ansID);
        } else {
            udb::query("LOCK TABLES `answers` WRITE");
            $que['showOrder'] = udb::single_value("SELECT IFNULL(MAX(`showOrder`) + 1, 1) FROM `answers` WHERE `questionID` = " . $data['questionID']);
            $ansID = udb::insert('answers', $que);
            udb::query("UNLOCK TABLES");
        }

        $dlist = [];
        $doms  = udb::single_column("SELECT `domainID` FROM `domains` WHERE 1", 0);
        $langs = udb::single_column("SELECT `langID` FROM `language` WHERE 1", 0);
        foreach(DomainList::get() as $did => $dom){
			
            $dlist[] = "(" . $ansID . ", " . $did . ", " . intval($data['domActive'][$did]) . ")";

            $list = [];
            foreach($langs as $lid) {
				$list[] = "(" . $ansID . ", " . $did .  ", " . $lid . ", '" . udb::escape_string($data['answerTitle'][$did][$lid]) . "', '" . udb::escape_string($data['answerText'][$did][$lid]) . "')";
			}
                
					   
            count($list) and udb::query("INSERT INTO `answers_langs`(`ansID`, `domainID`, `langID`, `answerTitle`,  `answerText`) 
					   VALUES " . implode(',', $list) . " ON DUPLICATE KEY UPDATE `answerTitle` = VALUES(`answerTitle`), `answerText` = VALUES(`answerText`)");
        }
        count($dlist) and udb::query("INSERT INTO `answers_domains`(`ansID`, `domainID`, `active`) VALUES" . implode(',', $dlist) . " ON DUPLICATE KEY UPDATE `active` = VALUES(`active`)");


		$dom = udb::single_row("SELECT * FROM `answers_domains` WHERE `domainID` = 1 AND `ansID` = " . $ansID);
		$dom['domainID'] = 1;
		udb::insert('answers_domains', $dom, true);

		$doms = udb::single_list("SELECT * FROM `answers_langs` WHERE `domainID` = 1 AND `ansID` = " . $ansID);
		foreach($doms as $dom){
			$dom['domainID'] = 1;
			udb::insert('answers_langs', $dom, true);
		}

        //reloadParent();
    }
    catch (LocalException $e){
        // show error
    } ?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php 

}
 

$base = udb::single_row("SELECT * FROM `answers` WHERE `ansID` = " . $ansID);
$d_attr = udb::key_row("SELECT * FROM `answers_domains` WHERE `ansID` = " . $ansID, 'domainID');
$l_attr = udb::key_row("SELECT * FROM `answers_langs` WHERE `ansID` = " . $ansID, ['domainID', 'langID']);
$categories = udb::full_list("SELECT `questionID`, `questionTitle` FROM `questions` WHERE 1 ORDER BY `showOrder`");
?>

<div class="editItems">
	<form method="POST" id="myform" enctype="multipart/form-data">
        <input type="hidden" name="ansID" value="<?=$ansID?>" />
		<div class="topInput">
			<div class="inputLblWrap">
				<div class="labelTo">כותרת תשובה: </div>
				<input type="text" placeholder="כותרת תשובה" name="defaultTitle" value="<?=js_safe($base['answerTitle'])?>" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">תשובה מלאה: </div>
				<textarea placeholder="כותרת תשובה" name="defaultText" value="<?=js_safe($base['answerText'])?>"></textarea>
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">שאלה</div>
				
				<select name="questionID">
				<?
				$questions = udb::full_list("select * from questions");
				foreach($questions  as $question) {
				?>
					<option value="<?=$question['questionID']?>" <?if($base['questionID'] == $question['questionID']) echo ' selected '?> ><?=$question['questionTitle']?></option>
				<?
				}
				?>
				</select>
			</div>
			<div class="checkLabel">
				<label for="active">מוצג</label>
				<div class="checkBoxWrap">
                    <input type="checkbox" name="active" id="active" value="1" <?=(($base['active'] || !$ansID) ? 'checked="checked"' : '')?> title="" />
					<label for="active"></label>
				</div>
			</div>
			
			
			
			<?/*
			<div class="inputLblWrap">
				<div class="labelTo">שייך לקטגוריה</div>
				<select name="category">
<?php
    foreach($categories as $cid => $cname)
        echo '<option value="' . $cname['questionID'] . '" ' . (($cname['questionID'] == $base['questionID']) ? 'selected="selected"' : '') . '>' . $cname['questionTitle'] . '</option>';
?>
				</select>
			</div>
		</div>*/?>

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
					
<?php
        if($dom['attrIcon']){
?>
					
<?php
        }
?>
				</div>
				<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
					
<?php
        if($dom['attrPic']){
?>
					
<?php
        }
?>
				</div>	
				<div class="checkLabel">
					<label for="checkthis<?=$did?>">מוצג בדומיין זה</label>
					<div class="checkBoxWrap">
                        <input type="checkbox" name="domActive[<?=$did?>]" value="1" <?=(($dom['active'] || !$ansID) ? 'checked="checked"' : '')?> title="" id="checkthis<?=$did?>" />
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
							<div class="label">
							כותרת תשובה: </div>
							<input type="text" value="<?=js_safe($attr['answerTitle'])?>" name="answerTitle[<?=$did?>][<?=$lid?>]" class="inpt" />
						</div>
					</div>
					
					
					<div style="clear:both;"></div>
					<div class="section txtarea big">
						<div class="summerTtl">מידע נוסף</div>
						<textarea name="answerText[<?=$did?>][<?=$lid?>]"  class="summernote"><?=$attr['answerText']?></textarea>
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
				<input type="submit" value="<?=($ansID ? "שמור" : "הוסף")?>" class="submit" />
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
