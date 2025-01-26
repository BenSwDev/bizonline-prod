<?php
include_once "bin/system.php";
include_once "bin/top.php";

$langID = intval($_GET['langID'])?intval($_GET['langID']):1;

$domainID = intval($_GET['domainID'])?intval($_GET['domainID']):1;

$que="SELECT LangID, LangName FROM language WHERE 1";
$languages = udb::full_list($que);

$que="SELECT domainID, domainName FROM domains WHERE domainMenu=1";
$domains = udb::full_list($que);

if('POST' == $_SERVER['REQUEST_METHOD']) {

	foreach($_POST as $key=>$val){
		if($key!="LOGO" && $key!="summerBG" && $key!="LangID")
		{
			$cp=Array();
			$cp['content'] = $val;
			$cp['system'] = $key;
			$cp['LangID'] = intval($_POST['LangID']);
            $cp['domainID'] = intval($_POST['domainID']);
			$que="SELECT system FROM configurations WHERE system='".$key."' AND LangID=".intval($_POST['LangID']). " AND domainID=".intval($_POST['domainID']);
			$test=udb::single_row($que);
			if($test){
				udb::update("configurations", $cp, "system ='".$key."' AND LangID=".intval($_POST['LangID']));
			} else {
				udb::insert("configurations", $cp);
			}
		}
	}

	/*if(isset($_FILES)){
		foreach($_FILES as $key=>$val){
			$photo = pictureUpload($key,"../gallery/");
			if($photo[0]['file']) {
				$cp = Array();
				$cp["content"] = $photo[0]['file'];
				$cp['system'] = $key;
				$cp['LangID'] = intval($_POST['LangID']);

				$que="SELECT system FROM configurations WHERE system='".$key."' AND LangID=".intval($_POST['LangID']);
				$test=udb::single_row($que);
				if($test){
					udb::update("configurations", $cp, "system ='".$key."' AND LangID=".intval($_POST['LangID']));
				} else {
					udb::insert("configurations", $cp);
				}
			}
		}
	}*/
}


$que = "SELECT * FROM configurations WHERE LangID=".$langID . " and domainID=".$domainID;
$configurations = udb::key_row($que, "system");

if(!$configurations){
	$default=true;
	$que = "SELECT * FROM configurations WHERE LangID=1 and domainID=".$domainID ;
	$configurations = udb::key_row($que, "system");
} ?>


<div class="editItems">
    <h1>הגדרות האתר</h1>
	<div class="miniTabs general">
        <?php foreach($languages as $lang){ ?>
            <div class="tab<?=$lang['LangID']==$langID?" active":""?>" onclick="window.location.href='/cms/configurations.php?langID=<?=$lang['LangID']?>'"><p><?=$lang['LangName']?></p></div>
        <?php } ?>
        <?php foreach($domains as $domain){ ?>
            <div class="tab<?=$domain['domainID']==$domainID?" active":""?>" onclick="window.location.href='/cms/configurations.php?langID=<?=$langID?>&domainID=<?=$domain['$domainID']?>'"><p><?=$domain['domainName']?></p></div>
        <?php } ?>
        <?php include "configTabs.php"; ?>
    </div>
    <div class="miniTabs general" style="display: block;">
        <?php foreach($domains as $domain){ ?>
            <div class="tab<?=$domain['domainID']==$domainID?" active":""?>" onclick="window.location.href='/cms/configurations.php?langID=<?=$langID?>&domainID=<?=$domain['domainID']?>'"><p><?=$domain['domainName']?></p></div>
        <?php } ?>
    </div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="LangID" value="<?=$langID?>">
        <input type="hidden" name="domainID" value="<?=$domainID?>">
		<div class="section">
			<div class="inptLine">
				<div class="label">כותרת: </div>
				<input type="text" value="<?=stripslashes(htmlspecialchars($configurations['TITLE']['content'], ENT_QUOTES))?>" name="TITLE" class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">טלפון: </div>
				<input type="text" value="<?=$configurations['WEBPHONE']['content']?>" name="WEBPHONE" class="inpt" style="direction:ltr">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">כתובת: </div>
				<input type="text" value="<?=$configurations['ADDRESS']['content']?>" name="ADDRESS" class="inpt" style="direction:ltr">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">אימייל: </div>
				<input type="text" value="<?=$configurations['ADMINMAIL']['content']?>" name="ADMINMAIL" class="inpt" style="direction:ltr">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">פייסבוק: </div>
				<input type="text" value="<?=$configurations['FACEBOOK']['content']?>" name="FACEBOOK" class="inpt" style="direction:ltr">
			</div>
		</div>
		<?php /*?>
		<div class="section">
			<div class="inptLine">
				<div class="label">אינסטגרם: </div>
				<input type="text" value="<?=$configurations['INSTAGRAM']['content']?>" name="INSTAGRAM" class="inpt" style="direction:ltr">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">יוטיוב: </div>
				<input type="text" value="<?=$configurations['YOUTUBE']['content']?>" name="YOUTUBE" class="inpt" style="direction:ltr">
			</div>
		</div>
		<?php */?>
		<div  style="clear:both;"></div>
        <div class="section txtarea">
            <div class="inptLine">
                <div class="label">SEO HOMEPAGETITLE </div>
                <textarea name="HOMEPAGETITLE"><?=$configurations['HOMEPAGETITLE']['content']?></textarea>
            </div>
        </div>
		<div class="section txtarea">
			<div class="inptLine">
				<div class="label">SEO Description </div>
				<textarea name="DESCRIPTION"><?=$configurations['DESCRIPTION']['content']?></textarea>
			</div>
		</div>
		<div class="section txtarea">
			<div class="inptLine">
				<div class="label">SEO Keywords </div>
				<textarea name="KEYWORDS"><?=$configurations['KEYWORDS']['content']?></textarea>
			</div>
		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="שמור" class="submit">
			</div>
		</div>


	</form>
</div>
<link rel="stylesheet" href="app/bootstrap.css">
<link rel="stylesheet" href="app/dist/summernote.css">
<script src="app/bootstrap.min.js"></script>
<script src="app/dist/summernote.js?v=<?=time()?>"></script>
<script>
	var addZimer = function (context) {
		var ui = $.summernote.ui;
		var button = ui.button({
			contents: '<i class="fa fa-object-ungroup"/> הוספת קישור לצימר',
			tooltip: 'הוספת קישור לצימר',
			click: function () {
				var zimerID = prompt("הזן מספר צימר", "");

				if (zimerID != null) {
					context.invoke('editor.insertText', '@@z-'+zimerID+'x');
				}
			}
		});
		return button.render();
	};
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
				['style', ['style', 'bold', 'italic', 'underline', 'clear']],
				['fontname', ['fontname']],
				['fontsize', ['fontsize']],
				['para', ['ul', 'ol', 'paragraph']],
				['height', ['height']],
				['insert', ['picture', 'link','video']],
				['addZimer', ['addZimer']],
				['view', ['codeview']]
			],
			popover: {
				image: [
					['alt', ['addAlt']],
					['imagesize', ['imageSize100', 'imageSize50', 'imageSize25']],
					['float', ['floatLeft', 'floatRight', 'floatNone']],
					['remove', ['removeMedia']]
				]},
			buttons: {
				addZimer: addZimer,
				addAlt: addAlt
			},
			height: 300
		});
	});


</script>
<?php include_once "bin/footer.php";