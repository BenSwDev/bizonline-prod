<?php
	header('Content-Type: application/json');

	require_once "auth.php";


	$orderID = intval($_POST['orderID']);
	$text = typemap($_POST['sms_con'],"text");
	//$text = udb::escape_string($_POST['sms_con']);
	
try
{

	$order = udb::single_row("SELECT * FROM orders WHERE orderID=".$orderID);
	if(!$order)
		throw new Exception("לא נמצאה הזמנה");

    $smsName = udb::single_value("SELECT `smsName` FROM `sites` WHERE `siteID` = " . $order['siteID']);

	$send = Maskyoo::sms($text, $order['customerPhone'], $smsName ?: 'BizOnline');
	if(!$send)
		throw new Exception("יש תקלה בשליחת הSMS");

	$insert = udb::query("INSERT INTO `orders_sms`(`orderID`, `sms_con`) VALUES (".$orderID.", '".$text."')");

	$result['msg'] = "SMS נשלח בהצלחה!";

} catch(Exception $e){
    $result['error'] = $e->getMessage();
}


echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
