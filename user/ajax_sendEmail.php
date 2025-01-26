<?php define('ACTIVE',"Kew0Rd!Kew0Rd!Kew0Rd!");
header('Content-Type: application/json');
try{
    if(!$_POST['to'] || !$_POST['subject'] || !$_POST['body']){
        throw new Exception (Dictionary::translate("משהו שהשתבש"));
    }

    $message = $_POST['body'];


    include_once "phpmailer/class.bizonlineMailer.php";
    $mail = new bizonlineMailer();
    $html = $message;
    $mail->Subject  = $_POST['subject'];
    $mail->Body     = '<body style="margin:0" dir="rtl">' . $html . '</body>';
    $mail->AltBody  = trim(strip_tags($html));

    $mail->addAddress($_POST['to']);

    $ifsend = $mail->send();


    $result['success'] = true;
    $result['title'] = "נשלח בהצלחה";
    $_RESULT['status'] = 0;

}
catch(Exception $e){
    $result['error'] = $e->getMessage();
}
echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
