<?php
include_once "../../../../bin/system.php";
include_once "../../../../bin/top_frame.php";
include_once "../../mainTopTabs.php";
include_once "../../../../_globalFunction.php";


$siteID=intval($_GET['siteID']);
$frameID=intval($_GET['frame']);
$siteName = $_GET['siteName'];


if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
        // main site data
		foreach($_POST['attributes'] as $key => $attr){
			$cp['rating'] = $attr;
			udb::update('sites_attributes', ['rating' => intval($attr)], '`siteID` = ' . $siteID.' AND `attrID`='.intval($key));
		}

		PromoManager::recalc_base_score($siteID);
	}
    catch (LocalException $e){
        // show error
    }
}

$que = "SELECT `sites_attributes`.* , attributes.defaultName  FROM `sites_attributes`
INNER JOIN `attributes` USING (attrID) WHERE `sites_attributes`.`siteID`=".$siteID." ORDER BY sites_attributes.rating DESC";

$attributes = udb::full_list($que);

?>
<div class="editItems">
	<div class="siteMainTitle"><?=$siteName?></div>
	<?=showTopTabs(0)?>
	<div class="manageItems" id="manageItems">
		<h1>דירוג מאפיינים</h1>
		<form method="post" class="attrsWrap">
			
			<?php if($attributes) {
			foreach($attributes as $attr) { ?>
			<div class="attrWrap">
				<label for="attr<?=$attr['attrID']?>"><?=$attr['defaultName']?></label>
				<select name="attributes[<?=$attr['attrID']?>]" id="attr<?=$attr['attrID']?>">
					<?php for($i=1;$i<=9;$i++) { ?>
						<option value="<?=$i?>" <?=$attr['rating']==$i?"selected":""?> ><?=$i?></option>
					<?php } ?>
				</select>
			</div>
			<?php } } ?>
			<div style="clear:both;"></div>
			<div class="section sub">
				<div class="inptLine">
					<input type="submit" value="שמור" class="submit">
				</div>
			</div>
		</form>
	</div>
</div>
<script type="text/javascript">


function closeTab(id){
	$(".popRoomContent").html('');
	$(".popRoom").hide();
	window.parent.parent.$('.tabCloser').show();
}




</script>