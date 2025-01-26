<?php
include_once "../../../bin/system.php";

$result = new JsonResult;

$siteID = intval($_POST['siteID']);
$roomID = intval($_POST['roomID'] ?? 0);

try {
    
	$order = $_POST['order'];
	
	 foreach($order as $k=>$ord) {
		$que = "update spaces set showOrder=".$ord." where spaceID=".$k;
		udb::query($que);
	 }
	
    $result['success'] = true;
}
catch (Exception $e){
    $result['success'] = false;
    $result['error']   = $e->getMessage();
}
