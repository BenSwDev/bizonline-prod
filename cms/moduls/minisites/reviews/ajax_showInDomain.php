<?php
define('ACTIVE',"Kew0Rd!Kew0Rd!Kew0Rd!");
include_once "../../../bin/system.php";



$reviewID = intval($_POST['reviewID']);
$domainID = intval($_POST['domainID']);
if($reviewID && $domainID){
	$que = "SELECT *  FROM reviewsDomains  WHERE reviewID = ".$reviewID;
	$res= udb::full_list($que);

	if($res){
		udb::query("UPDATE reviewsDomains SET domainID=". $domainID ." WHERE reviewID=".$reviewID);	
		 echo 'ok';
	}else{
		udb::query("INSERT INTO reviewsDomains  (reviewID,domainID) VALUES (". $reviewID .",". $domainID .")");
		 echo 'ok2';
	}
}else{
	echo "no data";
}

?>