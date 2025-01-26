<?php
include_once "../../bin/system.php";
if(intval($_POST['siteID'])!=''){
	$siteID=intval($_POST['siteID']);
	udb::update("sites",['active' => -1], "siteID=".$siteID);
}