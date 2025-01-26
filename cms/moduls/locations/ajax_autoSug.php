<?php 

	include_once "../../bin/system.php";
	$exists = udb::single_value("SELECT activeAutoSuggest FROM areas WHERE areaID =".intval($_POST['id']));
	$change = $exists==1? 0 : 1;
	  $siteData = [
            'activeAutoSuggest' => $change
		];
    udb::update('areas', $siteData, '`areaID` = '.intval($_POST['id']));
	echo $change; 
?>
