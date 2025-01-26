<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$customID = intval($_GET['customID']);
$siteID = intval($_GET['siteID']);


if ('POST' == $_SERVER['REQUEST_METHOD']) {

if(intval($_POST['portalID']) == 1){
	$array=Array();
	$array['customTitle']=inDB($_POST['customTitle']);
	$array["ifShow"] = intval($_POST['ifShow'])?"1":"0";
	$array['html_text']=$_POST['html_text'];
	udb::update("sitesCustoms", $array, "customID=".$customID);
} else {
	$cp=Array();
	$cp['customKey']=inDB($_POST['customKey']);
	$cp['customTitle']=inDB($_POST['customTitle']);
	$cp["ifShow"] = intval($_POST['ifShow'])?"1":"0";
	$cp['html_text']=$_POST['html_text'];
	$cp['PortalID']=intval($_POST['portalID']);
	$cp['customID']=$customID;
	
	$que="SELECT customID, PortalID FROM sitesCustoms_text WHERE customID=".$customID." AND PortalID=".intval($_POST['portalID'])." ";
	$custom=udb::single_row($que);
	if($custom){
		udb::update("sitesCustoms_text", $cp, "customID=".$customID." AND PortalID=".intval($_POST['portalID'])."");
	} else {
		udb::insert("sitesCustoms_text", $cp);
	}
}


?>
		<script>
			window.parent.location.reload();
			window.parent.closeTab('frame_<?=$customID?>_<?=$siteID?>');	
		</script>
<?php

}

$que="SELECT * FROM `sitesCustoms` WHERE siteID = ".$siteID." AND customID=".$customID."";
$add= udb::single_row($que);


$que="SELECT * FROM `portals` WHERE portalID!=1";
$portals= udb::key_row($que, "portalID");


$que="SELECT `sitesCustoms_text`.* FROM `sitesCustoms_text` INNER JOIN sitesCustoms USING(customID) WHERE sitesCustoms.siteID = ".$siteID." AND sitesCustoms.customID=".$customID."";
$portalData= udb::key_row($que,Array("PortalID"));


?>
<div class="editItems">
	<div class="miniTabs general" style="margin-right:50px;">	
		<div class="tab active" data-portalid="1"><p>צימרטופ</p></div>
		<?php foreach($portals as $portal){ ?>
			<div class="tab<?=$portal['portalID']==1?" active":""?>" data-portalid="<?=$portal['portalID']?>"><p><?=$portal['portalName']?></p></div>
		<?php } ?>
	</div>
	<form method="POST" class="frm"  enctype="multipart/form-data" id="portalForm1" style="display:block">
		<input type="hidden" name="portalID" value="1">
		<div class="section">
			<div class="inptLine">
				<div class="label">כותרת: </div>
				<input type="text" value="<?=$add['customTitle']?>" name="customTitle" class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">מוצג באתר: </div>
				<div class="chkBox">
					<input type="checkbox" value="1" <?=$add['ifShow']?"checked":""?> name="ifShow" id="ifShow">
					<label for="ifShow"></label>
				</div>
			</div>
		</div>
		<div  style="clear:both;"></div>

		<textarea name="html_text" class="summernote"><?=outDB($add['html_text'])?></textarea>


		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="שמור" class="submit">
			</div>
		</div>
	</form>
	<?php foreach($portals as $portal){ 
	if($portalData[$portal['portalID']]){
		$add = $portalData[$portal['portalID']];
	}
	?>
	<form method="POST" class="frm" enctype="multipart/form-data" id="portalForm<?=$portal['portalID']?>" style="display:<?=$portal['portalID']==1?"block":"none"?>">
		<input type="hidden" name="portalID" value="<?=$portal['portalID']?>">
		<input type="hidden" name="customKey" value="<?=$add['customKey']?>">
		<div class="section">
			<div class="inptLine">
				<div class="label">כותרת: </div>
				<input type="text" value="<?=$add['customTitle']?>" name="customTitle" class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">מוצג באתר: </div>
				<div class="chkBox">
					<input type="checkbox" value="1" <?=$add['ifShow']?"checked":""?> name="ifShow" id="ifShow<?=$portal['portalID']?>">
					<label for="ifShow<?=$portal['portalID']?>"></label>
				</div>
			</div>
		</div>
		<div  style="clear:both;"></div>

		<textarea name="html_text" class="summernote"><?=outDB($add['html_text'])?></textarea>


		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="שמור" class="submit">
			</div>
		</div>
	</form>
	<?php } ?>
</div>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
<link rel="stylesheet" href="../app/dist/summernote.css">

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
<script src="../app/dist/summernote.js?v=<?=time()?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function(){       
	$('.summernote').summernote({
		toolbar: [
		['style', ['bold', 'italic', 'underline', 'clear']],
		['fontname', ['fontname']],
		['fontsize', ['fontsize']],
		['para', ['ul', 'ol', 'paragraph']],
		['height', ['height']],
		['insert', ['picture', 'link','video']]
		], height: 300
	});


$('.summernote').summernote({
    callbacks: {
        onPaste: function (e) {
            var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
            e.preventDefault();
            document.execCommand('insertText', false, bufferText);
        }
    }
});


});

$(".tab").click(function(){
	$(".tab").removeClass("active");
	$(this).addClass("active");

	var ptID = $(this).data("portalid");
	$(".frm").css("display","none");
	console.log(ptID);
	$("#portalForm"+ptID).css("display","block");
});
</script>