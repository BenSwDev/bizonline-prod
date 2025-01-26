<?php
include_once "../../bin/system.php";
$siteID=intval($_GET['siteID']);
$domainID=intval($_GET['domainID']);
$results = [];
try {
    if(isset($_POST['ids'])){
        $ids = explode(",", $_POST['ids']);
        $key = 0;
        foreach($ids as $id){
            $query=Array();
            $key++;
            $id = intval(str_replace("galRow","",$id));
            $query['showOrder']=$key;
            $results[] = $id;
            udb::update("sites_galleries", $query, " galleryID=".$id." AND siteID=".$siteID." ");
            $query=Array();
            $query['orderGallery']=$key;
            udb::update("galleries", $query, "GalleryID=".$id." AND siteID=".$siteID." AND domainID=".$domainID);
        }
    }
} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}

echo json_encode($results);
