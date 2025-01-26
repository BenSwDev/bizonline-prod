<?
include_once "../bin/system.php";

if(isset($_POST['ids'])){
	$ids = explode(",", $_POST['ids']);
		foreach($ids as $key=>$id){
			if($id){
				$query=Array();
				$query['showOrder']=$key;
				udb::update("sitesRooms", $query, "roomID=".$id." AND siteID=".intval($_POST['siteID'])."");
			}
	}
}