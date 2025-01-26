<?
include_once "../bin/system.php";
$pageID=intval($_POST['pageID']);
if(isset($_POST['ids'])){
	$ids = explode(",", $_POST['ids']);
		foreach($ids as $key=>$id){
		$query=Array();
		$query['ShowOrder']=$key;
		udb::update("galleries", $query, "GalleryID=".$id." AND pageID=".$pageID." ");
	}
}