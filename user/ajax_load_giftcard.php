<?php
include_once "auth.php";
include_once "../picUpload.php";

$id = intval($_GET['id']) ?? intval($_POST['siteID2']); //must use siteID2!!!
$act = intval($_GET['act']);
$results = [];
$results['success'] = false;

switch($act) {
    case 0://just get data
        $sql = "SELECT * FROM `giftCards` where deleted=0 and giftCardID=".$id;
        $item = udb::single_row($sql);
		if(isset($_SESSION['user_id']) || intval($_SESSION['user_id']))
			$results['admin']=1;
        if($item) {
            $results['success'] = true;
            $results['data'] = $item;
            $results['data']['description'] = $results['data']['description'];
            $results['data']['restrictions'] = $item['restrictions'] == null ? ' ' : $item['restrictions'];
        }
        break;
    case 1: //inset or update
        $cp = [];
        $giftCardID = intval($_POST['giftCardID']);
        $useDate = null;
        if($_POST['expdate']) {
            $useDate = str_replace("/","-",$_POST['expdate']);
        }

        $cp['title'] = typemap($_POST['title'],'text');
        $cp['siteID'] = intval($_POST['siteID2']); //must use siteID2!!!
        $cp['description'] = typemap($_POST['desc'],'text');
        $cp['restrictions'] = typemap($_POST['restrictions'],'text');
        $cp['sum'] = intval($_POST['amount']);
        $cp['showPrice'] = intval($_POST['showPrice']);
        //giftValue 

        $cp['daysValid'] = intval($_POST['daysValid']) ?? 12;
//        if($useDate) {
//            $cp['expirationDate'] = date("Y-m-d H:i",strtotime($useDate));
//        }

        if($_FILES['picpic']) {
            $photo = pictureUpload('picpic',"../gallery/");
            if($photo && $photo[0]['file']) {
                $cp['image'] = $photo[0]['file'];
            }
        }
        if($_POST['removepicpic']) {
            $cp['image'] = '';
        }
        $results['success'] = true;
        if($giftCardID) {
            udb::update("giftCards",$cp," giftCardID=".$giftCardID);
        }
        else {
            $count = udb::single_value("select count(*) from giftCards where siteID=".intval($_POST['siteID2'])); //must use siteID2!!!
            $cp['showOrder'] = $count + 1;
            udb::insert("giftCards",$cp);
        }
        break;
        case 2: //set active or not
            $sql = "update `giftCards` set active= NOT active where giftCardID=".$id;
            udb::query($sql);
            $results['success'] = true;
            break;
    case 3: //set active or not
        $sql = "update `giftCards` set deleted=1 where giftCardID=".$id;
        udb::query($sql);
        $results['success'] = true;
        break;
    case 4:
        $showOrder = 1;
        foreach($_POST['data'] as $item) {
            $cp = [];
            $cp['showOrder'] = $showOrder;
            udb::update("giftCards",$cp," giftCardID=".$item);
            $showOrder++;
        }
        $results['success'] = true;
        break;
}



echo json_encode($results, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

