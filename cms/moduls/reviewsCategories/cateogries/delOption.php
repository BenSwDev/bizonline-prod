<?php 
	include_once "../../../bin/system.php";


	$id = intval($_POST['id']);
	udb::query("DELETE FROM `reviewOptions` WHERE id=".$id);

