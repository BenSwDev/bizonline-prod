<?
include_once "../../bin/system.php";

if(isset($_POST['ids'])){
	$ids = explode(",", $_POST['ids']);
		foreach($ids as $key=>$id){
		$query=Array();
		$query['innerShowOrder']=$key;
		udb::update($_POST['table'], $query, "nid=".$id."");
	}
}