<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if('POST' == $_SERVER['REQUEST_METHOD']) {
	

	$cp=Array();
	$cp['title'] = inDb($_POST['title']);
	$cp["ifShow"] = intval($_POST['ifShow'])?"1":"0";
	$cp["text"] = ($_POST['text'] == "<br>") ? "" : $_POST['text'];
	$cp["LangID"] = intval($_POST['LangID']);
	$cp["visitor"] = $_POST['visitor'];

	$cp["day"] = date("Y-m-d", strtotime($_POST['day']));
	//$cp["showInHome"] = intval($_POST['showInHome'])?"1":"0";	
	//$cp["link"] = inDb($_POST['link']);	
	 


	if($pageID){
		udb::update("reviews", $cp, "reviewID =".$pageID);
	} else {
		//$cp["createDay"] = date("Y-m-d");
		$pageID = udb::insert("reviews", $cp);
	}

?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php
	
}

$position=1;
$menu = include "pages_menu.php";


if($pageID){
	$que="SELECT * FROM `reviews` WHERE reviewID=".$pageID." ";
	$page= udb::single_row($que);
}

?>
<div class="editItems">
    <h1><?=$page['title']?outDb($page['title']):"הוספת דף חדש"?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<input type="hidden" name="LangID" value="<?=$langID?>">
		<div class="frm" id="langTab1">
			<div class="section">
				<div class="inptLine">
					<div class="label">כותרת: </div>
					<input type="text" value="<?=outDb($page['title'])?>" name="title" class="inpt">
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
			<div class="section">
				<div class="inptLine">
					<div class="label">בחר שפה: </div>
					<select name="LangID">
						<option value="1" <?=($page['LangID'])==1?'selected':''?>>עברית</option>
						<option value="2" <?=($page['LangID'])==2?'selected':''?>>אנגלית</option>
					</select>
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
			<div class="section">
				<div class="inptLine">
					<div class="label">תאריך:</div>
					<input type="text" readonly value="<?=($page['day']!="1970-01-01" && $page['day']!="0000-00-00"?date("d/m/Y", strtotime($page['day'])):date("d/m/Y"))?>" name="day" class="inpt datepicker">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">כותב ההמלצה: </div>
					<input type="text" value="<?=outDb($page['visitor'])?>" name="visitor" class="inpt">
				</div>
			</div>	
			<div style="clear:both;"></div>
			<div class="section txtarea big">
				<textarea name="text" class="summernote"><?=outDb($page['text'])?></textarea>
			</div>
		
		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$page['reviewID']?"שמור":"הוסף"?>" class="submit">
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
		dateFormat: 'yy/mm/dd',
		changeMonth: true,
		changeYear: true
	});
});
</script>