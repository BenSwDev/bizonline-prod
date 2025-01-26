<?php define('ACTIVE',"Kew0Rd!Kew0Rd!Kew0Rd!");
include_once "../../bin/system.php";
header('Content-Type: application/json');

try{
$siteID = $_POST['siteID'];

$que = "SELECT `email`,`phone`,`guid`,`signature` FROM sites WHERE `siteID` = ".$siteID;
$site = udb::single_row($que);

if($site['signature'])throw new Exception("ההסכם כבר נחתם");

$phone = $site['phone'];
$smsMsg = "להלן קישור להסכם - https://bizonline.co.il/vagreement.php?guid=".$site['guid'];

$message = '<body><img src="https://bizonline.co.il/user/assets/img/bizlogo2.png" style="width:180px;height:60px;display:block;margin-bottom:10px;float:right;" border=0 />';
$message .= "<div style='direction:rtl;clear:both'><p>פניה מביז אונליין</p>";
$message .= "<div style='direction:rtl;clear:both'><a href=\"https://bizonline.co.il/vagreement.php?guid=".$site['guid']."\">להלן קישור להסכם</a>";


$message .= "</div></body>";

file_get_contents('https://sms.deals/ws.php?service=send_sms&message='.urlencode(str_replace('<br />', '\r\n', $smsMsg)).'&dest='.$phone.'&sender=bizonline&username=s0509350015@gmail.com&password=plusplus');

include_once "../../../user/phpmailer/class.bizonlineMailer.php";
$mail = new bizonlineMailer();
$html = $message;
$mail->Subject  = 'הסכם מביז אונליין';
$mail->Body     = '<body style="margin:0">' . $html . '</body>';
$mail->AltBody  = trim(strip_tags($html));

//$mail->addAddress($site['email']);

$mail->addAddress($site['email']);


$ifsend = $mail->send();


$result['success'] = true;
$result['title'] = "נשלח בהצלחה";
//$result['text'] = "ההודעה נשלחה בהצלחה נציגנו יצרו עמך קשר בזמן הקרוב";
$_RESULT['status'] = 0;

//$_SESSION['contact']==true;

}

catch(Exception $e){
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
