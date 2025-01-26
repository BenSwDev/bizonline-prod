<?php

include_once "../functions.php";

include 'ICS.php';

header('Content-Type: text/html; charset=utf-8');



// 1. Set the correct headers for this file

header('Content-type: text/calendar; charset=utf-8');

header('Content-Disposition: attachment; filename=Event_' . intval($_GET['orderID']).'_'.time());


/*
$user_guid = $_GET['user_guid'];

$useUserDates = false;

if($user_guid) {

$user_view=udb::single_row('SELECT * FROM events_confirms WHERE user_guid="'.$user_guid.'"');

$useUserDates = true;

}
*/

if(!$_GET['orderID'])
exit;
$que = "SELECT * FROM orders
        INNER JOIN sites_langs USING(`siteID`)
        WHERE  sites_langs.langID=1 AND sites_langs.domainID=1 AND orderID=".intval($_GET['orderID']);
        $orders = udb::single_row($que);
		
		if($orders['parentOrder'] > 0 && $orders['parentOrder'] != $orders['orderID'] ){
			$childID = $orders['parentOrder'];
			$que = "SELECT orders.*, `settlements`.`TITLE` AS clientCity FROM orders 
			INNER JOIN sites_langs USING(`siteID`)
			LEFT JOIN `settlements` ON(`settlements`.`settlementID` = orders.`settlementID`)
			WHERE status=1 AND sites_langs.langID=1 AND sites_langs.domainID=1 AND orderID = ".$childID;
			$orders = udb::single_row($que);
		}

$que = "SELECT orders.*, therapists.siteName AS `masterName`, orderUnits.extraRoomName AS `roomName`, treatments.treatmentName
		FROM `orders` 
			LEFT JOIN `orderUnits` USING(`orderID`)
			LEFT JOIN `therapists` USING(`therapistID`)
			LEFT JOIN `treatments` USING(`treatmentID`)
		WHERE orders.parentOrder = " . $orders['orderID'] . " AND orders.orderID <> " . $orders['orderID'];
		$treatments = udb::single_list($que);
		foreach($treatments as &$treat){
			if ($treat['timeFrom'][0] != '0'){      // not 0000-00-00 00:00:00
				$dates[] = $treat['timeFrom'];
				$dates[] = $treat['timeUntil'];
				list($treat['startDate'], $treat['startTime']) = explode(' ', substr($treat['timeFrom'], 0, 16));
				list($treat['endDate'], $treat['endTime']) = explode(' ', substr($treat['timeUntil'], 0, 16));
			}
		}

$que = "SELECT sites.*, settlements.Title AS sTitle , sites_langs.address , sites_domains.phoneOnOrder,sites_domains.addressOnOrder,sites_domains.topCommentsOnOrder,sites_domains.cancelTermsOnOrder,sites_domains.bottomCommentsOnOrder
		FROM sites LEFT JOIN settlements USING (settlementID) LEFT JOIN sites_langs ON(sites.siteID = sites_langs.siteID) LEFT JOIN sites_domains ON(sites.siteID = sites_domains.siteID and sites_domains.domainID=1) 
		WHERE sites_langs.domainID = 1 and langID = 1 AND sites.siteID=".$orders['siteID'];
		$site = udb::single_row($que);

$event = $orders;


$url = WEBSITE . "signature2.php?guid=".$event['guid'];

$ics = new ICS(array(

 //   'location' => $event['siteName'] .($site['addressOnOrder']? ." - ".$site['addressOnOrder'] : ""),
 
    'description' => "הזמנה מספר ".$event['orderIDBySite']." ל".$event['siteName'],

    'dtstart' => date('Y-m-d H:i', strtotime($event['showTimeFrom'])),

    'dtend' => date('Y-m-d H:i', strtotime($event['showTimeUntil'])),

    'summary' => $event['siteName'] .($site['addressOnOrder']? ." - ".$site['addressOnOrder'] : "")." ".()." ,

    'url' => $url

));





echo $ics->to_string();