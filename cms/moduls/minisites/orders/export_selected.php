<?php



function typemap($param, $map){
    static $scalar = array('int' => 'intval', 'string' => 1, 'bool' => 'boolval', 'float' => 'floatval', 'decimal' => 1, 'html' => 1, 'text' => 1, 'email' => 1, 'date' => 1, 'numeric' => 1);

    if ((is_string($map) || is_callable($map)) && ($param === null || is_scalar($param))){
        switch($map){
            case 'int'    : return intval($param);
            case 'float'  : return floatval($param);
            case 'bool'   : return !!$param;
            case 'decimal': return round(floatval($param), 2);
            case 'string' : return trim(filter_var($param, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW));
            case 'email'  : return trim(filter_var(filter_var(trim($param), FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL) ?: '');
            case 'html'   : return trim(filter_var($param, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW));
            case 'text'   : return trim(str_replace('{:~:}', "\n", filter_var(str_replace("\n", '{:~:}', $param), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW)));
            case 'date'   : return preg_match('/^\d{4}-\d{2}-\d{2}$/i', trim($param)) ? trim($param) : null;
            case 'numeric': return preg_replace('/\D+/', '', $param);
            default       : return is_callable($map) ? $map($param) : null;
        }
    }
    elseif (is_array($map) && is_array($param)){
        reset($map);
        $key = key($map); $val = current($map);
        $result = array();

        if (count($map) == 1 && ($key === 0 || isset($scalar[$key]) || is_callable($key))){
            if (isset($scalar[$key]))
                $key_foo = is_string($scalar[$key]) ? $scalar[$key] : function($p) use ($key) {return typemap($p, $key);};
            else
                $key_foo = ($key === 0) ? null : $key;

            if (is_array($val))
                $val_foo = function($p) use ($val) {return typemap($p, $val);};
            elseif (isset($scalar[$val]))
                $val_foo = is_string($scalar[$val]) ? $scalar[$val] : function($p) use ($val) {return typemap($p, $val);};
            else
                $val_foo = $val;

            foreach($param as $k => $v)
                $key_foo ? $result[$key_foo($k)] = $val_foo($v) : $result[] = $val_foo($v);
        }
        else
            foreach($map as $k => $v){
                if ($k[0] == '!' || isset($param[$k])){
                    $rk = ($k[0] == '!') ? substr($k, 1) : $k;
                    $result[typemap($rk, 'string')] = typemap(isset($param[$rk]) ? $param[$rk] : null, $v);
                }
            }

        return $result;
    }
    elseif ($map == 'date' && is_array($param)){
        if (count($param) == 3 && checkdate($param[1], $param[2], $param[0]))
            return date('Y-m-d', mktime(9, 0, 0, $param[1], $param[2], $param[0]));
        return null;
    }
    return null;
}



$frameID=intval($_GET['frame']);
$siteName = $_GET['siteName'];

$filter = intval($_GET['filter'] ?? 1);
$siteID = intval($_GET['siteID']);
$sourceID = typemap($_GET['sourceID'], 'string');
$domainID = intval($_GET['domainID'] ?? 0);
$statusID = (intval($_GET['statusID'])>0 ? intval($_GET['statusID'])-1 :  -1);
$commission = intval($_GET['commission']);
$free = typemap($_GET['free'], 'string');
$dateType = intval($_GET['dateType'] ?? 0);
$dateFrom = implode('-',array_reverse(explode('/',(($_GET['dateFrom'])))))." 00:00:00";
$dateUntil = implode('-',array_reverse(explode('/',(($_GET['dateUntil'])))))." 23:59:59";


//echo $statusID;
$status = ['cancelled' => 'בוטלה', 'confirmed' => 'אושרה', 'pending' => 'מחכה לאישור', 'error' => 'שגיאה!', 'request' => 'בקשת הזמנה'];
   
//  switch($filter){
//	  case  1: $cond = "`timeFrom` >= '" . date('Y-m-d') . "'"; $class = 'future'; break;
//	  case -1: $cond = "`timeUntil` < '" . date('Y-m-d') . "'"; $class = 'past'; break;
//	  default: $cond = '1'; $class = 'all'; break;
//  }
//
$where = ['1'];

if($siteID) $where[]="`orders`.siteID = ".$siteID."  ";
$dateT[2]='`Torders`.`TimeFrom`';
$dateT[1]='`Torders`.`create_date`';
if($dateType){
	$addSearch[] =  $dateT[$dateType]." >='".$dateFrom."' AND ".$dateT[$dateType]." <='".$dateUntil."'";
}

if($sourceID){
	$addSearch[] =  "`Torders`.sourceID LIKE '".$sourceID."'";
}

if($addSearch){
$joinSearch ="INNER JOIN `orders` AS `Torders` ON (`Torders`.parentOrder = orders.orderID AND ".implode(' AND ', $addSearch).")";
}


if($statusID >=0){
	$where[]= "`orders`.`status` IN ('".$statusID."')  ";
}


$oids = [];

$que = "SELECT `orders`.*, sites.siteName, sites.owners  ,sites.onlineCommission
        FROM `orders` 
		INNER JOIN sites ON (`orders`.siteID = sites.siteID) ".
		$joinSearch
        ."WHERE orders.apiSource = 'spaplus' AND `orders`.`allDay`= 0 AND orders.parentOrder = orders.orderID  AND ". implode(' AND ', $where) . "			   
        ORDER BY `orderID` DESC";
$oids = udb::key_row($que, 'orderID');

if(count($oids)){
    $que = "SELECT `parentOrder`, COUNT(*) AS `cnt`, GROUP_CONCAT(DISTINCT DATE(`timeFrom`) SEPARATOR ',') AS `dates` 
            FROM `orders` 
            WHERE `orderID` <> `parentOrder` AND `parentOrder` IN (" . implode(',', array_keys($oids)) . ")
            GROUP BY `parentOrder`
            ORDER BY NULL";
    
    $treats = udb::key_row($que, 'parentOrder');
    }

/*
if (!$sids)
    exit;

$que = "SELECT p.siteID, MIN(pr.weekday1) AS `price`
        FROM `rooms_prices` AS `pr` INNER JOIN `sites_periods` AS `p` USING(`periodID`)
            INNER JOIN `rooms` ON (rooms.roomID = pr.roomID)
        WHERE p.siteID IN (" . $sids . ") AND p.periodType = 1 AND pr.day = -1 AND rooms.active = 1
        GROUP BY p.siteID
        ORDER BY NULL";
$prices = udb::key_value($que);


$que = "SELECT * FROM `cache_sites_reviews` WHERE `siteID` IN (" . $sids . ") AND categoryID <= 0";     // 0 - count, -1 - rating
$rating = udb::key_value($que, ['siteID', 'categoryID'], 'rating');
*/

$columns = ['ID', 'שם המקום', 'ת. רכישה', 'ת. מימוש', 'שם המזמין', 'טיפולים', 'מייל', 'טלפונים', 'עלות', '%', 'עמלה', 'מקור', 'סטטוס'];

if (defined('CRON_SCRIPT') && isset($csvFileName)){
    $out = fopen($csvFileName, 'w');
}
else {
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: binary");
    header("Content-disposition: attachment; filename=\"bizonline-" . date('Y-m-d') . ".csv\"");

    $out = fopen('php://output', 'w');
}

fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, $columns);

foreach($oids as  $oid => $rxw) {
    $treat = $treats[$oid] ?? [];

    $row = [
        $rxw['orderID'],
        $rxw['siteName'],
        htmlDate(substr($rxw['create_date'], 0, 10)),
        implode('<br />', array_map('htmlDate', explode(',', $treat['dates']))),
        $rxw['customerName'],
        $treat['cnt'],
        $rxw['customerEmail'],
        str_replace('~', '<br />', trim($rxw['customerPhone'] . '~' . $rxw['customerPhone2'], '~')),
        number_format($rxw['price'] + $rxw['extraPrice']),
        ($sourceID=='online'? $rxw['onlineCommission']. "%" : ''),        
        ($sourceID=='online'&& $rxw['onlineCommission']? "₪".number_format(($rxw['price'] + $rxw['extraPrice'])* $rxw['onlineCommission']/100,2) : ""),        
        $rxw['sourceID'],
        ($rxw['status'] ? "פעילה" : ($rxw['autoCancel'] ? "Lead" : "בוטלה"))
    ];

    
    $stat = $rxw['status']?: ($rxw['autoCancel'] ? 2 : 0);
    $total_cnt ++;
    $totalsites[$rxw['siteID']] = 1;
    $totalPrice[$stat] += $rxw['price'] + $rxw['extraPrice'];
    $totalStatus[$stat]++;
	if($sourceID=='online'){
		$totalCommission[$stat]+= ($rxw['price'] + $rxw['extraPrice'])* $rxw['onlineCommission']/100;
	}
    fputcsv($out, $row);
}



$row = [
    $total_cnt,
    count($totalsites).' בתי ספא',
    '',
    '',
    '',
    '',
    '',
    '',
    '₪'.number_format($totalPrice[1]),
    '',
    $totalCommission[1]?"₪".number_format($totalCommission[1],2):'',
    '',
    $totalStatus[1]." פעילות"
];

fputcsv($out, $row);


$row = [
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    "₪".number_format($totalPrice[2]),
    '',
    $totalCommission[2]?"₪".number_format($totalCommission[2],2):'',
    '',
    $totalStatus[2]." לידים"
];

fputcsv($out, $row);

$row = [
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    "₪".number_format($totalPrice[0]),
    '',
    $totalCommission[0]?"₪".number_format($totalCommission[0],2):'',
    '',
    $totalStatus[0].' מבוטלות'
];

fputcsv($out, $row);

fclose($out);
