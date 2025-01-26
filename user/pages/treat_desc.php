<?php 
require_once "auth.php";

$orderID = $GET['orderID']?: 124827;

$order = udb::single_row("SELECT * FROM `orders` WHERE `orderID` = " . $orderID . " AND `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");

$treatments  = udb::full_list("SELECT *,treatments.treatmentName FROM `orders` LEFT JOIN `treatments` USING (`treatmentID`) WHERE `parentOrder` = " . $orderID . " AND  orderID<> ".$orderID." AND `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");

foreach($treatments as $treatment){
	$text.= $treatment['treatmentName']." ".$treatment['treatmentLen']." דקות <br>";
}

$que = "SELECT extraID , extraName
		FROM `sites_treatment_extras` AS `s` 
		INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) 
		WHERE s.siteID = " . $order['siteID'] ." AND included = 0
		ORDER BY e.showOrder";


$extras = udb::key_value($que, ['extraID']);

$orderExtras = $order['extras'] ? json_decode($order['extras'], true) : [];

foreach($orderExtras['extras'] as $extra){
	if($extras[$extra['extraID']])
		$text.= $extra['count']." x ".$extras[$extra['extraID']]."<br>";
	
}

echo $text;


