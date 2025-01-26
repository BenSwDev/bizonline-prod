<?php define('ACTIVE',"Kew0Rd!Kew0Rd!Kew0Rd!");
header('Content-Type: application/json');

try{


if(!$_POST['fullName']){
	throw new Exception (Dictionary::translate("נא הכנס שם מלא"));
}	
if(!$_POST['phone']){
	throw new Exception (Dictionary::translate("נא הכנס טלפון"));
}

$type_cc = $_POST['typecc']==1? "הקמת ספק" : "משתמש עם ספק קייים";

$message = '<body><img src="https://bizonline.co.il/user/assets/img/bizlogo2.png" style="width:180px;height:60px;display:block;margin-bottom:10px;float:right;" border=0 />';
$message .= "<div style='direction:rtl;clear:both'><p>פניה מביז אונליין הצטרפות לסליקה ".$type_cc."</p>";
$message .= "שם המתעניין: " . trim($_POST['fullName'])."<br>";
$message .= "שם המתחם: " . trim($_POST['siteName'])."<br>";
$message .= "טלפון: " . trim($_POST['phone'])."<br>";
$message .= "אימייל: " . trim($_POST['email'])."<br>";
if($_REQUEST['invoice']) $message .= "מעוניין בהנפקת חשבונית דרך המערכת.";
//if($_POST['fromPage']) $message .= "נשלח מדף סוויטה: " . trim($_POST['fromPage'])."<br>";
$message .= "</div></body>";


include_once "phpmailer/class.bizonlineMailer.php";
$mail = new bizonlineMailer();
$html = $message;
$mail->Subject  = 'פניה מביז אונליין - הצטרפות לסליקה '.$type_cc;
$mail->Body     = '<body style="margin:0">' . $html . '</body>';
$mail->AltBody  = trim(strip_tags($html));

$mail->addAddress('vila4uservice@gmail.com');
$mail->addAddress('shiran.vila4u@gmail.com');
$mail->addAddress('palombo.r@gmail.com');
$mail->addAddress('vila4uservice@gmail.com');

//$mail->addAddress('ssdstudio@gmail.com');


$ifsend = $mail->send();


$result['success'] = true;
$result['title'] = "נשלח בהצלחה";
$result['text'] = "ההודעה נשלחה בהצלחה נציגנו יצרו עמך קשר בזמן הקרוב";
$_RESULT['status'] = 0;

//$_SESSION['contact']==true;

}

catch(Exception $e){
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
