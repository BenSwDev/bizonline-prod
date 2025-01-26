<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 23/08/2021
 * Time: 11:07
 */

require_once "auth.php";

/**
 * @var $_CURRENT_USER
 */

$result = new JsonResult;

$therapistID = intval($_POST['therapistID']);

$result['success'] = false;

try {
    if ($therapistID) {
        $siteID = udb::single_value("SELECT `siteID` FROM `therapists` WHERE `therapistID` = " . $therapistID);
        if (!$siteID || !$_CURRENT_USER->has($siteID))
            throw new Exception("Access denied");

        $check = udb::single_value("SELECT COUNT(*) FROM `orders` AS `a` INNER JOIN `orders` AS `b` ON (a.parentOrder = b.orderID) WHERE a.siteID = " . $siteID . " AND a.therapistID = " . $therapistID . "  AND b.status = 1");
        if ($check)
            throw new Exception("Therapist has " . $check . " active bookings");

        udb::update("orders", ['therapistID' => 0], "`siteID` = " . $siteID . " AND `therapistID` = " . $therapistID);
        udb::update("therapists", ['deleted' => 1, 'active' => 0], "`therapistID` = " . $therapistID);

        $result['success'] = true;
    }
}
catch (Exception $e){
    $result['error']   = $e->getMessage();
    $result['success'] = false;
}
