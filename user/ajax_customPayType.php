<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 22/02/2022
 * Time: 10:04
 */

require_once "auth.php";

if (!$_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN)){
    echo blockAccessMsg();
    return;
}
$result = new JsonResult(['status' => 99]);
$siteID2 = intval($_POST['siteID2']);
$title = inDb($_POST['title']);
$cpn_remarks = inDb($_POST['cpn_remarks']);
$parent = $_POST['parent'];
$couponPayed = intval($_POST['couponPayed']);
$cuponPrice = intval($_POST['cuponPrice']);
$active = intval($_POST['active']);
$customPayTypeID = intval($_POST['customPayTypeID']);
$act = $_POST['act'];
try {
    if(!$siteID2 ) {
        throw new Exception("חסר מזהה אתר");
    }
    switch($act) {
        case 'load':
            $id = intval($_POST['cpn']);
            if(!$id){
                throw new Exception("חסר מזהה קופון");
            }
            $result['data'] = udb::single_row("select * from customPayTypes where id=".$id);
            break;
        default:
            $que = [];
            $que['siteID'] = $siteID2;
            $que['cuponPrice'] = $cuponPrice;
            $que['couponPayed'] = $couponPayed;
            $que['active'] = $active;
            $que['parent'] = $parent;
            $que['shortname'] = $title;
            $que['fullname'] = $title;
            $que['cpn_remarks'] = $cpn_remarks;
            if($customPayTypeID) {
                udb::update("customPayTypes",$que," id=".$customPayTypeID);
            }
            else {
			    $que['key'] = time();
                udb::insert("customPayTypes",$que);
            }
            $result['status'] = 1;
            break;
    }

} catch (Exception $e) {
    $result['message'] = $e->getMessage();
}

