<?php
	include_once "../../../../bin/system.php";
	if($_POST['link']){
		$link = substr($_POST['link'],strpos($_POST['link'],"page")+12);

	$que = "SELECT `id` FROM `alias_text` WHERE `table`='search' AND `ref`=".$link;
	$ref = single_value($que);
	if($ref)
	echo intval($ref);
	}


	