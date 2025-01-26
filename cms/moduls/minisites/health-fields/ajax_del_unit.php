<?php
include_once "../../../bin/system.php";

$result = new JsonResult;

$unitID = intval($_POST['id']);

try {
    if (udb::single_value("SELECT COUNT(*) FROM `orderUnits` WHERE `unitID` = " . $unitID))
        throw new Exception("Cannot delete unit that has bookings");

    $roomID = udb::single_value("SELECT `roomID` FROM `rooms_units` WHERE `unitID` = " . $unitID);

    udb::query("DELETE FROM `tfusa` WHERE `unitID` = " . $unitID);
    udb::query("DELETE FROM `rooms_units` WHERE `unitID` = " . $unitID);

    udb::query("DELETE o.*, u.* FROM `orderUnits` AS `u` INNER JOIN `orders` AS `o` USING(`orderID`) WHERE o.allDay = 1 AND u.unitID = " . $unitID);

    $result['count'] = udb::single_value("SELECT COUNT(*) FROM `rooms_units` WHERE `roomID` = " . $roomID) ?: 0;

    udb::update('rooms', ['roomCount' => $result['count']], "`roomID` = " . $roomID);

    $result['success'] = true;
}
catch (Exception $e){
    $result['success'] = false;
    $result['error']   = $e->getMessage();
}
