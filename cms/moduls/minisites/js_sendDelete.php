<?php define('ACTIVE',"Kew0Rd!Kew0Rd!Kew0Rd!");
header('Content-Type: application/json');
include_once "../../bin/system.php";

Dictionary::setLanguage(intval($_POST['lang']));

try{


if($_POST['pass'] != 'roy123'){
	throw new Exception (Dictionary::translate("הסיסמא אינה נכונה"));
}	

$result['siteid'] = $_POST['siteID'];

}

catch(Exception $e){
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
