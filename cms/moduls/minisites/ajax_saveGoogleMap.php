<?php define('ACTIVE',"Kew0Rd!Kew0Rd!Kew0Rd!");
$_SESSION['user_id'] = 3;
$_SESSION['permission'] = 100;

include_once "../../bin/functions_and_constants_only!!!.php";

$siteID = intval($_GET['siteID']);
$site = udb::single_row("select sites.*,areas.TITLE as areasName,settlements.TITLE as cityName,main_areas.TITLE as mainAreaName , settlements.lat_y,settlements.lon_x  from sites 
left join sites_langs ON (sites.siteID=sites_langs.siteID AND sites_langs.domainID = 1 AND sites_langs.langID = 1)
left join settlements on (sites.`settlementID` = settlements.settlementID) 
left join areas on (areas.areaID = settlements.areaID) 
left join main_areas on (areas.main_areaID = main_areas.main_areaID) 
where sites.siteID=".$siteID);

$address = $site['mainAreaName']. '+' .$site['cityName'];
$address = str_replace(' ' , '+' , $address);
$gpss = null;
if($site['gpsLat'] && $site['gpsLong']) {
    $gpss = $site['gpsLat'] . "," . $site['gpsLong'];
}
else {
    if($site['lat_y'] && $site['lon_x']) {
        $gpss	 = $site['lat_y'] . "," . $site['lon_x'];
    }
}

if($gpss) {
    $address = $gpss;
}

$url = "https://maps.googleapis.com/maps/api/staticmap?center=".$address."&size=318x320&maptype=roadmap&zoom=13&size=318x320&language=he&key=AIzaSyBCBut3eC_1LxaeXMmyILFv6nGJxTa_hZ4";

function cUrlGet($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    $res = curl_exec($ch);
    if (curl_errno($ch))
        throw new Exception('Connect error: ' . curl_error($url));
    curl_close($ch);
    return $res;
}

try {
    $newFileName = "googleMap".$siteID.".png";
	$gmap = "../../../googlemaps/".$newFileName;

	file_put_contents($gmap, cUrlGet($url));

	$que = [];
	$que['googlemap'] = "googlemaps/" .$newFileName;
	udb::update("sites",$que," siteID=".$siteID);

	echo "https://www.bizonline.co.il/googlemaps/".$newFileName;
} catch (Exception $e){
   print_r($e);
}
