<?php

	include_once "../../bin/system.php";
	include_once "../../_globalFunction.php";

	$name = typemap($_POST['name'],'string');
	
	$que = "SELECT siteID FROM sites WHERE userName='".$name."'";
	$id = udb::single_value($que);

	if($id){
		echo "השם תפוס";
	}
	else{
		echo "השם פנוי";
	}

	exit;
