<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 01/08/2022
 * Time: 10:23
 */

define('ACTIVE',"Kew0Rd!Kew0Rd!Kew0Rd!");
header('Content-Type: application/json');
include_once "../../../bin/system.php";
$result = [];
try {
    $searchID = intval($_GET['searchID']);
    $sql = "select * from search where id=".intval($_GET['searchID']);
    $item = udb::single_row($sql);
    if($item) throw new Exception("חיפוש לא נמצא");

    udb::query("delete from searchCatch where search_id=".$searchID);

    udb::query("delete from alias where `table`='search' and ref=".$searchID);

    udb::query("delete from search where id=".$searchID);


} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
