<?php
include_once "../../../bin/system.php";

$order = json_decode(str_replace("\\","", $_POST['order']), true);

foreach($order as $key=>$ord){
	
	$cp=Array();
	$cp['showOrder']=$key;
	
	udb::update("search_homepage", $cp, "id=".$ord['id']."");
}
