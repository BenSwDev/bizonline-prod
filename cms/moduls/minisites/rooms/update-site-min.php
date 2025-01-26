<?php
include_once "../../../bin/system.php";

$result = new JsonResult;

$siteID = intval($_POST['id']);
$minu = intval($_POST['minu']);
try {
    udb::query("update sites set minOrderUnits=".$minu . " where siteID=".$siteID);
    $result['success'] = true;
}
catch (Exception $e){
    $result['success'] = false;
    $result['error']   = $e->getMessage();
}
