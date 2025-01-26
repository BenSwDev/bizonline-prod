<?php 

	include_once "../../bin/system.php";
	$exists = udb::single_value("SELECT showAutoSug FROM settlements WHERE settlementID =".intval($_POST['id']));
	$change = $exists==1? 0 : 1;
	  $siteData = [
            'showAutoSug' => $change
		];
    udb::update('settlements', $siteData, '`settlementID` = '.intval($_POST['id']));
	echo $change; 
?>
