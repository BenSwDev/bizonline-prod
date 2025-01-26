<?
include_once "../bin/system.php";

$siteID = intval($_POST['siteID']);

if(!$_POST['clientName']){
    echo "שם חובה";
    exit;
}
if(!$_POST['clientPhone']){
    echo "טלפון חובה";
    exit;
}
$regex = "/^(\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i";
if(!preg_match("/^[0-9]{1,10}$/",$_POST["clientPhone"])){
    echo "טלפון שגוי";
    exit;
}

$que="SELECT TITLE, siteID, phone1, owners, owner_real FROM sites WHERE siteID=".$siteID;
$site=udb::single_row($que);

$clientName=inDB($_POST['clientName']);
$sender=$site['phone1'];
$toPhone=inDB($_POST['clientPhone']);

$message = $clientName." שלום, ";
$message.="\n";
$message.="אנו מודים לכם שבחרתם לבלות את חופשתכם ב".$site['TITLE'].". ";
$message.="\n";
$message.="אם נהניתם - מאוד יעזור לנו אם תמלאו חוות דעת באתר צימרטופ, בלינק הבא:";
$message.="\n";
$message.=showAlias("sites", $site['siteID'])."?toRev=1";
$message.="\n";
$message.="נשמח לארח אתכם שוב.";
$message.="\n";
$message.="תודה מראש, ".$site['owner_real'];

$response=maskyoo_send_sms($message,$toPhone, $sender);
if($response=="ERROR"){
    echo "error";
    exit;
} else {

    $cp=Array();
    $cp['siteID']=$site['siteID'];
    $cp['clientName']=$clientName;
    $cp['clientPhone']=$toPhone;
    udb::insert("sms_log", $cp);

}