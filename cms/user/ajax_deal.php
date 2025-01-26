<?
include_once "../bin/system.php";
require_once "../classes/class.PriceCache.php";



$cp=Array();
$cp['siteID'] = intval($_POST['siteID']);
$cp['active'] = intval($_POST['active'])?intval($_POST['active']):0;
$cp['discount'] = intval($_POST['discount']);
$cp['daysInWeek'] = intval($_POST['daysInWeek']);
$cp['exclusive'] = intval($_POST['exclusive'])?intval($_POST['exclusive']):0;
if(intval($_POST['sysID'])){
    $que="SELECT * FROM sitesSpecialsSys WHERE specID=".intval($_POST['sysID'])." ";
    $sysDeal = udb::single_row($que);
    $cp['dateFrom'] = $sysDeal['dateFrom'];
    $cp['dateTo'] = $sysDeal['dateTo'];
    $cp['dealType'] = $sysDeal['dealType'];
    $cp['periodInYear'] = $sysDeal['periodInYear'];
    $cp['dealTo'] = $sysDeal['dealTo'];
    $cp['limitations'] = $sysDeal['limitations'];
    $cp['baseSys'] = $sysDeal['specID'];
} else {

    if($_POST['dateFrom']){
        $date=explode("/", $_POST['dateFrom']);
        $date=$date[2]."-".$date[1]."-".$date[0];
        $cp["dateFrom"] = $date;
    }
    if($_POST['dateTo']){
        $date=explode("/", $_POST['dateTo']);
        $date=$date[2]."-".$date[1]."-".$date[0];
        $cp["dateTo"] = $date;
    }
    $cp['dealType'] = intval($_POST['dealType'])?intval($_POST['dealType']):2;
    $cp['periodInYear'] = intval($_POST['periodInYear']);
    $cp['dealTo'] = intval($_POST['dealTo']);
    $cp['limitations'] = intval($_POST['limitations']);
    $cp['baseSys'] = 0;
}

if(intval($_POST['dealID'])){
    udb::update("sitesSpecials", $cp, "specID=".intval($_POST['dealID'])." AND siteID=".intval($_POST['siteID'])." ");
} else {
    $_POST['dealID'] = udb::insert("sitesSpecials", $cp);
}
if(intval($_POST['dealID']) && intval($_POST['siteID'])){
    udb::query("DELETE FROM sitesSpecialsRooms WHERE specID=".$_POST['dealID']." AND siteID=".intval($_POST['siteID'])." ");
    if($_POST['roomID']){
        $room=Array();
        $room['siteID']=intval($_POST['siteID']);
        $room['roomID']=intval($_POST['roomID']);
        $room['specID']=intval($_POST['dealID']);
        udb::insert("sitesSpecialsRooms", $room);
    } else {
        $room=Array();
        $room['siteID']=intval($_POST['siteID']);
        $room['roomID']=0;
        $room['specID']=intval($_POST['dealID']);
        udb::insert("sitesSpecialsRooms", $room);
    }

    udb::query("DELETE FROM sitesSpecialsExtras WHERE specID=".$_POST['dealID']." AND siteID=".intval($_POST['siteID'])." ");
    if($_POST['extras']){
        $extras=Array();
        $extras['siteID']=intval($_POST['siteID']);
        $extras['extraID']=intval($_POST['extras']);
        $extras['specID']=intval($_POST['dealID']);
        udb::insert("sitesSpecialsExtras", $extras);
    }

    if(intval($_POST['exclusive'])==1){
        $cp2=Array();
        $cp2['exclusive']=0;
        udb::update("sitesSpecials", $cp2, "siteID=".intval($_POST['siteID'])."");

        $cp3=Array();
        $cp3['exclusive']=1;
        udb::update("sitesSpecials", $cp3, "specID=".intval($_POST['dealID'])."");
    }
}

PriceCache::updateTomorrow();
PriceCache::updateWeekend();

echo intval($_POST['dealID']);