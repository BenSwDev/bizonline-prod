<?php
include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "../../_globalFunction.php";

$langID     = typemap($_GET['LangID'], 'int') ?: 1;
$questionID = typemap($_GET['pageID'], 'int');

if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
        $questionID = typemap($_POST['questionID'], 'int');
        $data = typemap($_POST, [
            'defaultName'  => 'string',
            'active'       => 'int',
            'questionTitle' => ['int' => 'string']
        ]);

        if (!$data['defaultName'])
            throw new LocalException('חייב להיות שם');

        $que = ['active' => $data['active'], 'questionTitle' => $data['defaultName']];
        if ($questionID)
            udb::update('questions', $que, '`questionID` = ' . $questionID);
        else {
            udb::query("LOCK TABLES `questions` WRITE");
            $que['showOrder'] = udb::single_value("SELECT IFNULL(MAX(`showOrder`) + 1, 1) FROM `questions` WHERE 1");

            $questionID = udb::insert('questions', $que);
            udb::query("UNLOCK TABLES");
        }

        $list = [];
        $langs = udb::single_column("SELECT `langID` FROM `language` WHERE 1", 0);
        foreach($langs as $lid)
            $list[] = "(" . $questionID . ", " . $lid . ", '" . udb::escape_string($data['questionTitle'][$lid]) . "')";

        if (count($list))
            udb::query("INSERT INTO `questions_langs`(`questionID`, `langID`, `questionTitle`) VALUES" . implode(',', $list) . " ON DUPLICATE KEY UPDATE `questionTitle` = VALUES(`questionTitle`)");

        //reloadParent();
    }
    catch (LocalException $e){
        // show error
    }
	echo '<script>window.parent.location.reload(); window.parent.closeTab();</script>';
}

$base = udb::single_row("SELECT * FROM `questions` WHERE `questionID` = " . $questionID);
$categories = udb::key_row("SELECT * FROM `questions_langs` WHERE `questionID` = " . $questionID, 'langID');

?>
<style type="text/css">
.topInput{}
.topInput .labelTo{display: inline-block;vertical-align: middle;}
.topInput input[type="text"]{max-width: 300px;width: 100%;display: inline-block;vertical-align: middle;}
.topInput .checkLabel{display: inline-block;vertical-align: middle;}

.checkLabel{margin-right:10px;}
.checkLabel > label{font-size: 16px;color: #666;display: inline-block;vertical-align: middle;font-weight: bold;}
.checkLabel .checkBoxWrap{position:relative;width: 20px;height:20px;cursor:pointer;box-sizing:border-box;border:1px solid #666;background:#fff;display: inline-block;vertical-align: middle;border-radius:4px;}
.checkLabel .checkBoxWrap input[type="checkbox"]{display: none;}
.checkLabel .checkBoxWrap label{width: 100%;height: 100%;cursor: pointer;position: absolute;
top: 0;left: 0;}
.checkLabel .checkBoxWrap label::after{content: '';width: 20px;height: 5px;
position: absolute;top: 0;left: 1px;border: 3px solid #666;border-top: none;border-right: none;background: transparent;opacity: 0;-webkit-transform: rotate(-45deg);transform: rotate(-45deg);}
.checkLabel .checkBoxWrap input:checked + label:after {opacity: 1;}
.iconPicWrap{margin:15px 0;}
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
	<form method="POST" id="myform" enctype="multipart/form-data">
        <input type="hidden" name="questionID" value="<?=$questionID?>" />
		<div class="topInput">
			<div class="labelTo">שאלה</div>
			<input type="text" placeholder="שאלה" name="defaultName" value="<?=js_safe($base['questionTitle'])?>" />
			<div class="checkLabel">
				<label for="show1">מוצג</label>
				<div class="checkBoxWrap">
					<input type="checkbox" name="active" id="active" value="1" <?=(($base['active'] || !$questionID) ? 'checked="checked"' : '')?> />
					<label for="active"></label>
				</div>
			</div>
		</div>

<?php
    $langs = languagTabs($langID);

    foreach($langs as $lid => $langName){
        $category = $categories[$lid];
?>
		<div class="frmWrapSelect language <?=(($lid == $langID) ? 'active' : '')?>" data-id="<?=$lid?>">
			<div class="section">
				<div class="inptLine">
					<div class="label">שאלה : </div>
					<input type="text" value="<?=js_safe($category['questionTitle'])?>" name="questionTitle[<?=$lid?>]" class="inpt" />
				</div>
			</div>
			<!-- div class="section">
				<div class="inptLine">
					<div class="label">קישור: </div>
					<input type="text" value="" name="link" class="inpt">
				</div>
			</div>
			<div style="clear:both;"></div>
			<div class="section txtarea">
				<div class="inptLine">
					<div class="label">תיאור קצר: </div>
					<textarea name="ShortDesc"></textarea>
				</div>
			</div>
			<div style="clear:both;"></div>
			<div class="section txtarea big">
				<div class="summerTtl">מידע נוסף</div>
				<textarea name="html_text"  class="summernote"></textarea>
			</div -->
		</div>
<?php
    }
?>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=($questionID ? "שמור" : "הוסף")?>" class="submit" />
			</div>
		</div>
	</form>
</div>
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
