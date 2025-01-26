<?php
include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "../../_globalFunction.php";

// Gal Working... 


$langID     = typemap($_GET['LangID'], 'int') ?: 1;
$categoryID = typemap($_GET['pageID'], 'int');
$domainID   = DomainList::active();


if($_GET['attrdel'] != 0){
	udb::query("DELETE FROM  attributes_categories where categoryID=".intval($_GET['attrdel']));
	udb::query("DELETE FROM  attributes_categories_langs where categoryID=".intval($_GET['attrdel']));
	$allAttrs = udb::single_column("select attrID FROM `attributes` WHERE categoryID=".intval($_GET['attrdel']));
	$allAttrs = implode(",",$allAttrs);
	echo $allAttrs;
	if($allAttrs) {
		udb::query("DELETE FROM `attributes` WHERE attrID in (".$allAttrs.")");
		udb::query("DELETE FROM `attributes_langs` WHERE attrID in (".$allAttrs.")");
		udb::query("DELETE FROM `attributes_domains` WHERE attrID in (".$allAttrs.")");
		//Delete all other uses
		udb::query("DELETE FROM `rooms_attributes` WHERE attrID in (".$allAttrs.")");
		udb::query("DELETE FROM `sites_attributes` WHERE attrID in (".$allAttrs.")");	
	}
	
	echo '<script>window.parent.location.reload(); window.parent.closeTab();</script>';	


}

if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
        $categoryID = typemap($_POST['categoryID'], 'int');
        $data = typemap($_POST, [
            'defaultName'  => 'string',
            'active'       => 'int',
            'categoryName' => ['int' => 'string']
        ]);

        if (!$data['defaultName'])
            throw new LocalException('חייב להיות שם');
		$photo = pictureUpload('picture',"../../../gallery/");
		
		//print_r($photo);
        $que = ['active' => $data['active'], 'categoryName' => $data['defaultName'], 'domainID' => $domainID];
		if($photo){
			$que["iconImage"] = $photo[0]['file'];
		}
        if ($categoryID)
            udb::update('attributes_categories', $que, '`categoryID` = ' . $categoryID);
        else {
            udb::query("LOCK TABLES `attributes_categories` WRITE");
            $que['showOrder'] = udb::single_value("SELECT IFNULL(MAX(`showOrder`) + 1, 1) FROM `attributes_categories` WHERE 1");

            $categoryID = udb::insert('attributes_categories', $que);
            udb::query("UNLOCK TABLES");
        }

        $list = [];
        $langs = udb::single_column("SELECT `langID` FROM `language` WHERE 1", 0);
        foreach($langs as $lid)
            $list[] = "(" . $categoryID . ", " . $lid . ", '" . udb::escape_string($data['categoryName'][$lid]) . "')";

        if (count($list))
            udb::query("INSERT INTO `attributes_categories_langs`(`categoryID`, `langID`, `categoryName`) VALUES" . implode(',', $list) . " ON DUPLICATE KEY UPDATE `categoryName` = VALUES(`categoryName`)");

        //reloadParent();
    }
    catch (LocalException $e){
        // show error
    }
	//echo '<script>window.parent.location.reload(); window.parent.closeTab();</script>';
}

$base = udb::single_row("SELECT * FROM `attributes_categories` WHERE `categoryID` = " . $categoryID);
$categories = udb::key_row("SELECT * FROM `attributes_categories_langs` WHERE `categoryID` = " . $categoryID, 'langID');

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
        <input type="hidden" name="categoryID" value="<?=$categoryID?>" />
		<div class="topInput">
			<div class="labelTo">שם קטגוריה</div>
			<input type="text" placeholder="שם קטגוריה" name="defaultName" value="<?=js_safe($base['categoryName'])?>" />
			<div class="checkLabel">
				<label for="show1">מוצג</label>
				<div class="checkBoxWrap">
					<input type="checkbox" name="active" id="active" value="1" <?=(($base['active'] || !$categoryID) ? 'checked="checked"' : '')?> />
					<label for="active"></label>
				</div>
			</div>
		</div>
		<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
			<div class="section">
				<div class="inptLine">
					<div class="label">תמונה: </div>
					<input type="file" name="picture" class="inpt" value="">
				</div>
			</div>
			<?php if($base['iconImage']){ ?>
			<div class="section">
				<div class="inptLine">
					<img src="/gallery/<?=$base['iconImage']?>" style="width:100%">
				</div>
			</div>
			<?php } ?>
		</div>

<?php
    $langs = languagTabs($langID);

    foreach($langs as $lid => $langName){
        $category = $categories[$lid];
		//print_r($categories);
?>
		<div class="frmWrapSelect language <?=(($lid == $langID) ? 'active' : '')?>" data-id="<?=$lid?>">
			<div class="section">
				<div class="inptLine">
					<div class="label">שם קטגוריה : </div>
					<input type="text" value="<?=js_safe($category['categoryName'])?>" name="categoryName[<?=$lid?>]" class="inpt" />
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
				<?php if($categoryID) { ?>
				<div class="deleteBtn" onclick="if(confirm('האם את/ה בטוח/ה שברצונך למחוק את הפריט?')){location.href='?attrdel=<?=$categoryID?>';}">מחק</div>
				<?php } ?>
				<input type="submit" value="<?=($categoryID ? "שמור" : "הוסף")?>" class="submit" />
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
