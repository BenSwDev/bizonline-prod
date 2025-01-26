<?
include_once "../../../bin/system.php";

if(isset($_POST['ids'])){
    $ids = explode(",", $_POST['ids']);
    foreach($ids as $key=>$id){
        $query=Array();
        $query['showOrder']=$key;
        udb::update("sites_treatments", $query, " siteID=".$siteID." and treatmentID=".$id);
    }
}