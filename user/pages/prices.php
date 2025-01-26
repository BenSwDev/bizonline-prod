<?
$que = "SELECT sites.siteID, sites.siteName FROM sites WHERE `sites`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ")";
$sites = udb::full_list($que);


?>
<div class="tabs">
	<? foreach($sites as $site){?>
		<div id="tab<?=$site["siteID"]?>" onclick="changeTab(<?=$site["siteID"]?>)" class="tab <?=$site["siteID"]==SITE_ID? "active" : ""?>"><?=$site["siteName"]?></div>
	<?}?>
</div>
<?php
    $wsite = ReferenceMap::from_local(SITE_ID, 'wubook');
    if ($wsite && SITE_ID == 389){
?>
    <div class="tabs">
        <a href="?page=prices" class="tab active" style="margin-right:10px">מחירון רגיל</a>
        <a href="?page=wubook_prices" class="tab" style="margin-right:10px">מחירון Wubook</a>
    </div>
<?php
    }
?>

<iframe id="prices" src="prices.php?asite=<?=SITE_ID?>" ></iframe>

<style>
.tabs {font-size: 0;margin: 10px;white-space: nowrap;text-align: center;overflow: auto;}
.tabs .tab {display: inline-block;line-height: 30px;padding: 0 8px;margin: 3px 2px;background: #cfeef0;border-radius: 10px;font-size: 16px;color: #0dabb6;text-decoration: none;border: 1px #0dabb6 solid;cursor:pointer}
.tabs .tab.active {color: white;background: #0dabb6;}
#prices{position: absolute;width: 100%;top: 115px;bottom: 0;left: 0;height: calc(100vh - 115px);}

@media(max-width:992px){
#prices{top: 200px;bottom: 0;left: 0;height: calc(100vh - 200px);}
}
</style>
<script>
function changeTab(siteid){
	var tab = "#tab"+siteid;
	$(".tab").removeClass('active');
	$(tab).addClass('active');
	$('#prices').attr('src','prices.php?asite='+siteid);
}
</script>