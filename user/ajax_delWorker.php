<?php


require_once "auth.php";



$result = new JsonResult;

$workerID = intval($_POST['workerID']);

$result['success'] = false;

try {
    if ($workerID) {
        $siteID = udb::single_value("SELECT `siteID` FROM `workers` WHERE `workerID` = " . $workerID);
        if (!$siteID || !$_CURRENT_USER->has($siteID))
            throw new Exception("Access denied");

        udb::update("workers", ['deleted' => 1, 'active' => 0], "`workerID` = " . $workerID);

        $result['success'] = true;
    }
}
catch (Exception $e){
    $result['error']   = $e->getMessage();
    $result['success'] = false;
}
