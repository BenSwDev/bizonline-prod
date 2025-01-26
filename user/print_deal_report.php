<?php
require_once "auth.php";




?>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
</head>
<body style="direction:rtl;text-align:center;font-family:'Arial';font-size:16px">
<?php 
ob_start();
?>

<?php
$timeFrom = typemap(implode('-',array_reverse(explode('/',trim($_GET['from'] ?? date('01/m/Y'))))),"date");
$timeTill = typemap(implode('-',array_reverse(explode('/',trim($_GET['to'] ?? date('t/m/Y'))))),"date");
$siteID = $_CURRENT_USER->active_site();
$text_data = udb::single_value('SELECT infoInvoiceText FROM sites WHERE siteID ='.$siteID);

?>
<table style="width:100%;max-width:900px;text-align:center;">
	<tr>
		<td>
			<div style='font-size:18px;font-weight:bold'>דוח ריכוז עסקאות</div>
			<div style='margin-top:10px'>דיווח מע"מ לתקופה <?=implode('/',array_reverse(explode('-',$timeFrom)))?> - <?=implode('/',array_reverse(explode('-',$timeTill)))?></div>
			<div style='margin-top:10px;line-height:1.5'><?=nl2br($text_data)?></div>
		</td>		
	</tr>
</table>
<div style="height:20px"></div>

<div style="display:flex;max-width: 800px;margin: 0 auto;align-items: start;">
<table style='width:300px'>
	<tr><td style='text-align:center' colspan=2><b>פירוט עסקאות</b></td></tr>
<?


UserUtilsNew::init($siteID);

$where = $where2 = $where3 = ["p.complete = 1", "o.siteID = " . $siteID, "p.startTime BETWEEN '" . $timeFrom . " 00:00:00' AND '" . $timeTill . " 23:59:59'", 'vou' => "(p.payType <> 'coupon' OR p.provider <> 'vouchers')", 'sub' => "p.payType <> 'member2'"];
$withRefund = true;

//if ($sid && $_CURRENT_USER->has($sid))
//    $where[] = $where2[] = $where3[] = "o.siteID = " . $sid;
//else
//    $where[] = $where2[] = $where3[] = "o.siteID IN (" . $_CURRENT_USER->sites(true) . ")";

if ($_GET['orderSign'] == 'done')
    $where[] = "o.approved = 1";
elseif ($_GET['orderSign'] == 'incomplete')
    $where[] = "o.approved = 0";

if ($_GET['payType'] && isset(UserUtilsNew::$payTypesFull[$_GET['payType']])){
    $where['pt'] = $where2['pt'] = $where3['pt'] = ($_GET['payType'] == 'ccard') ? "(p.payType = 'ccard' OR p.payType = 'pseudocc')" : "p.payType = '" . udb::escape_string($_GET['payType']) . "'";
    $withRefund  = ($_GET['payType'] == 'refund');
}
elseif (is_array($_GET['payType2'])){
    $ptypes = typemap($_GET['payType2'], ['string']);

    if (in_array('ccard', $ptypes))
        $ptypes[] = 'pseudocc';

    $where['pt'] = $where2['pt'] = $where3['pt'] = "p.payType IN ('" . implode("','", array_map('udb::escape_string', $ptypes)) . "')";
    $withRefund  = in_array('refund', $ptypes);
}

if ($freeText = udb::escape_string(typemap($_GET['free'] ?? '', 'string'))){
    $list = ['customerName', 'customerEmail', 'customerPhone', 'customerTZ'];
    $where[] = "(`" . implode("` LIKE '%" . $freeText . "%' OR `", $list) . "` LIKE '%" . $freeText . "%')";

    $list2 = ['clientName', 'clientEmail', 'clientPhone', 'clientTZ'];
    $where2[] = "(`" . implode("` LIKE '%" . $freeText . "%' OR `", $list2) . "` LIKE '%" . $freeText . "%')";

    $list2 = ['giftSender', 'famname', 'giftReciver', 'giftPhoneSender', 'giftPhoneReciver', 'giftEmailSender', 'giftEmailReciver'];
    $where3[] = "(`" . implode("` LIKE '%" . $freeText . "%' OR `", $list2) . "` LIKE '%" . $freeText . "%')";
}

//$where = [/*"p.endTime IS NOT NULL",*/ "p.complete = 1", "o.siteID = " . $siteID, "p.startTime BETWEEN '" . $timeFrom . " 00:00:00' AND '" . $timeTill . " 23:59:59'"];
//
//if ($_GET['orderSign'] == 'done')
//    $where[] = "o.approved = 1";
//elseif ($_GET['orderSign'] == 'incomplete')
//    $where[] = "o.approved = 0";
//
//if ($_GET['payType'] && isset(UserUtilsNew::$payTypesFull[$_GET['payType']]))
//    $where['pt'] = ($_GET['payType'] == 'ccard') ? "(p.payType = 'ccard' OR p.payType = 'pseudocc')" : "p.payType = '" . udb::escape_string($_GET['payType']) . "'";
//
//if ($freeText = udb::escape_string(typemap($_GET['free'] ?? '', 'string'))){
//    $list = ['customerName', 'customerEmail', 'customerPhone', 'customerTZ'];
//    $where[] = "(`" . implode("` LIKE '%" . $freeText . "%' OR `", $list) . "` LIKE '%" . $freeText . "%')";
//}
//
//if ($_GET['otype'] && UserUtilsNew::$orderTypes[$_GET['otype']])
//    $where[] = "o.orderType = '" . udb::escape_string($_GET['otype']) . "'";

$where4 = $where3;      // special conditions for Vouchers refunds - no limitations on type of initial payment
unset($where4['pt']);

$totalQue = "SELECT `payType`, SUM(`sum`) AS `total`, COUNT(*) AS `cnt`
             FROM (
                (SELECT p.payType, p.sum 
                FROM `orderPayments` AS `p` INNER JOIN `orders` AS `o` USING(`orderID`) 
                WHERE " . implode(' AND ', $where) . " AND p.subType NOT IN ('card_test', 'freeze_sum') AND p.cancelled = 0)
                UNION ALL
                (SELECT p.payType, p.sum 
                FROM `subscriptionPayments` AS `p` INNER JOIN `subscriptions` AS `o` USING(`subID`) 
                WHERE " . implode(' AND ', $where2) . " AND p.subType NOT IN ('card_test', 'freeze_sum') AND p.cancelled = 0)
                UNION ALL
                (SELECT p.payType, p.sum 
                FROM `gift_purchase_payments` AS `p` INNER JOIN `gifts_purchases` AS `o` ON (p.orderID = o.pID) 
                WHERE " . implode(' AND ', $where3) . " AND p.subType NOT IN ('card_test', 'freeze_sum') AND p.cancelled = 0)
                " . ($withRefund ? "
                UNION ALL
                (SELECT 'refund', -p.sum
                FROM `gift_purchase_payments` AS `p` INNER JOIN `gifts_purchases` AS `o` ON (p.orderID = o.pID) INNER JOIN `pm_transactions` AS `t` ON (t.transID = o.refunded)
                WHERE " . str_replace('p.startTime', 't.createTIme', implode(' AND ', $where4)) . " AND t.status = 1)
                " : "") . "
             ) AS `bigT`
             GROUP BY `payType` 
             ORDER BY NULL";
$totals = udb::key_row($totalQue, 'payType');


/*if ($totals['refund']['total']){
    $refunds = udb::single_list("SELECT p.lineID, p.payType, p.sum, p.inputData FROM `orderPayments` AS `p` INNER JOIN `orders` AS `o` USING(`orderID`) WHERE " . implode(' AND ', $where) . " AND p.payType = 'refund'");
    foreach($refunds as $refund){
        $inp = json_decode($refund['inputData'], true);
        $_type = '';

        if ($inp['lineID']){
            $_type = udb::single_value("SELECT IFNULL(`provider`, `payType`) FROM `orderPayments` WHERE `lineID` = " . intval($inp['lineID']));
//            if (isset($pays[$refund['lineID']]))
//                $pays[$refund['lineID']]['_rtype'] = $_type;
        }

        //$totals[$_type ?: 'ccard']['total'] += $refund['sum'];
        //$totals[$_type ?: 'ccard']['cnt']++;
    }

    $refunds = udb::single_list("SELECT p.lineID, p.payType, p.sum, p.inputData FROM `subscriptionPayments` AS `p` INNER JOIN `subscriptions` AS `o` USING(`subID`) WHERE " . implode(' AND ', $where2) . " AND p.payType = 'refund'");
    foreach($refunds as $refund){
        $inp = json_decode($refund['inputData'], true);
        $_type = '';

        if ($inp['lineID']){
            $_type = udb::single_value("SELECT IFNULL(`provider`, `payType`) FROM `subscriptionPayments` WHERE `lineID` = " . intval($inp['lineID']));
//            if (isset($pays[$refund['lineID']]))
//                $pays[$refund['lineID']]['_rtype'] = $_type;
        }

        //$totals[$_type ?: 'ccard']['total'] += $refund['sum'];
        //$totals[$_type ?: 'ccard']['cnt']++;
    }

    $refunds = udb::single_list("SELECT p.lineID, p.payType, t.sum FROM `gift_purchase_payments` AS `p` INNER JOIN `gifts_purchases` AS `o` ON (p.orderID = o.pID) INNER JOIN `pm_transactions` AS `t` ON (t.transID = o.refunded) WHERE " . str_replace('p.startTime', 't.createTIme', implode(' AND ', $where4)) . " AND t.status = 1");
    foreach($refunds as $refund){
        //$totals[$refund['payType'] ?: 'ccard']['total'] -= $refund['sum'];
        //$totals[$refund['payType'] ?: 'ccard']['cnt']++;
    }

    unset($refunds);
}*/


$subTotals = [];
if ($totals['coupon']['total']){
    unset($where['vou'], $where2['vou'], $where['sub'], $where2['sub']);

    $where['pt'] = $where2['pt'] = "p.payType = 'coupon' AND p.provider <> 'vouchers'";

    $que = "SELECT `provider`, SUM(`sum`) AS `total`, COUNT(*) AS `cnt`
            FROM (
                (SELECT p.provider, p.sum FROM `orderPayments` AS `p` INNER JOIN `orders` AS `o` USING(`orderID`) WHERE " . implode(' AND ', $where) . " AND p.subType NOT IN ('card_test', 'freeze_sum') AND p.cancelled = 0)
                UNION ALL
                (SELECT p.provider, p.sum FROM `subscriptionPayments` AS `p` INNER JOIN `subscriptions` AS `o` USING(`subID`) WHERE " . implode(' AND ', $where2) . " AND p.subType NOT IN ('card_test', 'freeze_sum') AND p.cancelled = 0)
            ) as `uni`
            GROUP BY `provider` 
            ORDER BY NULL";
    $subTotals = udb::key_row($que, 'provider');
}

$payList = UserUtilsNew::$payTypesFull;
unset($payList['pseudocc']);

if (isset($totals['pseudocc']))
    $totals['ccard'] = ($totals['ccard'] ?? 0) + $totals['pseudocc'];

$all_totals = $all_totals_count = 0;

$allCoupons = udb::key_value("SELECT `key`, `shortname` FROM `payTypes` where parent=11");

foreach($payList as $key => $name){
    if (isset($totals[$key])){
        $all_totals +=$totals[$key]['total'];
        $all_totals_count +=$totals[$key]['cnt'];
?>
        <tr>
            <td><?=$name?></td>
            <td style="direction:ltr"><b>(<?=number_format($totals[$key]['cnt'])?>)</b> ₪<?=rtrim(rtrim(number_format($totals[$key]['total'], 1), '0'), '.')?></td>
        </tr>
<?php
    }

    if ($key == 'coupon'){
?>
	<tr><td><table class='inner' style='width:200px'>
	
<?php
        foreach($subTotals as $_type => $data){
?>
            <tr style='background:#eee'>
                <td><?=$allCoupons[$_type]?></td>
                <td style="direction:ltr"><b>(<?=number_format($data['cnt'])?>)</b> ₪<?=rtrim(rtrim(number_format($data['total'], 1), '0'), '.')?></td>
            </tr>
<?php
        }?>
	</table></td></tr>
	<?
	}
}
?>
	<tr>
		<th>סה"כ מחזור כל העסקאות</th>
		<th style="direction:ltr">₪<?=rtrim(rtrim(number_format($all_totals, 1), '0'), '.')?></th>
	</tr>

	<tr>
		<th>סה"כ פעולות</th>
		<th style="direction:ltr"><?=$all_totals_count?></th>
	</tr>
</table>
<?
if($_GET['from']){
    $timeFrom = typemap(implode('-',array_reverse(explode('/',trim($_GET['from'])))),"date");
}else{
    $timeFrom = date("Y-m-01");
    $_GET['from'] = implode('/',array_reverse(explode('-',trim($timeFrom))));
}

if($_GET['to']){
    $timeUntil = typemap(implode('-',array_reverse(explode('/',trim($_GET['to'])))),"date");
}else{
    $timeUntil = date("Y-m-t");
    $_GET['to'] = implode('/',array_reverse(explode('-',trim($timeUntil))));
}





$time_where = " AND T_orders.`timeFrom` >= '". $timeFrom . " 00:00:00' AND T_orders.`timeFrom` <= '". $timeUntil . " 23:59:59' ";
$date_type = '`timeFrom`';



$sids_str = $_CURRENT_USER->sites(true);
$daily_ex = array();
	
	
	$que = "SELECT `o`.orderID, `o`.extras,	 		
			CAST(`T_orders`.".$date_type." AS DATE) AS daydate
			FROM orders AS `o`
			INNER JOIN orders AS T_orders ON (T_orders.parentOrder = o.orderID)
			WHERE o.siteID IN (" . $sids_str . ") AND o.status=1  ".$time_where." AND `o`.extras IS NOT NULL AND `o`.extras NOT LIKE ''
			GROUP BY o.orderID
			ORDER BY daydate";
    $oextras = udb::full_list($que);
	foreach($oextras as $extra){
		$exx = json_decode($extra['extras'], true) ;	
		
		if(is_array($exx)){
			foreach($exx['extras']  as $ex){
				if($ex['extraID'])
					$extraIDs[$ex['extraID']] = $ex['extraID'];
			}
		}
	}
if($extraIDs){
	$que = "SELECT e.extraID, e.extraName FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) 
                WHERE (s.siteID IN (" . $sids_str . ") AND s.included = 0 AND s.active = 1) OR e.extraID IN(".implode(',',$extraIDs).") ORDER BY e.showOrder";
	$extrasNames = udb::key_value($que);

	//print_r($oextras);
	foreach($oextras as $extra){
		$exx = json_decode($extra['extras'], true) ;	
		
		if(is_array($exx)){
			foreach($exx['extras']  as $ex){	
				//print_r($ex).PHP_EOL;
				$ex_count[$ex['extraID']] += $ex['count'];
				$ex_price[$ex['extraID']] += $ex['price']*$ex['count'];
				$all_ex_types[$ex['extraID']] = $extrasNames[$ex['extraID']];
			}
		}
	}
?>

<table style="display:none">
	<tr><th colspan=3>מכירת תוספים</th></tr>
	<?
	foreach($all_ex_types as $k => $ex_name){?>
		<tr>
			<td><?=$ex_name?></td>
			<td>₪<?=number_format($ex_price[$k])?></td>
			<td><?=number_format($ex_count[$k])?></td>
		</tr>
	<?
		$ex_total_price += $ex_price[$k];
		$ex_total_count += $ex_count[$k];
	}?>
		<tr>
			<th>סה"כ</th>
			<th>₪<?=number_format($ex_total_price)?></th>
			<th><?=number_format($ex_total_count)?></th>
		</tr>

</table>

<?}?>
</div>

<div style='height:20px'></div>

<style>
table {border-collapse:collapse;width:300px; margin:0 auto}
table.inner {border-collapse:collapse;width:200px; margin:0 auto}
table td , table th{padding:5px;border:1px #333 solid}
.print{cursor:pointer;background: #0dabb6;color:#FFF;text-decoration:none;font-size:24px;font-weight:600;font-family:arial;margin:10px 0;clear:both;display: block;width: 100%;text-align: center;font-size: 16px;line-height: 50px;max-width: 200px;margin: 0 auto;}
@media print{
.print{display:none}
	@page :footer {
        display: none
    }
  
    @page :header {
        display: none
    }
}
</style>
<?php 
$report = ob_get_clean();



require_once('TCPDF/tcpdf_config.php');
require_once('TCPDF/tcpdf.php');

$pdf_file = '_' . str_replace('.', '', microtime(true)) . mt_rand(100, 999) . '.pdf';
$upath = __DIR__ . '/reports/'.$pdf_file;

genContractPDF($upath, $report);

echo $report;

?>
<?/*
<a style="background: #0dabb6;color:#FFF;text-decoration:none;font-size:24px;font-weight:600;font-family:arial;margin:10px 0;clear:both;display: block;width: 100%;text-align: center;font-size: 16px;line-height: 50px;max-width: 200px;margin: 0 auto;" target="_blank" href="/user/reports/<?=$pdf_file?>" download>הורדת PDF</a>
*/?>
<a class='print' target="_blank" onclick="window.print();return false; ">הדפסה</a>

</body>
</html>
