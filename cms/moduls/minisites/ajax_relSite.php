<?php
include_once "../../bin/system.php";


$sitesP = udb::key_row("SELECT siteID,portalsID FROM sites where portalsID > 0 ","portalsID");
$sitesZ = udb::key_row("SELECT siteID,zimmersID FROM sites where zimmersID > 0 ","zimmersID");

function getZimmersStat($uurl){
    $url = $uurl;
    $curlSend = curl_init();

    curl_setopt($curlSend, CURLOPT_URL, $url);
    curl_setopt($curlSend, CURLOPT_RETURNTRANSFER, 1);

    $curlResult = curl_exec($curlSend);
    $curlStatus = curl_getinfo($curlSend, CURLINFO_HTTP_CODE);
    curl_close($curlSend);
    if ($curlStatus === 200)
        return $curlResult;
    else
        return null;

}

function getStats($type , $siteID=0){
    $url = "https://www.villadaka90.co.il/api/?key=ssd205033&type=" . $type . "&siteid=".$siteID;
    $curlSend = curl_init();

    curl_setopt($curlSend, CURLOPT_URL, $url);
    curl_setopt($curlSend, CURLOPT_RETURNTRANSFER, 1);

    $curlResult = curl_exec($curlSend);
    $curlStatus = curl_getinfo($curlSend, CURLINFO_HTTP_CODE);
    curl_close($curlSend);
    //file_put_contents("log_api_cals".date("Ymd").".txt", $url . PHP_EOL . $curlResult . PHP_EOL, FILE_APPEND);
    if ($curlStatus === 200)
        return json_decode($curlResult,true);
    else
        return [];
}

if(intval($_POST['siteID'])!=''){
    $siteID=intval($_POST['siteID']);
    $portalsID=intval($_POST['portalsID']);
    $zimmersID=intval($_POST['zimmersID']);
    udb::update("sites",['zimmersID' => $zimmersID , 'portalsID'=>$portalsID], "siteID=".$siteID);

    if($zimmersID) {
        $data = getZimmersStat("https://www.zimmersdaka90.co.il/getStats.php?type=121&siteid=".$zimmersID);
        $data = $data ? json_decode($data,true) : null;

        if($data){

            $villasWApp = $data['data'];
            foreach ($villasWApp['data'] as $item) {
                if(!intval($sitesZ[$item['siteID']]['siteID'])) continue;
                $cp = [];
                $cp['name'] = $item['name'];
                $cp['phone'] = $item['phone'];
                $cp['date'] = $item['date'];
                $cp['siteID'] = $sitesZ[$item['siteID']]['siteID'];
                $cp['domainID'] = 9;
                $cp['created'] = $item['created'];
                $cp['sourceID'] = $item['id'];
                //print_r($cp);
                $count++;
                udb::insert("contact_whatsapp",$cp);
            }
        }


        $data = getZimmersStat("https://www.zimmersdaka90.co.il/getStats.php?type=211&siteid=".$zimmersID);
        $data = $data ? json_decode($data,true) : null;

        if($data){
            $villasContact = $data['data'];
            foreach ($villasContact['data'] as $item) {
                if(!intval($sitesZ[$item['siteID']]['siteID'])) continue;

                $cp = [];
                $cp['siteID'] = $sitesZ[$item['siteID']]['siteID'];
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


    }

    if($portalsID) {
//get whats app contact from vilas portals
        $villasWApp = getStats(1 , $portalsID);
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
                udb::insert("contact_whatsapp",$cp);
            }
        }


//get contact form lead from vilas portals
        $villasContact = getStats(2 , $portalsID);
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
    }


    echo 'ok';
}
else {
    echo 'not found';
}