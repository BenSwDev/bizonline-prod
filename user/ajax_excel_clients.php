<?php
require_once "auth.php";

$sid = intval($_GET['sid']);

if($_GET['from']){
    $timeFrom = typemap(implode('-',array_reverse(explode('/',trim($_GET['from'])))),"date");
}else{
    $timeFrom = '2001-01-01';
    $_GET['from'] = implode('/',array_reverse(explode('-',trim($timeFrom))));
}

if($_GET['to']){
    $timeUntil = typemap(implode('-',array_reverse(explode('/',trim($_GET['to'])))),"date");
}else{
    $timeUntil = date("Y-m-t");
    $_GET['to'] = implode('/',array_reverse(explode('-',trim($timeUntil))));
}

$canDup = true;
$where = ["c.siteID IN (" . ($sid ?: $_CURRENT_USER->sites(true)) . ")"];

if(!$_GET['sort'] || !in_array($_GET['sort'], ['ASC', 'DESC']))
    $_GET['sort'] = "DESC";

if ($_GET['source'] == 'health2' || $_GET['source'] == 'health'){
    $where[] = "c.source = 'health'";

    if ($_GET['source'] == 'health2')
        $canDup = false;
}
elseif ($_GET['source'])
    $where[] = "c.source = '" . udb::escape_string(typemap($_GET['source'], 'string')) . "'";

if ($_GET['ads'] > 0)
    $where[] = "c.allowAds = 1";
elseif ($_GET['ads'] < 0)
    $where[] = "c.allowAds = 0";

if ($_GET['sfld'] == 'phone'){
    $sorter = 'c.clientMobile ' . udb::escape_string($_GET['sort']);
}
elseif ($_GET['timeType'] == 'u'){
    $where[] = "c.updateTime BETWEEN '" . udb::escape_string($timeFrom) . "' AND '" . udb::escape_string($timeUntil) . "'";
    $sorter  = 'c.updateTime ' . udb::escape_string($_GET['sort']);
}
else {
    $where[] = "c.createTime BETWEEN '" . udb::escape_string($timeFrom) . "' AND '" . udb::escape_string($timeUntil) . "'";
    $sorter  = 'c.createTime ' . udb::escape_string($_GET['sort']);
}

if ($freeText = udb::escape_string(typemap($_GET['free'] ?? '', 'string'))){
    if (is_numeric($freeText))
        $list = ['c.clientEmail', 'c.clientPhone', 'c.clientMobile', 'c.clientPassport', 'c.clientID'];
    else
        $list = ['c.clientName', 'c.clientEmail'];

    $where[] = "(" . implode(" LIKE '%" . $freeText . "%' OR ", $list) . " LIKE '%" . $freeText . "%')";
}

header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: binary");
header("Content-disposition: attachment; filename=\"clients.csv\"");

$columns = ['ID', 'שם מלא', 'ת.ז.', 'טלפון', 'דוא"ל', 'כתובת', 'דיוור', 'תאריך הוספה', 'עדכון אחרון'];

$xmlFile = fopen('php://output', 'w');

fwrite($xmlFile, "\xEF\xBB\xBF");        // BOM
fputcsv($xmlFile, $columns);

$temp = udb::key_row("SELECT c.*, sites.siteName FROM `crm_clients` AS `c` INNER JOIN `sites` USING(`siteID`) WHERE " . implode(" AND ", $where) . ($canDup ? "" : " GROUP BY c.clientMobile "), 'clientID');
foreach($temp as $row){
    fputcsv($xmlFile, [
        $row['clientID'],
        $row['clientName'],
        "=\"" .$row['clientPassport']. "\"",
        "=\"" .$row['clientMobile']. "\"",
        $row['clientEmail'],
        $row['clientAddress'],
        $row['allowAds'] ? 'כן' : '',
        date('d/m/y', strtotime($row['createTime'])),
        date('d/m/y', strtotime($row['updateTime']))
    ]);
}

fclose($xmlFile);
