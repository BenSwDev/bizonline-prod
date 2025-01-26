<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 18/05/2021
 * Time: 16:48
 */
include_once "../../../bin/system.php";
include_once "../../../_globalFunction.php";

//include_once "public_html/functions.php";

// types:
// 1 whats app clicks villas
// 10 zimmers sites list
// 11 vilas portals sites list
// 2 contact leads villas
// 12 whats app clicks Zimmers
// 21 contact leads Zimmers

function getStats($type , $lastID=0){
    $url = "https://www.vila.co.il/api/?key=ssd205033&type=" . $type . "&from=".$lastID;
    $curlSend = curl_init();

    curl_setopt($curlSend, CURLOPT_URL, $url);
    curl_setopt($curlSend, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlSend, CURLOPT_FOLLOWLOCATION, true);

    $curlResult = curl_exec($curlSend);
    $curlStatus = curl_getinfo($curlSend, CURLINFO_HTTP_CODE);
    curl_close($curlSend);
    if ($curlStatus === 200)
        return json_decode($curlResult,true);
    else
        return [];
}

function fixPhone($currPhone){
    $retValue = trim($currPhone);
    if($currPhone[0] != '0') {
        $retValue = '0' .$currPhone;
    }
    if(strpos($retValue,"-") === false) {
        $retValue = substr_replace($retValue, "-", 3, 0);
    }
    return $retValue;
}

$updateSites = isset($_GET['updateSites']);
$updateSites = 1;

$domainsList = udb::key_row("SELECT * FROM domains where portalsID > 0 ","portalsID");
$sites = udb::key_row("SELECT siteID,portalsID FROM sites where portalsID > 0 ","portalsID");
$sitesZ = udb::key_row("SELECT siteID,portalsID FROM sites where zimmersID > 0 ","portalsID");

if($updateSites){
    //get zimmers portals siteID
    $villasSites = getStats(10 , 0);
    $found = 0;
    if($villasSites) {
        foreach ($villasSites['sites'] as $vila) {
            $siteID = udb::single_value("select siteID from sites where siteName='".addslashes(typemap($vila['title'],'text'))."'");
            if($siteID) { // if found site with exact same name
                $cp = [];
                //$cp['portalsID'] = $vila['siteid'];
                $cp['zimmersID'] = $vila['siteid'];
                $found++;
                udb::update("sites",$cp," siteID=" . $siteID);
                if($exist = udb::single_row("select * from sites_domains where siteID=".$siteID ." and domainID=9")) {
                    udb::query("update sites_domains set maskyooPhone='".fixPhone($vila['phone']). "' where domainID=9 and siteID=".$siteID);
                }
                else {
                    $cp = [];
                    $cp['siteID'] = $siteID;
                    $cp['langID'] = 1;
                    $cp['domainID'] = 9;
                    $cp['siteName'] = typemap($vila['title'],'text');
                    udb::insert("sites_langs",$cp);
                    unset($cp['langID']);
                    unset($cp['siteName']);
                    $cp['maskyooPhone'] = fixPhone($vila['phone']);
                    udb::insert("sites_domains",$cp);
                }
            }
            else { // if not check if site id update manualy
                $item = udb::single_row("select portalsID,zimmersID,siteID from sites where zimmersID=".$vila['siteid']);
                if($item) {
                    if($exist = udb::single_row("select * from sites_domains where siteID=".$item['siteID'] ." and domainID=9")) {
                        udb::query("update sites_domains set maskyooPhone='".fixPhone($vila['phone']). "' where domainID=9 and siteID=".$item['siteID']);
                    }
                    else {
                        $cp = [];
                        $cp['siteID'] = $item['siteID'];
                        $cp['langID'] = 1;
                        $cp['domainID'] = 9;
                        $cp['siteName'] = typemap($vila['title'],'text');
                        udb::insert("sites_langs",$cp);
                        unset($cp['langID']);
                        unset($cp['siteName']);
                        $cp['maskyooPhone'] = fixPhone($vila['phone']);
                        udb::insert("sites_domains",$cp);
                    }
                }


            }
        }
    }
    echo 'doneZimmers';
    exit;
    //get vilas portals siteID
    $villasSites = getStats(11 , 0);
    $found = 0;
    if($villasSites) {
        foreach ($villasSites['sites'] as $vila) {
            $siteID = udb::single_value("select siteID from sites where siteName='".addslashes(typemap($vila['title'],'text'))."'");
            if($siteID) {
                $cp = [];
                $cp['portalsID'] = $vila['siteid'];
                $found++;
                udb::update("sites",$cp," siteID=" . $siteID);
                if($exist = udb::single_row("select * from sites_domains where siteID=".$siteID ." and domainID=".$domainsList[1]['domainID'])) {
                    udb::query("update sites_domains set maskyooPhone='".fixPhone($vila['phone']). "' where domainID=".$domainsList[1]['domainID'] . " and siteID=".$siteID);
                }
                else {
                    $cp = [];
                    $cp['siteID'] = $siteID;
                    $cp['langID'] = 1;
                    $cp['domainID'] = $domainsList[1]['domainID'];
                    $cp['siteName'] = typemap($vila['title'],'text');
                    udb::insert("sites_langs",$cp);
                    unset($cp['langID']);
                    unset($cp['siteName']);
                    $cp['maskyooPhone'] = fixPhone($vila['phone']);
                    udb::insert("sites_domains",$cp);
                }

            }
            else {
                $item = udb::single_row("select portalsID,zimmersID,siteID from sites where portalsID=".$vila['siteid']);
                if($item) {
                    if($exist = udb::single_row("select * from sites_domains where siteID=".$item['siteID'] ." and domainID=".$domainsList[1]['domainID'])) {
                        udb::query("update sites_domains set maskyooPhone='".fixPhone($vila['phone']). "' where domainID=".$domainsList[1]['domainID'] ." and siteID=".$item['siteID']);
                    }
                    else {
                        $cp = [];
                        $cp['siteID'] = $item['siteID'];
                        $cp['langID'] = 1;
                        $cp['domainID'] = $domainsList[1]['domainID'];
                        $cp['siteName'] = typemap($vila['title'],'text');
                        udb::insert("sites_langs",$cp);
                        unset($cp['langID']);
                        unset($cp['siteName']);
                        $cp['maskyooPhone'] = fixPhone($vila['phone']);
                        udb::insert("sites_domains",$cp);
                    }
                }

            }
        }
    }
}




//get whats app contact from vilas portals

$lastID = udb::single_value("select MAX(sourceID) from contact_whatsapp where domainID in (7,8)");
$villasWApp = getStats(1 , $lastID);
$count = 0;
if($villasWApp){
    foreach ($villasWApp['data'] as $item) {
        if(!intval($sites[$item['siteID']]['siteID'])) continue;
        $cp = [];
        $cp['name'] = $item['name'];
        $cp['phone'] = $item['phone'];
        $cp['date'] = $item['date'];
        $cp['siteID'] = $sites[$item['siteID']]['siteID'];
        $cp['domainID'] = $domainsList[$item['PortalID']]['domainID'];
        $cp['created'] = $item['created'];
        $cp['sourceID'] = $item['id'];
        //print_r($cp);
        $count++;
        udb::insert("contact_whatsapp",$cp);
    }
}


//get contact form lead from vilas portals
$lastID = udb::single_value("select MAX(sourceID) from contactForm where domainID in (7,8)");
$villasContact = getStats(2 , $lastID);

$count = 0;
if($villasContact){
    foreach ($villasContact['data'] as $item) {
        if(!intval($sites[$item['siteid']]['siteID'])) continue;
        $cp = [];
        $cp['siteID'] = $sites[$item['siteid']]['siteID'];
        $cp['domainID'] = $domainsList[$item['PortalID']]['domainID'];
        $cp['sourceID'] = $item['id'];
        $cp['note'] = $item['message'];
        $cp['mail'] = $item['email'];
        $cp['phone'] = $item['phone'];
        $cp['fullName'] = $item['name'];
        $cp['created'] = date("Y-m-d H:i",$item['date']);
        udb::insert("contactForm",$cp);
    }
}


//get whatsapp  from zimmers

$lastID = udb::single_value("select MAX(sourceID) from contact_whatsapp where domainID=9");
$villasWApp = getStats(12 , $lastID);
$count = 0;

if($villasWApp){

    $villasWApp = $villasWApp['data'];
    foreach ($villasWApp['data'] as $item) {
        if(!intval($sitesZ[$item['siteID']]['siteID'])) continue;
        $cp = [];
        $cp['name'] = $item['name'];
        $cp['phone'] = $item['phone'];
        $cp['date'] = $item['date'];
        $cp['siteID'] = $sites[$item['siteID']]['siteID'];
        $cp['domainID'] = 9;
        $cp['created'] = $item['created'];
        $cp['sourceID'] = $item['id'];
        //print_r($cp);
        $count++;
        udb::insert("contact_whatsapp",$cp);
    }
}


//get contact form lead from zimmers
$lastID = udb::single_value("select MAX(sourceID) from contactForm where domainID=9");
$villasContact = getStats(21 , $lastID);

$count = 0;
if($villasContact){
    $villasContact = $villasContact['data'];
    foreach ($villasContact['data'] as $item) {
        if(!intval($sites[$item['siteid']]['siteID'])) continue;

        $cp = [];
        $cp['siteID'] = $sitesZ[$item['siteid']]['siteID'];
        $cp['domainID'] = 9;
        $cp['sourceID'] = $item['id'];
        $cp['note'] = $item['message'];
        $cp['mail'] = $item['email'];
        $cp['phone'] = $item['phone'];
        $cp['fullName'] = $item['name'];
        $cp['created'] = date("Y-m-d H:i",$item['date']);
        udb::insert("contactForm",$cp);
    }
}