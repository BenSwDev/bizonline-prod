<?php 
require_once "auth.php";

$result = new JsonResult(['status' => 99]);

$name = $_POST['name'] != ""?udb::escape_string($_POST['name']):'';
$phone = $_POST['phone'] != ""?udb::escape_string($_POST['phone']):'';
$pid = $_POST['pid'] != ""?intval(udb::escape_string($_POST['pid'])):'';
$oid = $_POST['oid'] != ""?intval(udb::escape_string($_POST['oid'])):'';

$act = udb::escape_string($_POST['act']);

try {
    switch($act) {
        case 'search':
            if($name || $phone) {
                $orders = udb::full_list("SELECT * FROM orders WHERE parentOrder != orderID AND treatmentID > 0 AND customerPhone LIKE '%".$phone."%' AND customerName LIKE '%".$name."%' AND siteID IN(".$_CURRENT_USER->sites(true).")");
                if($orders) {
                    $html = '';
                    foreach($orders as $order) {
                        $time = $order['timeFrom']?$order['timeFrom']:'';
                        $html .= '
                        <div class="order" onclick="changeOrder('.$pid.', '.$order['orderID'].')" data-pid="'.$pid.'" data-orderid="'.$order['orderID'].'">
                            <div class="oid">מספר הזמנה:<br />'.$order['orderID'].'</div>
                            <div class="cname">שם המזמין:<br />'.$order['customerName'].'</div>
                            <div class="cphone">טלפון המזמין:<br />'.$order['customerPhone'].'</div>
                            <div class="ctime">תאריך ושעה:<br />'.$time.'</div>
                        </div>
                        ';
                    }
                    if($html)$result['html'] = $html;
                } else throw new Exception("nothing");
            }
        break;
        case 'changeOrder':
            print_r('pid: '.$pid.' oid: '.$oid);
            if($pid && $oid) {
                $declare = udb::single_row("SELECT * FROM health_declare WHERE declareID=".$pid);
                if(isset($declare) && intval($declare['fromOrder']) == 0) {
                    udb::update('health_declare', ['orderID' => $oid], "`declareID` = " . $pid);
                    $result['msg'] = 'updated! pid:'.$pid.' orderid changed to.'.$oid;
                } else throw new Exception("nothing");

            } else throw new Exception("nothing");
        break;
        default:
            throw new Exception("nothing");
        break;
    }
    $result['status'] = 0;
}
catch (Exception $e){
    $result['error']  = $e->getMessage();
    $result['status'] = 99;
}
