<?
include_once "../bin/system.php";
$siteID=intval($_POST['siteID']);
if(isset($_POST['ids'])){
	$ids = explode(",", $_POST['ids']);
		foreach($ids as $key=>$id){
		$query=Array();
		$query['ShowOrder']=$key;
		udb::update("galleries", $query, "GalleryID=".$id." AND sID=".$siteID." ");
	}
}

$que="SELECT files.id, files.src, files.showorder, galleries.GalleryID, galleries.sID, galleries.ShowOrder
	  FROM files
	  INNER JOIN galleries ON (galleries.GalleryID = files.ref)
	  WHERE galleries.ifShow=1 AND files.ifshow=1 AND files.`table`='site' AND galleries.sID=".$siteID."
	  GROUP BY sID, files.id
	  ORDER BY galleries.ShowOrder DESC, files.showorder DESC
	  ";
$firstPictures = udb::key_row($que, "sID");
if($firstPictures){
	foreach($firstPictures as $first){
		$cp=Array();
		$cp['prPictureFirst']=$first['src'];
		udb::update("sites", $cp, "siteID=".$siteID."");
	}
}