<?php
include_once "../../../bin/system.php";

$result = new JsonResult;

$unitID = intval($_POST['uid']);
$unitName = trim(filter_var($_POST['name'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW));

try {
    if (!$unitName)
        throw new Exception("Empty unit name");

    udb::update('rooms_units', ['unitName' => $unitName], "`unitID` = " . $unitID);

    $result['name']    = $unitName;
    $result['success'] = true;
}
catch (Exception $e){
    $result['success'] = false;
    $result['error']   = $e->getMessage();
}
