<?php
	header('Content-Type: application/json');

	require_once "auth.php";


	$orderID = intval($_POST['orderID']);
	$siteID = intval($_POST['siteID']);
	$phone = typemap($_POST['phone'],"numeric");
	
	$text = typemap($_POST['sms_con'],"text");
	//$text = udb::escape_string($_POST['sms_con']);
	
try
{

	$order = udb::single_row("SELECT * FROM orders WHERE orderID=".$orderID);
	if(!$order && !isset($_POST['phone']))
		throw new Exception("לא נמצאה הזמנה");

	if(isset($_POST['phone']) && $order)
		throw new Exception("יש תקלה");

	if(empty($_POST['phone']) && empty($order['customerPhone']))
		throw new Exception("לא קיים טלפון לשליחה");


    if($order || $siteID)
		$smsName = udb::single_value("SELECT `smsName` FROM `sites` WHERE `siteID` = " . ($order?$order['siteID']:$siteID));

	$send = Maskyoo::sms($text, $order?$order['customerPhone']:$phone, $smsName ?: 'BizOnline');
	if(!$send)
		throw new Exception("יש תקלה בשליחת הSMS");

	if($order && !isset($_POST['phone']))
		$insert = udb::query("INSERT INTO `orders_sms`(`orderID`, `sms_con`, `phone`) VALUES (".$orderID.", '".$text."', '".($order?$order['customerPhone']:$phone)."')");	
	
	if(!$order && $siteID && isset($_POST['phone'])) {
		$insert = udb::query("INSERT INTO `sms_manual`(`phone`, `con`, `buserID`, `siteID`, `response`, `senderName`) VALUES (".$phone.", '".$text."', 0, ".$siteID.", '".$send."', '".$smsName."')");	
	}

	$result['msg'] = "SMS נשלח בהצלחה!";

} catch(Exception $e){
    $result['error'] = $e->getMessage();
}


echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
