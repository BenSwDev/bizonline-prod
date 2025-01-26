<?php
require_once "auth.php";

//define('ACTIVE',"Kew0Rd!Kew0Rd!Kew0Rd!");
//header('Content-Type: application/json');

$result = new JsonResult();

try{
    if(!$_POST['phone'] || !$_POST['msg'])
        throw new Exception (Dictionary::translate("משהו שהשתבש"));

    $siteID = intval($_POST['sid']) ?: $_CURRENT_USER->active_site();
    $smsName = udb::single_value("SELECT `smsName` FROM `sites` WHERE `siteID` = " . $siteID);

    $input = typemap($_POST, [
        'phone' => 'int',
        'msg' => 'string'
    ]);

    $send = Maskyoo::sms($input['msg'], $input['phone'], $smsName ?: 'BizOnline');
    if($send)
        $result['msg'] = "ההודעה נשלחה בהצלחה";

}
catch(Exception $e){
    $result['error'] = $e->getMessage();
}

//echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
