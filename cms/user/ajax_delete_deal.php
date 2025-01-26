<?php
include_once "../bin/system.php";
require_once "../classes/class.PriceCache.php";


$siteID = intval($_POST['siteID']);
$specID = intval($_POST['dealID']);


$que = "DELETE sitesSpecials.*, sitesSpecialsExtras.*, sitesSpecialsRooms.*
	        FROM `sitesSpecials` LEFT JOIN `sitesSpecialsExtras` USING(`specID`)
	            LEFT JOIN `sitesSpecialsRooms` ON (sitesSpecialsRooms.specID = sitesSpecials.specID)
	        WHERE sitesSpecials.specID = " . $specID . " AND sitesSpecials.siteID = " . $siteID;
udb::query($que);

PriceCache::updateTomorrow($siteID);
PriceCache::updateWeekend($siteID);
PriceCache::updateVideo($siteID);