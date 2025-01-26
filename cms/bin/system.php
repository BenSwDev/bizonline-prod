<?php
include_once "functions_and_constants_only!!!.php";

$root = "https://bizonline.co.il/cms";

session_start();

$que="SELECT system, content FROM configurations WHERE LangID=1";
$config=udb::key_row($que, "system");
if($config){
	foreach($config as $conf){
		define("".$conf['system']."", $conf['content']);
	}
}

if(!isset($_SESSION['user_id']) && !isset($_SESSION['siteID'])){
	if(strpos($_SERVER['REQUEST_URI'], "js_login.php") === false){
		include __DIR__ . "/../login.php";
		exit;
	}
}

$farr = array_keys($_FILES);
foreach($farr as $key)
    if (isset($_FILES[$key]['size']) && $_FILES[$key]['size'] == 0)
        unset($_FILES[$key]);
unset($farr, $key);

if ($_POST['domid'] || $_GET['domid'])
    DomainList::active(intval($_POST['domid'] ?? $_GET['domid']));
if ($_POST['langid'] || $_GET['langid'])
    LangList::active(intval($_POST['langid'] ?? $_GET['langid']));
