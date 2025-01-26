<?php 
	include_once "../../bin/system.php";
	$galID = intval($_POST['id']);

	udb::query("DELETE p.* FROM pictures p INNER JOIN pictures_text USING (pictureID) WHERE `galleryID`=".$galID);
	udb::query("DELETE FROM galleries WHERE `galleryID`=".$galID);


?>