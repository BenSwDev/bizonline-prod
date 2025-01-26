<?php
include_once "../../bin/system.php";
include_once "../../_globalFunction.php";
include_once "class.siteduplicator.php";

$siteID = intval($_POST['siteID']);
$fromDomain = intval($_POST['fromDomain']);
$toDomains = $_POST['toDomains'];
$overridegalleries = intval($_POST['overridegalleries']) ? true : false;
$CloneGalleries = intval($_POST['clonegalleries']) ? true : false;
$fromLang = 1;
$toLang = 1;
$results = [];
try{
    foreach ($toDomains as $toDomain) {
        $cloner = new siteduplicator($siteID,$toDomain,$fromLang,$overridegalleries , $fromDomain , $CloneGalleries);
        $cloner->init();
        $results['domains'][] =  $toDomain;
        unset($cloner);
    }
} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}



echo json_encode($results, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);




