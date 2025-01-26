<?
include_once "bin/functions_and_constants_only!!!.php";

$sites = udb::single_column("SELECT `siteID` FROM `sites` WHERE `guid` IS NULL");
//print_r($sites);

foreach($sites as $site){
	$siteData = ["guid" => GUID()];
	udb::update('sites', $siteData, '`siteID` = ' . $site);

}


function GUID(){
	return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

?>