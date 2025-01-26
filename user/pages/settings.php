<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

$asid = $_CURRENT_USER->active_site() ?: 0;

//$sid = intval($_GET['sid']);
//if ($sid && !in_array($sid, $_CURRENT_USER->sites()))
//    $sid = 0;


$snames = udb::full_list("SELECT `siteID`, `siteName`, `sendReviews`, `publishReviews` FROM `sites` WHERE `siteID` IN (" . $asid . ")");
?>
<link href="assets/css/style_ctrl.php?dir=<?=$dir?>&fileName=settings&v=<?=rand()?>" rel="stylesheet">
<div class="last-orders">
<div class="title">הגדרת חוות דעת</div>
<div style="">
	
	<div>
		שליחת תזכורת אוטומטית למילוי חוות דעת בסיום הזמנה<br>
	</div>
<?
        foreach($snames as $sname){?>
		<div style="border-bottom:1px #ccc solid;padding:20px 0">
			<div class="inputLblWrap vmiddle" onclick="setReview(<?=$sname["siteID"]?>)" >
				<label class="switch vmiddle">
					<input disabled type="checkbox" value="1" name="review<?=$sname["siteID"]?>"  <?=$sname["sendReviews"]? "checked" : ""?>>
					<span class="slider round"></span>
				</label>
				<div class="labelText">הפעל שליחה אוטומטית <?=$sname["siteName"]?></div>
			</div>
			<div class="inputLblWrap vmiddle" onclick="setPublish(<?=$sname["siteID"]?>)" >
				<label class="switch vmiddle">
					<input disabled type="checkbox" value="1" name="publishReviews<?=$sname["siteID"]?>"  <?=$sname["publishReviews"]? "checked" : ""?>>
					<span class="slider round"></span>
				</label>
				<div class="labelText"><?=$sname["siteName"]?> מאשר הצגת חוות הדעת של הלקוחות ברשת אתרי	<img style="width:80px;margin:-3px 5px" src="https://www.spaplus.co.il/webimages/newSite/logoMobile.png"></div>
			</div>
		</div>
		<?}?>
			

	<style>
	</style>

	
</div>
</div>


<style>


</style>

<script>
function setReview(siteid){
	$.post("ajax_settings.php",{id:siteid,type:1},function(res){
		var elem = $("input[name='review"+siteid+"']");		
		if(res==0){
			elem.prop("checked", false);
		}else{		
			elem.prop("checked", true);
		}
	});
}

function setPublish(siteid){
	$.post("ajax_settings.php",{id:siteid, type:2},function(res){
		var elem = $("input[name='publishReviews"+siteid+"']");		
		if(res==0){
			elem.prop("checked", false);
		}else{		
			elem.prop("checked", true);
		}
	});
}



</script>
