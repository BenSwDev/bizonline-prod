<?php 

include_once "../../bin/system.php";

$que = "UPDATE `sites_domains` SET active=".intval($_POST['status'])." WHERE siteID=".intval($_POST['siteID'])." AND domainID=".intval($_POST['domainID']);
udb::query($que);