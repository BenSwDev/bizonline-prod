<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 01/07/2021
 * Time: 16:31
 */

include_once "../../../bin/system.php";

include_once "../../../_globalFunction.php";

include_once "../class.siteduplicator.php";


$results = [];

$siteID = intval($_POST['siteID']);
$domainID = intval($_POST['domainID']);
$active = intval($_POST['active']);
$toDomain = $domainID;
$type = intval($_POST['type']);

$fromDomain = 1;
$fromLang = 1;
$toLang = 1;
$copyGalleries = 0;
$copyMainGalleries = 0;
$copyLangs = 0;
$copyDomains = 0;

if(!$siteID || !$domainID) {
    $results['error'] = 'error';
}
else {
    switch ($type) {
        case 0:
            $exisats = udb::single_value("select siteID from sites_domains where siteID=".$siteID." and domainID=".$domainID);
            if(!$exisats) {
                $cloner = new siteduplicator($siteID,$toDomain,$fromLang,true , $fromDomain);
                $cloner->cloneSitesDomains();
            }
            udb::query("update sites_domains set active=".$active ." where siteID=".$siteID." and domainID=".$domainID);
            break;
        case 1:
            $que = [];
            $que['siteID'] = $siteID;
            $que['domainID'] = $domainID;
            $que['active'] = $active;
            udb::insert("promotedDomains",$que,true);
            break;
        case 2:
            $que = [];
            $que['siteID'] = $siteID;
            $que['domainID'] = $domainID;
            $que['active'] = $active;
            udb::insert("promotedHomeDomains",$que,true);
            break;
        case 3:
            $que = [];
            $que['siteID'] = $siteID;
            $que['domainID'] = $domainID;
            $que['active'] = $active;
            udb::insert("promotedsearchDomains",$que,true);
            break;
    }
    $results['success'] = 'ok';
}

echo json_encode($results, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
