<?php


/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 06/05/2021
 * Time: 15:21
 */

include_once "../../../bin/system.php";

include_once "../../../_globalFunction.php";
//require_once 'excel.php';

$minDate = udb::single_row("select min(usageDate) as lowerDate,max(usageDate) as highestDate from giftCardsUsage");
$minYear = intval(date("Y",strtotime($minDate['lowerDate'])));
$minMonth = intval(date("m",strtotime($minDate['lowerDate'])));
$maxYear = intval(date("Y",strtotime($minDate['highestDate'])));
$maxMonth = intval(date("m",strtotime($minDate['highestDate'])));
$where = [];
$where[] = " 1=1 ";
if(intval($_GET['year'])) {
    $currYear = intval($_GET['year']);
    $where[] = "  YEAR(usageDate)='".$currYear."'";
    if(intval($_GET['month'])) {
        $currMonth = intval($_GET['month']);
        $where[] = "  MONTH(usageDate)='".$currMonth."'";
    }

}
else {
    $currYear = intval(date("Y"));
    $currMonth = intval(date("m"));
    $where[] = "  YEAR(usageDate)='".$currYear."'";
    $where[] = "  MONTH(usageDate)='".$currMonth."'";
}

if(intval($_GET['siteID']) == 0) {
    $sql = "select siteID,giftCardCommission from sites ";
    $coms = udb::key_row($sql,"siteID");
    $list = udb::full_list("SELECT giftCards.siteID,count(DISTINCT (giftCardsUsage.pID)) as totalCards,sum(giftCardsUsage.useageSum) as mimushSum,sum(gifts_purchases.sum) as totalPaid , sites.siteName,sites.bankName,sites.bankNumber,sites.bankBranch,sites.bankAccount,sites.bankAcoountOwner from giftCardsUsage left join giftCards on (giftCards.giftCardID = giftCardsUsage.giftCardID) left join gifts_purchases on(gifts_purchases.pID = giftCardsUsage.pID) left JOIN sites on (sites.siteID = giftCards.siteID) where ".implode(" AND ",$where)." GROUP by giftCards.siteID");
    $paids = udb::key_row("select * from commissionPayments where pMonth=".$currMonth . " and pYear=".$currYear,"siteID");
    $totals = array(
        'totalCards' => 0,
        'totalPaid' => 0,
        'mimushSum' => 0,
        'remains' => 0,
        'commi' => 0,
        'aToPay' => 0
    );
    $sql = "select siteID,giftCardCommission from sites ";
    $coms = udb::key_row($sql,"siteID");
    $list = udb::full_list("SELECT giftCards.siteID,count(DISTINCT (giftCardsUsage.pID)) as totalCards,sum(giftCardsUsage.useageSum) as mimushSum,sum(gifts_purchases.sum) as totalPaid , sites.siteName,sites.bankName,sites.bankNumber,sites.bankBranch,sites.bankAccount,sites.bankAcoountOwner from giftCardsUsage left join giftCards on (giftCards.giftCardID = giftCardsUsage.giftCardID) left join gifts_purchases on(gifts_purchases.pID = giftCardsUsage.pID) left JOIN sites on (sites.siteID = giftCards.siteID) where ".implode(" AND ",$where)." GROUP by giftCards.siteID");
    $paids = udb::key_row("select * from commissionPayments where pMonth=".$currMonth . " and pYear=".$currYear,"siteID");
    $totals = array(
        'totalCards' => 0,
        'totalPaid' => 0,
        'mimushSum' => 0,
        'remains' => 0,
        'commi' => 0,
        'aToPay' => 0
    );
    $dataForExcel = [];

    foreach($list as $item) {
        $commi = ($coms[$item['siteID']]['giftCardCommission'] / 100)  * $item['mimushSum'];
        $toalCards = udb::single_value("SELECT sum(gifts_purchases.sum) as totalPaid  FROM `gifts_purchases` left join giftCards on(giftCards.giftCardID = gifts_purchases.giftCardID) where `gifts_purchases`.`paid`=1 and giftCards.siteID=".$item['siteID']);

        $totals['totalCards'] += $item['totalCards'];
        //$totals['totalPaid'] += $item['totalPaid'];
        $totals['totalPaid'] += $toalCards;
        $totals['mimushSum'] += $item['mimushSum'];
        $totals['remains'] += ($item['totalPaid'] - $item['mimushSum']);
        $totals['commi'] += $commi;
        $totals['aToPay'] += ($item['totalPaid']-$commi);
        $bankData = 'בנק: ' . $item['bankName'] . ' , ' .'קוד בנק: ' . $item['bankNumber']. ' , ' .'סניף: ' . $item['bankBranch'] . ' , ' . 'מספר חשבון: ' . $item['bankAccount'] .PHP_EOL .'בעל החשבון: ' . $item['bankAcoountOwner'];
        $dataForExcel[] = ['siteName' => $item['siteName'], 'BankData' => $bankData , 'totalCards'=>$item['totalCards'], 'totalCardsSum'=>$toalCards,'mimushSum'=>$item['mimushSum'],'remains'=>$item['totalPaid'] - $item['mimushSum'],'commi'=>$commi,'toPay'=>$item['totalPaid']-$commi];
    }

    $dataForExcel[] = ['siteName' => '', 'BankData' => '' , 'totalCards'=>$totals['totalCards'], 'totalCardsSum'=>$totals['totalPaid'],'mimushSum'=>$totals['mimushSum'],'remains'=>$totals['remains'],'commi'=>$totals['commi'],'toPay'=>$totals['aToPay']];
}
else {
    $siteID = intval($_GET['siteID']);
    $sql = "select siteID,giftCardCommission,siteName from sites where siteID=".$siteID;
    $coms = udb::key_row($sql,"siteID");
    $siteName = $coms[$siteID]['siteName'];

    $where[] = " giftCards.siteID=".$siteID;

    $list = udb::full_list("SELECT giftCards.siteID,giftCards.title,giftCardsUsage.useageSum,giftCardsUsage.comments,giftCardsUsage.usageDate,giftCardsUsage.pID,
gifts_purchases.giftSender,gifts_purchases.famname,gifts_purchases.giftPhoneSender,gifts_purchases.sum,gifts_purchases.ordersID,
sites.siteName,sites.bankName,sites.bankNumber,sites.bankBranch,sites.bankAccount,sites.bankAcoountOwner from giftCardsUsage 
left join giftCards on (giftCards.giftCardID = giftCardsUsage.giftCardID) 
left join gifts_purchases on(gifts_purchases.pID = giftCardsUsage.pID) 
left JOIN sites on (sites.siteID = giftCards.siteID) where ".implode(" AND ",$where));
    $paids = udb::key_row("select * from commissionPayments where pMonth=".$currMonth . " and pYear=".$currYear,"siteID");
    $totals = array('useageSum' => 0 ,'comi' => 0 ,'aToPay' => 0);
    $dataForExcel = [];
    foreach($list as $item) {
        $commi = ($coms[$siteID]['giftCardCommission'] / 100)  * $item['useageSum'];
        $totals['useageSum'] +=$item['useageSum'];
        $totals['comi'] +=$commi;
        $totals['aToPay'] += ($item['useageSum']-$commi);
        $dataForExcel[] = ['usageDate' => date("d/m/Y H:i",strtotime($item['usageDate'])) , 'giftSender' => $item['giftSender'] . ' ' . $item['famname'] , 'giftPhoneSender' => $item['giftPhoneSender'] , 'title' => $item['title'] , 'ordersID' => "'" . $item['ordersID'] . "'" , 'comments' => $item['comments'],'sum' => $item['sum'] , 'useageSum'=>$item['useageSum'] , 'commiprec'=>$coms[$siteID]['giftCardCommission'] . '%' , 'commi' => $commi , 'remains' => $item['useageSum']-$commi];
    }

    $dataForExcel[] = ['usageDate' => '' , 'giftSender' => '' , 'giftPhoneSender' => '' , 'title' => '' , 'ordersID' => '' , 'comments' => '','sum' => '' , 'useageSum'=>$totals['useageSum'] , 'commiprec'=> '' , 'commi' => $totals['comi'] , 'remains' => $totals['aToPay']];
}


function cleanData(&$str) {
    $str = preg_replace("/\t/", "\\t", $str);
    $str = preg_replace("/\r?\n/", "\\n", $str);
    if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
    $str = mb_convert_encoding($str, 'UTF-16LE', 'UTF-8');
}


$filename = "Shovarim_" . time() . ".csv";


$flag = false;

foreach($dataForExcel as $row) {
    $columns = array_keys($row);
    break 1;
}


// new CODE
header('Content-Encoding: UTF-8');
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: binary");
header("Content-disposition: attachment; filename=\"" . $filename . "\"");

$out = fopen('php://output', 'w');
fputs($out, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
fputcsv($out, $columns);
foreach($dataForExcel as $row) {
    $newRow = array_values($row) ;
    fputcsv($out, $newRow);
}
fclose($out);
exit;
