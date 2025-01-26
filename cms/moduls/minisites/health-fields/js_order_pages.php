<?
include_once "../../../bin/system.php";

if(isset($_POST['ids'])){
	$ids = explode(",", $_POST['ids']);
		foreach($ids as $key=>$id){
		$query=Array();
		$query['ShowOrder']=$key;
		udb::update("sites_health_fields", $query, "fieldID=".$id."");
	}
}