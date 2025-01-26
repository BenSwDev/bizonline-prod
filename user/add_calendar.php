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

$que = "SELECT `orders`.*, `rooms_units`.`unitName`, `sites`.siteName
FROM `orders`
INNER JOIN `sites` USING (siteID)
INNER JOIN orderUnits USING (orderID)
INNER JOIN `rooms_units` ON (`rooms_units`.unitID = orderUnits.unitID)
WHERE orderID=".intval($_GET['orderID']);
$event = udb::single_row($que);


/*

$useNames = $events['celebrant'] ? $events['celebrant'] : $events['owners'];

$title = $events['typeTitle'].($events['of_word']?' של ':'').($useNames?str_replace('39','',preg_replace('/[^\p{L}\p{N}\s]/u', '', $useNames)):str_replace('39','',preg_replace('/[^\p{L}\p{N}\s]/u', '', $eventss['owners'])));

$description = $events['invitation_text'].' '.($useNames?str_replace('39','',preg_replace('/[^\p{L}\p{N}\s]/u', '', $useNames)):str_replace('39','',preg_replace('/[^\p{L}\p{N}\s]/u', '', $eventss['owners'])));

$url = urldecode('https://www.ievent.co.il/event.php?guid='.$events['guid_clients']);



if($useUserDates == true) {

$events['event_date'] = $user_view['meetingDate'];

}


$event_parameters = array(

    'uid' =>  $events['id'],

    'summary' => $title,

    'description' => '',

    'location' => $url,

    'hall_title' => $events['title'],

    'event_date' => $events['event_date']

);



$date = new DateTime($event_parameters['event_date']);

*/
$url = WEBSITE . "user/index.php?page=calendar&date=".implode('/',array_reverse(explode('-',substr($event['showTimeFrom'],0,10))));

$ics = new ICS(array(

  //  'location' => $event['siteName'],

 
    'description' => "הזמנה מספר ".$event['orderIDBySite']." ל".$event['unitName'],

    'dtstart' => date('Y-m-d H:i', strtotime($event['showTimeFrom'])),

    'dtend' => date('Y-m-d H:i', strtotime($event['showTimeUntil'])),

    'summary' => htmlspecialchars($event['comments_customer']),

    'url' => $url

));





echo $ics->to_string();