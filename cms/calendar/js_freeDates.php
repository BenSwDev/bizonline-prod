<?php
include_once "../bin/system.php";

$prm    = explode('-', $_GET['m']);
$siteID = intval($_GET['siteID']);

$que="SELECT sitesRooms.roomID, sitesRooms.roomCount FROM `sitesRooms` WHERE sitesRooms.siteID = ".$siteID." ORDER BY showOrder";
//$rooms= udb::key_row($que, "roomID");
$units = udb::key_value($que, 'roomID', 'roomCount');


//$units  = array(15 => 2, 16 => 3, 17 => 5, 18 => 4);   // roomID => units  <-- must be sortred same way as in outsite

$result = array('month' => $prm, 'free' => array());


/*$units=Array();
foreach($rooms as $room){
	$units[$room['roomID']]=$room['roomCount'];
}*/

$que = "SELECT unitsDates.date, unitsDates.roomID, unitsDates.free FROM unitsDates INNER JOIN sitesRooms USING (`roomID`) WHERE sitesRooms.siteID=".$siteID." ";
/*$unitsDates=udb::full_list($que);
$freeDates=Array();

foreach($unitsDates as $dts){
	$freeDates[$dts['roomID']][date("d", strtotime($dts['date']))]=$dts['free'];
}*/
$freeDates = udb::key_value($que, array('date', 'roomID'), 'free');

// $freeDates is selected from DB by $siteID and $prm (specific month received from page)

$max = date('t', strtotime($_GET['m'] . '-01'));   // num of days in month
$pre = intval($prm[0]) . '-' . str_pad(intval($prm[1]), 2, '0', STR_PAD_LEFT) . '-';

foreach($units as $rid => $rc){
	//---- this part is for testing only -----
	/*for($i=1; $i<31; $i++)
		$freeDates[$rid][$i] = mt_rand(0,6) % ($u + 1);*/
	//---- testing only part end -----	
	
	$temp = array(0 => 0);
	for($i = 1; $i <= $max; ++$i){
		$d = $pre . str_pad($i, 2, '0', STR_PAD_LEFT);
		$temp[] = is_numeric($freeDates[$d][$rid]) ? $freeDates[$d][$rid] : $rc;
	}
	
	$result['free'][] = $temp;
}



header('Content-Type: application/json');

echo json_encode($result, JSON_NUMERIC_CHECK);
