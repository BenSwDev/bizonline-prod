<?php define('ACTIVE',"Kew0Rd!Kew0Rd!Kew0Rd!");
$_SESSION['user_id'] = 3;
$_SESSION['permission'] = 100;

include_once "../../bin/functions_and_constants_only!!!.php";

$missingSql = "SELECT * FROM `settlements` WHERE lon_x=0 OR lat_y=0";
$missing = udb::full_list($missingSql);

foreach($missing as $miss) {
	$address = $miss['TITLE'];
	$address = str_replace(' ' , '+' , $address);
	$url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$address."&key=AIzaSyBCBut3eC_1LxaeXMmyILFv6nGJxTa_hZ4";
	$resjson = file_get_contents($url,true);	
	$res = json_decode($resjson,true);
	if($res['status'] == 'ok') {
		$lat = $res['results'][0]['geometry']['location']['lat'];
		$lng = $res['results'][0]['geometry']['location']['lng'];
		echo "latlng=" . $lat . " , " . $lng;	
		// $query = [];
		// $query['lat_y'] = $lat;
		// $query['lon_x'] = $lng;
		// udb::update("settlements", $query  , " settlementID=".$miss['settlementID']);
	}	
	exit; 
	
}

