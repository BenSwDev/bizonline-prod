<?php
include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";

$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


?>

<div class="editItems">
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="topInput">
			<div class="labelTo">שם מאפיין</div>
			<input type="text" placeholder="שם מערכת" name="">
			<div class="checkLabel">
				<label for="show1">מוצג</label>
				<div class="checkBoxWrap">
					<input type="checkbox" name="" id="show1">
					<label for="show1"></label>
				</div>
			</div>
		</div>
		<div class="miniTabs">
			<div class="tab"><p>דומיין 1</p></div>
			<div class="tab"><p>דומיין 2</p></div>
			<div class="tab"><p>דומיין 3</p></div>
			<div class="tab"><p>דומיין 4</p></div>
		</div>
		<div class="iconPicWrap">
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
				<div class="section">
					<div class="inptLine">
						<div class="label">איקון: </div>
						<input type="file" name="icon" class="inpt" value="<?=$page['icon']?>">
					</div>
				</div>
				<?php if($page['icon']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../gallery/<?=$page['icon']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
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
			<div class="checkLabel">
				<label for="show2">מוצג בדומיין זה</label>
				<div class="checkBoxWrap">
					<input type="checkbox" name="domainShow" id="show2">
					<label for="show2"></label>
				</div>
			</div>
		</div>
		<div class="miniTabs">
			<div class="tab"><p>אנגלית</p></div>
			<div class="tab"><p>עברית</p></div>
			<div class="tab"><p>רוסית</p></div>
			<div class="tab"><p>צרפתית</p></div>
		</div>
		<div class="frm">
			<div class="mainSectionWrapper">
				<div class="sectionName">SEO</div>
				<div class="section txtarea">
					<div class="inptLine">
						<div class="label">מילות מפתח</div>
						<textarea name="seoKeyword"><?=outDb($page['seoKeyword'])?></textarea>
					</div>
				</div>
				<div class="section txtarea">
					<div class="inptLine">
						<div class="label">תאור דף</div>
						<textarea name="seoDesc"><?=outDb($page['seoDesc'])?></textarea>
					</div>
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">תוכן עמוד</div>
				<div class="section">
					<div class="inptLine">
						<div class="label">כותרת: </div>
						<input type="text" value='<?=stripslashes(htmlspecialchars($page['MainPageTitle'], ENT_QUOTES))?>' name="MainPageTitle" class="inpt">
					</div>
				</div>
				<div class="section">
					<div class="inptLine">
						<div class="label">קישור: </div>
						<input type="text" value="<?=outDb($page['link'])?>" name="link" class="inpt">
					</div>
				</div>
				<div class="section">
					<div class="inptLine">
						<div class="label">כתובת: </div>
						<input type="text" value="<?=outDb($page['address'])?>" name="link" class="inpt">
					</div>
				</div>
				<div class="section">
					<div class="inptLine">
						<div class="label">שייך גלריה </div>
						<select name="galleryID" class="inpt">
							<option value="<?=outDb($page['galleryID'])?>"></option>
						</select>
					</div>
				</div>
				<div style="clear:both;"></div>
				<div class="section txtarea big">
					<div class="label">תיאור קצר: </div>
					<textarea name="ShortDesc" class="textEditor"><?=outDb($page['ShortDesc'])?></textarea>
				</div>
				<div style="clear:both;"></div>
				<div class="section txtarea big">
					<div class="label">מידע נוסף</div>
					<textarea name="html_text"  class="textEditor"><?=outDb($page['html_text'])?></textarea>
				</div>
			</div>
			<div class="section sub">
				<div class="inptLine">
					<input type="submit" value="<?=$page['MainPageID']?"שמור":"הוסף"?>" class="submit">
				</div>
			</div>
		</div>
	</form>
</div>
<!-- <link rel="stylesheet" href="../app/bootstrap.css">
<link rel="stylesheet" href="../app/dist/summernote.css">
<script src="../app/bootstrap.min.js"></script>
<script src="../app/dist/summernote.js?v=<?=time()?>"></script> -->
<script src="../../app/tinymce/tinymce.min.js"></script>

<script type="text/javascript">
	$('.sectionName').click(function(){
		$(this).parent().toggleClass('open');
	});
</script>


<script>

tinymce.init({
  selector: 'textarea.textEditor' ,
  height: 500,  
 plugins: [
    "advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
    "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
    "table contextmenu directionality emoticons template textcolor paste  textcolor colorpicker textpattern"
  ],
  fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
  toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
  toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor",
  toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking template pagebreak restoredraft"

});
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