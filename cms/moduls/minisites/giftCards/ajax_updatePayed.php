<?php
include_once "../../../bin/system.php";

include_once "../../../_globalFunction.php";

$results = [];
$results['success'] = true;

try{

    $siteID = intval($_POST['siteID']);
    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    $paid = udb::single_value("select paymentID from commissionPayments where pMonth=".$month . " and pYear=".$year . " and siteID=".$siteID);
//TODO validates
    if($paid) {
        udb::query("delete from commissionPayments where paymentID=".$paid);
        $results['message'] = "נמחק";
    }
    else {
        $cp = [];
        $cp['siteID'] = $siteID;
        $cp['pYear'] = $year;
        $cp['pMonth'] = $month;
        udb::insert('commissionPayments',$cp);
        $results['message'] = "נוצר";
    }



} catch (Exception $e) {

    $results['error'] = true;

}

echo json_encode($results, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);