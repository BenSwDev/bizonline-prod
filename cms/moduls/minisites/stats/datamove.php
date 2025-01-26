<?php
include_once "../../../bin/system.php";


$domainsList = udb::key_row("SELECT * FROM domains where portalsID > 0 ","zimmersID");
$sites = udb::key_row("SELECT siteID,zimmersID FROM sites where zimmersID > 0 ","zimmersID");
$sitesPhones = udb::key_row("select maskyooPhone,siteID,domainID from sites_domains where maskyooPhone!='' and maskyooPhone is not null and domainID=9","maskyooPhone");

$sql = "SELECT * FROM `maskyoo_villa4u_calls` order by id ASC LIMIT 40000";
$vilasData = udb::full_list($sql);
$count = 0;
foreach ($vilasData as $call) {
    $maskyoo = str_replace("972","0",$call['maskyooNumber']);
    if($maskyoo[0] != 0) $maskyoo = '0' . $maskyoo;
    if(strpos($maskyoo,"-") === false) {
        $maskyoo = substr_replace($maskyoo, "-", 3, 0);
    }
    if(isset($sitesPhones[$maskyoo])) {
        $cp = [];
        $count++;
        $cp['siteID'] = $sitesPhones[$maskyoo]['siteID'];
        $cp['domainID'] = 7;
        $cp['start_call'] = $call['start_call'];
        $cp['end_call'] = $call['end_call'];
        $cp['cdr_ani'] = $call['cdr_ani'];
        $cp['cdr_ddi'] = $call['maskyooNumber'];
        $cp['user_phone'] = $call['user_phone'];
        $cp['user_name'] = $call['user_name'];
        $cp['call_status'] = $call['call_status'];
        $cp['id'] = $call['id'];
        udb::insert("maskyooCalls",$cp,true);
        $id = $call['id'];
        //print_r($cp);
        //if($count > 10) exit;
    }
}
$id++;

udb::query("delete from maskyoo_villa4u_calls where id<".$id);

echo $count;