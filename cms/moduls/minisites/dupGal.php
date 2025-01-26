<?php
	error_reporting(1);
	ini_set('display_errors', 1);
	include_once "../../bin/system.php";
	include "class.siteduplicator.php";
	$galID = intval($_POST['galID']);
	$roomID = intval($_POST['roomID']) ? intval($_POST['roomID']) : null;

	$toDomain = intval($_POST['toDomain']);
	$curDomain = intval($_POST['curDomain']);
	$galleryType  = $_POST['galleryType'];
	$galleryType = ($galleryType == "site_main_galleries") ? $galleryType : null;



	if($galID){
		$que = "SELECT * FROM `galleries` WHERE galleryID=".$galID;
		$galleries = udb::single_row($que);

        $cloner = new siteduplicator($galleries['siteID'],$toDomain,1,0 , $curDomain);

        $cloner->dupGallery($galID , $roomID , $toDomain , $curDomain , $galleryType);

	}
?>
