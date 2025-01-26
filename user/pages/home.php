<?php
$_timer = new BizTimer;

/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
$siteID = intval($_GET['sid']) ?: $_CURRENT_USER->select_site();
if($siteID && !$_CURRENT_USER->has($siteID)){
    echo 'Access denied';
    return;
}

$sids_str = $siteID ?: $_CURRENT_USER->sites(true);
$extrasNames = array();
$dayNames = array(
	'Sun' => 'ראשון',
	'Mon' => 'שני',
	'Tue' => 'שלישי',
	'Wed' => 'רביעי',
	'Thu' => 'חמישי',
	'Fri' => 'שישי',
	'Sat' => 'שבת',
);

//$timeSets['sumY'] = "AND T_orders.`timeFrom` BETWEEN '" . date('Y-01-01') . " 00:00:00' AND '" . date('Y-12-31') . " 23:59:59' ";
$timeSets['sumY'] = "";
$timeSets['sumM'] = "AND T_orders.`timeFrom` BETWEEN '" . date('Y-m-01') . " 00:00:00' AND '" . date('Y-m-t') . " 23:59:59' ";
$timeSets['sumW'] = "AND T_orders.`timeFrom` BETWEEN '" . date('Y-m-d', strtotime('last Sunday')) . " 00:00:00' AND '" . date('Y-m-d', strtotime('this Sunday')) . " 00:00:00' ";

$que = "CREATE TEMPORARY TABLE `tmp_stat_homepage`
            SELECT `parentOrder`, `timeFrom`, `treatmentID` FROM `orders` AS `T_orders`  
            WHERE T_orders.`orderType` = 'order' AND T_orders.`status` = 1 AND T_orders.`allDay` = 0 AND T_orders.`siteID` IN (" . $sids_str . ") AND T_orders.parentOrder > 0 AND T_orders.`timeFrom` BETWEEN '" . date('Y-01-01') . " 00:00:00' AND '" . date('Y-12-31') . " 23:59:59'";
udb::query($que);
$_timer->log();
if ($_CURRENT_USER->is_spa()){
    $sumY = $sumM = $sumW = [];

    if ($_CURRENT_USER->showstats == 1 && !$_CURRENT_USER->userType && ($_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN) || $_CURRENT_USER->suffix()!='member')){
        foreach($timeSets as $key => $timeSet){
            $que = "SELECT COUNT(DISTINCT parentOrder) AS `cnt`, SUM(IF(treatmentID, 1, 0)) AS `cnt_Treatments`, SUM(IF(treatmentID, 0, 1)) AS `cnt_Rooms`
                    FROM `tmp_stat_homepage` AS `T_orders`
                    WHERE 1 " . $timeSet;
            $$key = udb::single_row($que);
$_timer->log();

            $que = "SELECT SUM(orders.price) 
                    FROM `orders` INNER JOIN (
                        SELECT DISTINCT `parentOrder` FROM `tmp_stat_homepage` AS `T_orders` WHERE 1 " . $timeSet . "
                    ) as tmp ON (orders.orderID = tmp.parentOrder)
                    WHERE 1";
            $$key['sum'] = udb::single_value($que);

/*		$que =  "SELECT COUNT(DISTINCT(`orders`.orderID)) AS `cnt`
		FROM `orders` 
		LEFT JOIN orders AS T_orders ON (T_orders.parentOrder = orders.orderID)
		WHERE T_orders.`orderType` = 'order' AND T_orders.`status` = 1 AND T_orders.`allDay` = 0 AND T_orders.`siteID` IN (" . $sids_str . ") AND orders.parentOrder = orders.orderID ".$timeSet;
		$$key['cnt'] = udb::single_value($que);
echo '<!-- gen time - ' . basename(__FILE__) . ':' . __LINE__ . '  ' . (microtime(true) - $_time) . ' ' . $que . '-->' . PHP_EOL;
		$que =  "SELECT COUNT(T_orders.treatmentID) AS `cnt_Treatments`
		FROM `orders` AS T_orders		
		WHERE T_orders.`orderType` = 'order' AND T_orders.`status` = 1 AND T_orders.`allDay` = 0 AND T_orders.`siteID` IN (" . $sids_str . ") AND treatmentID > 0 ".$timeSet;		
		$$key['cnt_Treatments'] = udb::single_value($que);
echo '<!-- gen time - ' . basename(__FILE__) . ':' . __LINE__ . '  ' . (microtime(true) - $_time) . ' ' . $que . '-->' . PHP_EOL;
		$que =  "SELECT COUNT(T_orders.orderID) AS `cnt_Rooms`
		FROM `orders` AS T_orders
		INNER JOIN `orderUnits` ON(T_orders.`orderID` = `orderUnits`.`orderID`)
		WHERE T_orders.`orderType` = 'order' AND T_orders.`status` = 1 AND T_orders.`allDay` = 0 AND T_orders.`siteID` IN (" . $sids_str . ")  AND treatmentID = 0 ".$timeSet;
		$$key['cnt_Rooms'] = udb::single_value($que);
echo '<!-- gen time - ' . basename(__FILE__) . ':' . __LINE__ . '  ' . (microtime(true) - $_time) . ' ' . $que . '-->' . PHP_EOL;
		$que =  "SELECT orders.`price` AS `sum` 
		FROM `orders` 
		LEFT JOIN orders AS T_orders ON (T_orders.parentOrder = orders.orderID)
		WHERE T_orders.`orderType` = 'order' AND T_orders.`status` = 1 AND T_orders.`allDay` = 0 AND T_orders.`siteID` IN (" . $sids_str . ") AND orders.parentOrder = orders.orderID ".$timeSet."GROUP BY orders.orderID";
echo '<!-- gen time - ' . basename(__FILE__) . ':' . __LINE__ . '  ' . (microtime(true) - $_time) . ' ' . $que . '-->' . PHP_EOL;
		$sumtotals = udb::full_list($que);		
		foreach($sumtotals as $sumt)
			$$key['sum']+=  $sumt['sum'];*/

	    }
    }

//*************** not relevant for spa sites ***********************
//	$que = "SELECT COUNT(create_date) AS upCount, SUM(price) AS upSum
//		FROM `orders`
//		WHERE `orderType` = 'order' AND approved=0  AND allDay=0   AND status=1 AND siteID IN (" . $sids_str .") AND `timeFrom` >= NOW()
//		ORDER BY create_date DESC";
//	$unapproved = udb::single_row($que);

    $_timer->log();
}else{ // OLD FOR BACKUP
	$sumY = udb::single_row("SELECT COUNT(*) AS `cnt`, SUM(`price`) AS `sum` FROM `orders` WHERE `orderType` = 'order' AND `status` = 1 AND `allDay` = 0 AND `timeFrom` BETWEEN '" . date('Y-01-01') . " 00:00:00' AND '" . date('Y-12-31') . " 23:59:59' AND `siteID` IN (" . $sids_str . ")");
	$sumM = udb::single_row("SELECT COUNT(*) AS `cnt`, SUM(`price`) AS `sum` FROM `orders` WHERE `orderType` = 'order' AND `status` = 1 AND `allDay` = 0 AND `timeFrom` BETWEEN '" . date('Y-m-01') . "  00:00:00' AND '" . date('Y-m-t') . " 23:59:59' AND `siteID` IN (" . $sids_str . ")");
	$sumW = udb::single_row("SELECT COUNT(*) AS `cnt`, SUM(`price`) AS `sum` FROM `orders` WHERE `orderType` = 'order' AND `status` = 1 AND `allDay` = 0 AND `timeFrom` BETWEEN '" . date('Y-m-d', strtotime('last Saturday')) . "  23:59:59' AND '" . date('Y-m-d', strtotime('this Saturday')) . "  23:59:59' AND `siteID` IN (" . $sids_str . ")");

	$que = "SELECT COUNT(create_date) AS upCount, SUM(price) AS upSum
		FROM `orders` 
		WHERE `orderType` = 'order' AND approved=0  AND allDay=0   AND status=1 AND siteID IN (" . $sids_str .") AND `timeFrom` >= NOW()
		ORDER BY create_date DESC";
	$unapproved = udb::single_row($que);
}

$_timer->log();

if ($_CURRENT_USER->is_spa()){
	if(!$_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN) && $_CURRENT_USER->suffix()=='member'){
		$user_where = " AND T_orders.therapistID = " . $_CURRENT_USER->id();
		$user_where2 = " AND orders.therapistID = " . $_CURRENT_USER->id();
		if($siteData['limit_metaplim']){
			$until_date = date('Y-m-d 23:59:59',strtotime("+".($siteData['limit_metaplim']-1)." days"));			
			$user_where.= " AND T_orders.`timeFrom` <='".$until_date."'";;
			$user_where2.= " AND orders.`timeFrom` <='".$until_date."'";;
		}
	}
	else
        $user_where = $user_where2 = '';

	$daily_ex = array();
	$daily_print = array();

	$que = "SELECT e.extraID, e.extraName FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) 
                WHERE s.siteID IN (" . $sids_str . ") AND s.included = 0 AND s.active = 1 ORDER BY e.showOrder";
	$extrasNames = udb::key_value($que);

	$que = "SELECT e.extraID, e.extraName FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) 
                WHERE s.siteID IN (" . $sids_str . ") AND s.included = 0 AND s.active = 1 AND s.voucherprint = 1 ORDER BY e.showOrder";
	$extrasPrintVouchter = udb::key_value($que);

	$que = "SELECT `o`.orderID, `o`.extras,	 count(T_orders.orderID) AS treat_count,
			CAST(`T_orders`.`timeFrom` AS DATE) AS daydate
			FROM orders AS `o`
			INNER JOIN orders AS T_orders ON (T_orders.parentOrder = o.orderID)
			WHERE o.siteID IN (" . $sids_str . ") AND o.status=1  AND T_orders.`timeFrom` >= '".date("Y-m-d 00:00:00")."' AND T_orders.`timeFrom` <= '".date("Y-m-d 00:00:00",strtotime("+4 days"))."' 
			GROUP BY o.orderID
			ORDER BY daydate";
    $oextras = udb::full_list($que);
	//print_r($oextras);
	foreach($oextras as $extra){
		if($extra['extras']){
			$exx = json_decode($extra['extras'], true) ;		
			if(is_array($exx)){
				foreach($exx['extras']  as $key => $ex){	
					if(intval($ex['extraID']) == 0)						
						$ex['extraID'] = $key;

					if(array_key_exists($ex['extraID'],$extrasNames)){
						$daily_ex[$extra['daydate']][$ex['extraID']] += $ex['count'];
					}
					if(array_key_exists($ex['extraID'],$extrasPrintVouchter)){
						$daily_print[$extra['daydate']][$ex['extraID']] += $ex['count'];
					}
				}
			}		
		}	
		$daily_treats[$extra['daydate']]['treat_count'] += $extra['treat_count'];
	}

$_timer->log();

	$nextLimitTop = 16;
	$nextLimitBtm = 8;
    $que = "SELECT `sites`.`siteName`,`orders`.*
                , GROUP_CONCAT(rooms_units.unitName SEPARATOR ', ') AS `unitNames`
                , MIN(IF(T_orders.timeFrom = '00:00:00', NULL, T_orders.timeFrom)) AS `timeFrom`
                , MAX(T_orders.timeUntil) AS `timeUntil`
                , GROUP_CONCAT(treatments.treatmentName SEPARATOR ', ') AS `treatmentsNames`
                , GROUP_CONCAT(T_orders.treatmentLen SEPARATOR ', ') AS `treatmentsLen`
            FROM (
                    SELECT `orders`.*
                    FROM orders LEFT JOIN `orders` AS `T_orders` ON (orders.orderID = T_orders.parentOrder AND T_orders.orderID <> orders.orderID)
                    WHERE orders.orderType = 'order' AND orders.status=1 AND orders.allDay=0 AND orders.siteID IN (" . $sids_str . ") AND orders.parentOrder = orders.orderID ".$user_where."
                    GROUP BY orders.orderID
                    ORDER BY orders.orderID DESC 
                    LIMIT 6
                ) AS `orders` 			
                LEFT JOIN orders AS T_orders ON (T_orders.parentOrder = orders.orderID AND T_orders.timeFrom>0)
                LEFT JOIN `orderUnits` ON(T_orders.`orderID` = `orderUnits`.`orderID`)
                LEFT JOIN `rooms_units` USING(`unitID`)
                LEFT JOIN `sites` ON (orders.siteID = sites.siteID)
                LEFT JOIN treatments ON (T_orders.treatmentID = treatments.treatmentID)
            WHERE 1 ".$user_where."
            GROUP BY orders.parentOrder
            ORDER BY orders.create_date DESC";
    $last = udb::full_list($que);
$_timer->log();
    $que = "SELECT `sites`.`siteName`,`orders`.*
			, GROUP_CONCAT(rooms_units.unitName SEPARATOR ', ') AS `unitNames`
            , MIN(IF(T_orders.timeFrom = '00:00:00', NULL, T_orders.timeFrom)) AS `timeFrom`
			, MAX(T_orders.timeUntil) AS `timeUntil`
			, GROUP_CONCAT(treatments.treatmentName SEPARATOR ', ') AS `treatmentsNames`
			, GROUP_CONCAT(orders.treatmentLen SEPARATOR ', ') AS `treatmentsLen`
            FROM `orders` 			
			LEFT JOIN orders AS T_orders ON (T_orders.parentOrder = orders.orderID)
            LEFT JOIN `orderUnits` ON(T_orders.`orderID` = `orderUnits`.`orderID`)
            LEFT JOIN `rooms_units` USING(`unitID`)
            LEFT JOIN `sites` ON (orders.siteID = sites.siteID)
			LEFT JOIN treatments ON (T_orders.treatmentID = treatments.treatmentID)
            WHERE orders.orderType = 'order' AND orders.status=1 AND orders.allDay=0 AND orders.siteID IN (" . $sids_str . ") AND orders.parentOrder = orders.orderID AND T_orders.`timeFrom` > NOW() ".$user_where."
            GROUP BY orders.parentOrder
            ORDER BY orders.`timeFrom`
            LIMIT 8";
	
	$nextBtm = udb::full_list($que);
$_timer->log();
	$que = "SELECT sites.siteName, `orders`.*, 
			GROUP_CONCAT(rooms_units.unitName SEPARATOR ', ') AS `unitNames`, 
			treatments.treatmentName, therapists.siteName AS therapistName,health_declare.declareID  AS helthDelare, SUM(`health_declare`.negative) AS h_negatives,
			`p`.price AS priceTotal
            FROM `orders` 
            LEFT JOIN `orderUnits` USING(`orderID`)
            LEFT JOIN `rooms_units` USING(`unitID`)
			LEFT JOIN health_declare ON (orders.orderID = health_declare.orderID)
            LEFT JOIN `sites` ON (orders.siteID = sites.siteID)
			LEFT JOIN treatments ON (orders.treatmentID = treatments.treatmentID)
			LEFT JOIN therapists ON (orders.therapistID = therapists.therapistID)
			LEFT JOIN orders AS `p` ON (orders.parentOrder = `p`.orderID)
            WHERE p.status=1 AND p.allDay=0 AND orders.siteID IN (" . $sids_str . ") AND orders.`timeFrom` > '".date('Y-m-d 00:00:00')/*was NOW()*/."' ".$user_where2."
            GROUP BY orders.orderID
            ORDER BY orders.`timeFrom`
            LIMIT 200";
    $next = udb::full_list($que);
$_timer->log();
} else {	
	$nextLimitTop = 8;
	$nextLimitBtm = 8;
    $que = "SELECT `sites`.`siteName`,`orders`.*, GROUP_CONCAT(rooms_units.unitName SEPARATOR ', ') AS `unitNames`
    FROM `orders` 
    LEFT JOIN `orderUnits` USING(`orderID`)
    LEFT JOIN `rooms_units` USING(`unitID`)
    LEFT JOIN `sites` ON (orders.siteID = sites.siteID)
    WHERE orderType = 'order' AND status=1 AND allDay=0 AND orders.siteID IN (" . $sids_str . ") AND orders.parentOrder = 0
    GROUP BY orders.orderID
    ORDER BY create_date DESC
    LIMIT 6";
    $last = udb::full_list($que);

    $que = "SELECT sites.siteName, `orders`.*, GROUP_CONCAT(rooms_units.unitName SEPARATOR ', ') AS `unitNames`
    FROM `orders` 
    INNER JOIN `orderUnits` USING(`orderID`)
    INNER JOIN `rooms_units` USING(`unitID`)
    LEFT JOIN `sites` ON (orders.siteID = sites.siteID)	
    WHERE status=1 AND allDay=0 AND orders.siteID IN (" . $sids_str . ") AND `timeFrom` > NOW() AND orders.parentOrder = 0
    GROUP BY orders.orderID
    ORDER BY `timeFrom`
    LIMIT 100";
    $next = udb::full_list($que);
$_timer->log();
}

//print_r($next);
$today = urlencode(date('d/m/Y'));

$dateW = date('d/m/Y', strtotime('-1 week'));
$dateM = date('d/m/Y', strtotime('-1 month'));
$dateY = date('d/m/Y', strtotime('-1 year'));
?>
<link href="assets/css/style_ctrl.php?dir=<?=$dir?>&fileName=home&v=<?=rand()?>" rel="stylesheet">
<section class="home">
	<div class="top-buttons">
<?php
    if ($_CURRENT_USER->is_spa()) {

        if($_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN) || $_CURRENT_USER->suffix()!='member'){
?>
        <button class="create-order" data-pagetype="order" onclick="openNewSpa(this)"><span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 33 30" width="33" height="30"><g id="דסקטופ"><g id="Group 4"><g id="Group 3"><g id="Group 2 copy 3"><path id="Shape 5" class="shp0" d="M28.45 20.68C28.18 20.86 27.89 21.02 27.6 21.17L27.9 22.55L19.9 24.75L19.68 24.82L19.45 24.88L8.75 27.83L8.05 24.55L8 24.32L7.95 24.09L7.89 23.78L7.84 23.55L7.79 23.33L4.73 8.99L6.79 8.48C6.87 9.01 6.84 9.56 6.73 10.03C6.6 10.61 7.5 10.86 7.64 10.27C7.78 9.66 7.81 8.95 7.69 8.26L10.55 7.57C10.63 8.09 10.6 8.63 10.5 9.1C10.36 9.68 11.27 9.93 11.4 9.34C11.54 8.73 11.57 8.03 11.46 7.35L14.32 6.65C14.39 7.17 14.36 7.71 14.26 8.17C14.12 8.75 15.03 9 15.16 8.41C15.31 7.81 15.33 7.11 15.22 6.43L18.08 5.74C18.15 6.25 18.13 6.78 18.02 7.24C17.88 7.82 18.79 8.07 18.93 7.48C19.07 6.88 19.09 6.19 18.99 5.52L21.39 4.93L21.64 4.88L21.85 4.82C21.85 4.87 21.86 4.92 21.86 4.97C21.91 5.43 21.88 5.9 21.78 6.31C21.77 6.34 21.78 6.37 21.78 6.4C22.09 6.31 22.41 6.24 22.74 6.19C22.81 5.68 22.83 5.14 22.75 4.6L23.97 4.31L24.35 6.1C24.68 6.11 25.01 6.15 25.33 6.2L24.88 4.11L24.68 3.18L23.74 3.4L22.52 3.7C22.31 3.16 21.98 2.68 21.51 2.34C21.06 2.02 20.52 1.99 20.09 2.36C19.59 2.78 19.54 3.59 19.56 4.24C19.56 4.3 19.56 4.36 19.56 4.42L18.75 4.62C18.73 4.55 18.69 4.5 18.67 4.44C18.46 3.98 18.16 3.57 17.75 3.27C17.3 2.95 16.75 2.92 16.32 3.29C15.86 3.69 15.79 4.43 15.79 5.06C15.79 5.14 15.79 5.22 15.8 5.3C15.8 5.31 15.8 5.32 15.8 5.34L14.98 5.53C14.98 5.52 14.97 5.5 14.96 5.48C14.93 5.41 14.89 5.34 14.86 5.27C14.65 4.85 14.37 4.48 13.99 4.2C13.54 3.88 12.99 3.85 12.56 4.22C12.12 4.59 12.03 5.28 12.03 5.88C12.03 5.96 12.03 6.04 12.03 6.12C12.03 6.16 12.03 6.21 12.04 6.25L11.22 6.45C11.2 6.4 11.17 6.36 11.15 6.31C11.12 6.24 11.08 6.16 11.04 6.09C10.84 5.72 10.58 5.38 10.22 5.13C9.77 4.81 9.23 4.78 8.8 5.15C8.39 5.5 8.29 6.12 8.27 6.7C8.27 6.78 8.27 6.86 8.27 6.94C8.27 7.01 8.27 7.09 8.27 7.17L8.06 7.22L7.44 7.35C7.41 7.28 7.37 7.21 7.34 7.14C7.3 7.07 7.27 6.99 7.23 6.92C7.04 6.59 6.78 6.29 6.46 6.06C6.01 5.74 5.47 5.71 5.04 6.08C4.65 6.41 4.54 6.97 4.51 7.51C4.51 7.59 4.51 7.67 4.51 7.75C4.51 7.83 4.51 7.91 4.51 7.99L2.69 8.39L1 25.29L6.88 23.59L6.93 23.81L6.98 24.04L7.05 24.37L2.29 25.86L1.02 26.26L2.26 26.74L7.93 28.94L8.03 28.98L8.03 28.99L8.04 28.98L8.08 29L8.23 28.96L19.7 25.78L20.15 25.66L20.15 25.65L28.15 23.45L29 23.21L28.81 22.36L28.45 20.68ZM20.72 3.05C20.76 3 20.76 3 20.78 3.01C20.9 3.03 21 3.11 21.12 3.23C21.29 3.4 21.43 3.6 21.54 3.81C21.56 3.85 21.58 3.89 21.6 3.93L20.49 4.19C20.49 4.14 20.49 4.09 20.49 4.04C20.49 3.67 20.53 3.26 20.72 3.05ZM16.98 3.98C17.02 3.93 17.01 3.94 17.03 3.94C17.15 3.96 17.25 4.04 17.37 4.16C17.51 4.3 17.62 4.47 17.72 4.64C17.76 4.71 17.8 4.77 17.83 4.84L17.28 4.99L16.83 5.11L16.83 5.1C16.83 5.02 16.78 4.94 16.78 4.86C16.79 4.52 16.81 4.17 16.98 3.98ZM12.97 5.67C12.99 5.37 13.04 5.08 13.19 4.91C13.24 4.86 13.24 4.87 13.26 4.87C13.38 4.89 13.48 4.97 13.6 5.09C13.72 5.2 13.81 5.33 13.9 5.47C13.95 5.54 13.99 5.61 14.02 5.68C14.04 5.71 14.05 5.73 14.06 5.76L12.97 6.02C12.97 5.99 12.97 5.95 12.97 5.91C12.97 5.83 12.97 5.75 12.97 5.67ZM9.44 5.84C9.49 5.79 9.48 5.79 9.5 5.8C9.62 5.82 9.72 5.9 9.84 6.02C9.93 6.11 10.01 6.2 10.08 6.3C10.13 6.37 10.16 6.44 10.2 6.51C10.23 6.57 10.27 6.62 10.3 6.67C10.3 6.68 10.3 6.68 10.3 6.69L9.3 6.94C9.3 6.87 9.25 6.8 9.25 6.73C9.26 6.65 9.24 6.57 9.24 6.49C9.27 6.23 9.31 5.99 9.44 5.84ZM5.45 7.55C5.45 7.46 5.46 7.38 5.47 7.3C5.5 7.09 5.56 6.9 5.67 6.77C5.71 6.72 5.72 6.73 5.74 6.73C5.86 6.75 5.96 6.83 6.08 6.95C6.13 7.01 6.18 7.07 6.23 7.14C6.29 7.2 6.34 7.27 6.38 7.34C6.43 7.41 6.47 7.48 6.51 7.55L5.44 7.79C5.44 7.71 5.44 7.63 5.45 7.55ZM14.83 15.15L16.04 14.86C16.01 14.59 16 14.33 16 14.05C16 13.27 16.12 12.51 16.33 11.79L14.15 12.31L14.83 15.15ZM15.74 18.57L17.19 18.23C16.68 17.4 16.31 16.47 16.13 15.48L15.05 15.73L15.74 18.57ZM16.41 21.07L16.47 21.29L16.49 21.41L16.55 21.64L16.6 21.86L16.64 22L18.54 21.55L18.76 21.49L18.99 21.44L19.51 21.32L19.31 20.5C18.64 20.02 18.06 19.44 17.57 18.78L15.95 19.16L16.36 20.84L16.41 21.07ZM12.97 22.06L13.02 22.29L13.07 22.48L13.13 22.71L13.16 22.86L14.25 22.6L16.03 22.18L16 22.05L15.95 21.83L15.89 21.6L15.86 21.47L15.81 21.24L15.75 21.02L15.34 19.34L12.48 20.02L12.91 21.84L12.97 22.06ZM25 7.08C21.13 7.08 18 10.2 18 14.05C18 17.9 21.13 21.03 25 21.03C28.87 21.03 32 17.9 32 14.05C32 10.2 28.87 7.08 25 7.08ZM28 15.05L26 15.05L26 17.04L24 17.04L24 15.05L22 15.05L22 13.06L24 13.06L24 11.06L26 11.06L26 13.06L28 13.06L28 15.05ZM9.53 23.06L9.59 23.29L9.65 23.56L9.69 23.72L10.57 23.51L12.56 23.04L12.53 22.9L12.47 22.67L12.42 22.47L12.37 22.24L12.31 22.01L11.87 20.2L9 20.88L9.48 22.84L9.53 23.06ZM12.26 19.43L15.13 18.75L14.44 15.91L11.57 16.6L12.26 19.43ZM8.1 17.46L8.79 20.29L11.66 19.61L10.97 16.78L8.1 17.46Z" /></g></g></g></g></svg></span>יצירת הזמנה</button>
        <button class="tfusa" onclick="window.location.href='?page=absolute_calendar&type=1&viewtype=2'"><span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 31" width="30" height="31"><path class="shp0" d="M29 23.2L28.8 22.3 24.9 4 24.7 3.1 23.7 3.3 22.5 3.6C22.3 3.1 22 2.6 21.5 2.3 21.1 1.9 20.5 1.9 20.1 2.3 19.6 2.7 19.5 3.5 19.6 4.2 19.6 4.2 19.6 4.3 19.6 4.3L18.8 4.5C18.7 4.5 18.7 4.4 18.7 4.4 18.5 3.9 18.2 3.5 17.8 3.2 17.3 2.9 16.8 2.8 16.3 3.2 15.9 3.6 15.8 4.4 15.8 5 15.8 5.1 15.8 5.1 15.8 5.2 15.8 5.2 15.8 5.2 15.8 5.3L15 5.5C15 5.4 15 5.4 15 5.4 14.9 5.3 14.9 5.3 14.9 5.2 14.7 4.8 14.4 4.4 14 4.1 13.5 3.8 13 3.8 12.6 4.1 12.1 4.5 12 5.2 12 5.8 12 5.9 12 6 12 6 12 6.1 12 6.1 12 6.2L11.2 6.4C11.2 6.3 11.2 6.3 11.2 6.2 11.1 6.2 11.1 6.1 11.1 6 10.8 5.6 10.6 5.3 10.2 5.1 9.8 4.7 9.2 4.7 8.8 5.1 8.4 5.4 8.3 6 8.3 6.6 8.3 6.7 8.3 6.8 8.3 6.9 8.3 6.9 8.3 7 8.3 7.1L8.1 7.1 7.4 7.3C7.4 7.2 7.4 7.1 7.3 7.1 7.3 7 7.3 6.9 7.2 6.8 7 6.5 6.8 6.2 6.5 6 6 5.7 5.5 5.6 5 6 4.7 6.3 4.5 6.9 4.5 7.4 4.5 7.5 4.5 7.6 4.5 7.7 4.5 7.8 4.5 7.8 4.5 7.9L2.7 8.3 1 25.3 6.9 23.6 6.9 23.8 7 24 7.1 24.4 2.3 25.9 1 26.3 2.3 26.7 7.9 28.9 8 29 8 29 8 29 8.1 29 8.2 29 19.7 25.8 20.2 25.7 20.2 25.6 28.2 23.4 29 23.2ZM20.7 3C20.8 2.9 20.8 2.9 20.8 2.9 20.9 2.9 21 3 21.1 3.1 21.3 3.3 21.4 3.5 21.5 3.7 21.6 3.8 21.6 3.8 21.6 3.8L20.5 4.1C20.5 4.1 20.5 4 20.5 4 20.5 3.6 20.5 3.2 20.7 3ZM17 3.9C17 3.8 17 3.9 17 3.9 17.2 3.9 17.3 4 17.4 4.1 17.5 4.2 17.6 4.4 17.7 4.6 17.8 4.6 17.8 4.7 17.8 4.8L17.3 4.9 16.8 5C16.8 5 16.8 5 16.8 5 16.8 4.9 16.8 4.9 16.8 4.8 16.8 4.4 16.8 4.1 17 3.9ZM13 5.6C13 5.3 13.1 5 13.2 4.8 13.2 4.8 13.2 4.8 13.3 4.8 13.4 4.8 13.5 4.9 13.6 5 13.7 5.1 13.8 5.3 13.9 5.4 14 5.5 14 5.5 14 5.6 14 5.6 14.1 5.7 14.1 5.7L13 5.9C13 5.9 13 5.9 13 5.8 13 5.8 13 5.7 13 5.6ZM9.5 5.8C9.5 5.7 9.5 5.7 9.5 5.7 9.6 5.7 9.7 5.8 9.8 5.9 9.9 6 10 6.1 10.1 6.2 10.1 6.3 10.2 6.4 10.2 6.4 10.2 6.5 10.3 6.5 10.3 6.6 10.3 6.6 10.3 6.6 10.3 6.6L9.3 6.9C9.3 6.8 9.3 6.7 9.3 6.7 9.3 6.6 9.2 6.5 9.3 6.4 9.3 6.2 9.3 5.9 9.5 5.8ZM5.5 7.5C5.5 7.4 5.5 7.3 5.5 7.2 5.5 7 5.6 6.8 5.7 6.7 5.7 6.6 5.7 6.7 5.7 6.7 5.9 6.7 6 6.8 6.1 6.9 6.1 6.9 6.2 7 6.2 7.1 6.3 7.1 6.3 7.2 6.4 7.3 6.4 7.3 6.5 7.4 6.5 7.5L5.5 7.7C5.5 7.6 5.5 7.6 5.5 7.5ZM19.9 24.7L19.7 24.8 19.5 24.9 8.8 27.8 8.1 24.5 8 24.3 8 24.1 7.9 23.8 7.8 23.5 7.8 23.3 4.7 8.9 6.8 8.4C6.9 8.9 6.9 9.5 6.7 10 6.6 10.5 7.5 10.8 7.6 10.2 7.8 9.6 7.8 8.9 7.7 8.2L10.6 7.5C10.6 8 10.6 8.6 10.5 9 10.4 9.6 11.3 9.9 11.4 9.3 11.6 8.7 11.6 8 11.5 7.3L14.3 6.6C14.4 7.1 14.4 7.6 14.3 8.1 14.1 8.7 15 8.9 15.2 8.3 15.3 7.7 15.3 7 15.2 6.4L18.1 5.7C18.2 6.2 18.1 6.7 18 7.2 17.9 7.8 18.8 8 18.9 7.4 19.1 6.8 19.1 6.1 19 5.4L21.4 4.9 21.6 4.8 21.9 4.7C21.9 4.8 21.9 4.8 21.9 4.9 21.9 5.4 21.9 5.8 21.8 6.2 21.7 6.8 22.6 7.1 22.7 6.5 22.8 5.9 22.9 5.2 22.8 4.5L24 4.2 27.9 22.5 19.9 24.7ZM11.7 19.6L8.8 20.3 8.1 17.4 11 16.7 11.7 19.6ZM12.3 19.4L11.6 16.6 14.5 15.9 15.1 18.7 12.3 19.4ZM15.1 15.7L17.9 15 18.6 17.9 15.7 18.5 15.1 15.7ZM21.4 14.1L22.1 17 20.8 17.3 20.6 17.4 20.3 17.4 19.2 17.7 18.5 14.8 20.6 14.3 20.8 14.3 21.1 14.2 21.4 14.1ZM22 14L24.9 13.3 25.6 16.1 22.7 16.8 22 14ZM14.8 15.1L14.2 12.3 17 11.6 17.7 14.4 14.8 15.1ZM18.3 14.2L17.6 11.4 20.5 10.7 20.8 11.9 21 12.7 21.1 13.4 21.2 13.6 21.1 13.6 20.9 13.6 20.6 13.7 18.3 14.2ZM24 9.8L24.7 12.7 21.8 13.4 21.3 11.4 21.1 10.7 21.1 10.5 21.2 10.5 21.4 10.5 24 9.8ZM12.5 22.9L12.6 23 10.6 23.5 9.7 23.7 9.7 23.5 9.6 23.3 9.5 23 9.5 22.8 9 20.9 11.9 20.2 12.3 22 12.4 22.2 12.4 22.4 12.5 22.7 12.5 22.9ZM16 22L16 22.2 14.3 22.6 13.2 22.8 13.1 22.7 13.1 22.5 13 22.3 13 22 12.9 21.8 12.5 20 15.4 19.3 15.8 21 15.8 21.2 15.9 21.4 15.9 21.6 16 21.8 16 22ZM19.3 20.4L19.5 21.3 19 21.4 18.8 21.5 18.5 21.5 16.6 22 16.6 21.8 16.6 21.6 16.5 21.4 16.5 21.3 16.4 21 16.4 20.8 16 19.1 18.8 18.4 19.2 20 19.3 20.2 19.3 20.4Z"></path></svg></span> יומן תפוסה</button>
        <button class="tfusa2" id="today-luz"><span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 31" width="30" height="31"><path class="shp0" d="M29 23.2L28.8 22.3 24.9 4 24.7 3.1 23.7 3.3 22.5 3.6C22.3 3.1 22 2.6 21.5 2.3 21.1 1.9 20.5 1.9 20.1 2.3 19.6 2.7 19.5 3.5 19.6 4.2 19.6 4.2 19.6 4.3 19.6 4.3L18.8 4.5C18.7 4.5 18.7 4.4 18.7 4.4 18.5 3.9 18.2 3.5 17.8 3.2 17.3 2.9 16.8 2.8 16.3 3.2 15.9 3.6 15.8 4.4 15.8 5 15.8 5.1 15.8 5.1 15.8 5.2 15.8 5.2 15.8 5.2 15.8 5.3L15 5.5C15 5.4 15 5.4 15 5.4 14.9 5.3 14.9 5.3 14.9 5.2 14.7 4.8 14.4 4.4 14 4.1 13.5 3.8 13 3.8 12.6 4.1 12.1 4.5 12 5.2 12 5.8 12 5.9 12 6 12 6 12 6.1 12 6.1 12 6.2L11.2 6.4C11.2 6.3 11.2 6.3 11.2 6.2 11.1 6.2 11.1 6.1 11.1 6 10.8 5.6 10.6 5.3 10.2 5.1 9.8 4.7 9.2 4.7 8.8 5.1 8.4 5.4 8.3 6 8.3 6.6 8.3 6.7 8.3 6.8 8.3 6.9 8.3 6.9 8.3 7 8.3 7.1L8.1 7.1 7.4 7.3C7.4 7.2 7.4 7.1 7.3 7.1 7.3 7 7.3 6.9 7.2 6.8 7 6.5 6.8 6.2 6.5 6 6 5.7 5.5 5.6 5 6 4.7 6.3 4.5 6.9 4.5 7.4 4.5 7.5 4.5 7.6 4.5 7.7 4.5 7.8 4.5 7.8 4.5 7.9L2.7 8.3 1 25.3 6.9 23.6 6.9 23.8 7 24 7.1 24.4 2.3 25.9 1 26.3 2.3 26.7 7.9 28.9 8 29 8 29 8 29 8.1 29 8.2 29 19.7 25.8 20.2 25.7 20.2 25.6 28.2 23.4 29 23.2ZM20.7 3C20.8 2.9 20.8 2.9 20.8 2.9 20.9 2.9 21 3 21.1 3.1 21.3 3.3 21.4 3.5 21.5 3.7 21.6 3.8 21.6 3.8 21.6 3.8L20.5 4.1C20.5 4.1 20.5 4 20.5 4 20.5 3.6 20.5 3.2 20.7 3ZM17 3.9C17 3.8 17 3.9 17 3.9 17.2 3.9 17.3 4 17.4 4.1 17.5 4.2 17.6 4.4 17.7 4.6 17.8 4.6 17.8 4.7 17.8 4.8L17.3 4.9 16.8 5C16.8 5 16.8 5 16.8 5 16.8 4.9 16.8 4.9 16.8 4.8 16.8 4.4 16.8 4.1 17 3.9ZM13 5.6C13 5.3 13.1 5 13.2 4.8 13.2 4.8 13.2 4.8 13.3 4.8 13.4 4.8 13.5 4.9 13.6 5 13.7 5.1 13.8 5.3 13.9 5.4 14 5.5 14 5.5 14 5.6 14 5.6 14.1 5.7 14.1 5.7L13 5.9C13 5.9 13 5.9 13 5.8 13 5.8 13 5.7 13 5.6ZM9.5 5.8C9.5 5.7 9.5 5.7 9.5 5.7 9.6 5.7 9.7 5.8 9.8 5.9 9.9 6 10 6.1 10.1 6.2 10.1 6.3 10.2 6.4 10.2 6.4 10.2 6.5 10.3 6.5 10.3 6.6 10.3 6.6 10.3 6.6 10.3 6.6L9.3 6.9C9.3 6.8 9.3 6.7 9.3 6.7 9.3 6.6 9.2 6.5 9.3 6.4 9.3 6.2 9.3 5.9 9.5 5.8ZM5.5 7.5C5.5 7.4 5.5 7.3 5.5 7.2 5.5 7 5.6 6.8 5.7 6.7 5.7 6.6 5.7 6.7 5.7 6.7 5.9 6.7 6 6.8 6.1 6.9 6.1 6.9 6.2 7 6.2 7.1 6.3 7.1 6.3 7.2 6.4 7.3 6.4 7.3 6.5 7.4 6.5 7.5L5.5 7.7C5.5 7.6 5.5 7.6 5.5 7.5ZM19.9 24.7L19.7 24.8 19.5 24.9 8.8 27.8 8.1 24.5 8 24.3 8 24.1 7.9 23.8 7.8 23.5 7.8 23.3 4.7 8.9 6.8 8.4C6.9 8.9 6.9 9.5 6.7 10 6.6 10.5 7.5 10.8 7.6 10.2 7.8 9.6 7.8 8.9 7.7 8.2L10.6 7.5C10.6 8 10.6 8.6 10.5 9 10.4 9.6 11.3 9.9 11.4 9.3 11.6 8.7 11.6 8 11.5 7.3L14.3 6.6C14.4 7.1 14.4 7.6 14.3 8.1 14.1 8.7 15 8.9 15.2 8.3 15.3 7.7 15.3 7 15.2 6.4L18.1 5.7C18.2 6.2 18.1 6.7 18 7.2 17.9 7.8 18.8 8 18.9 7.4 19.1 6.8 19.1 6.1 19 5.4L21.4 4.9 21.6 4.8 21.9 4.7C21.9 4.8 21.9 4.8 21.9 4.9 21.9 5.4 21.9 5.8 21.8 6.2 21.7 6.8 22.6 7.1 22.7 6.5 22.8 5.9 22.9 5.2 22.8 4.5L24 4.2 27.9 22.5 19.9 24.7ZM11.7 19.6L8.8 20.3 8.1 17.4 11 16.7 11.7 19.6ZM12.3 19.4L11.6 16.6 14.5 15.9 15.1 18.7 12.3 19.4ZM15.1 15.7L17.9 15 18.6 17.9 15.7 18.5 15.1 15.7ZM21.4 14.1L22.1 17 20.8 17.3 20.6 17.4 20.3 17.4 19.2 17.7 18.5 14.8 20.6 14.3 20.8 14.3 21.1 14.2 21.4 14.1ZM22 14L24.9 13.3 25.6 16.1 22.7 16.8 22 14ZM14.8 15.1L14.2 12.3 17 11.6 17.7 14.4 14.8 15.1ZM18.3 14.2L17.6 11.4 20.5 10.7 20.8 11.9 21 12.7 21.1 13.4 21.2 13.6 21.1 13.6 20.9 13.6 20.6 13.7 18.3 14.2ZM24 9.8L24.7 12.7 21.8 13.4 21.3 11.4 21.1 10.7 21.1 10.5 21.2 10.5 21.4 10.5 24 9.8ZM12.5 22.9L12.6 23 10.6 23.5 9.7 23.7 9.7 23.5 9.6 23.3 9.5 23 9.5 22.8 9 20.9 11.9 20.2 12.3 22 12.4 22.2 12.4 22.4 12.5 22.7 12.5 22.9ZM16 22L16 22.2 14.3 22.6 13.2 22.8 13.1 22.7 13.1 22.5 13 22.3 13 22 12.9 21.8 12.5 20 15.4 19.3 15.8 21 15.8 21.2 15.9 21.4 15.9 21.6 16 21.8 16 22ZM19.3 20.4L19.5 21.3 19 21.4 18.8 21.5 18.5 21.5 16.6 22 16.6 21.8 16.6 21.6 16.5 21.4 16.5 21.3 16.4 21 16.4 20.8 16 19.1 18.8 18.4 19.2 20 19.3 20.2 19.3 20.4Z"></path></svg></span> הדפסת לו"ז יומי</button>
<?php
        } else {
?>
        <button class="tfusa" onclick="window.location.href='?page=absolute_calendar&type=1&viewtype=2'"><span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 31" width="30" height="31"><path class="shp0" d="M29 23.2L28.8 22.3 24.9 4 24.7 3.1 23.7 3.3 22.5 3.6C22.3 3.1 22 2.6 21.5 2.3 21.1 1.9 20.5 1.9 20.1 2.3 19.6 2.7 19.5 3.5 19.6 4.2 19.6 4.2 19.6 4.3 19.6 4.3L18.8 4.5C18.7 4.5 18.7 4.4 18.7 4.4 18.5 3.9 18.2 3.5 17.8 3.2 17.3 2.9 16.8 2.8 16.3 3.2 15.9 3.6 15.8 4.4 15.8 5 15.8 5.1 15.8 5.1 15.8 5.2 15.8 5.2 15.8 5.2 15.8 5.3L15 5.5C15 5.4 15 5.4 15 5.4 14.9 5.3 14.9 5.3 14.9 5.2 14.7 4.8 14.4 4.4 14 4.1 13.5 3.8 13 3.8 12.6 4.1 12.1 4.5 12 5.2 12 5.8 12 5.9 12 6 12 6 12 6.1 12 6.1 12 6.2L11.2 6.4C11.2 6.3 11.2 6.3 11.2 6.2 11.1 6.2 11.1 6.1 11.1 6 10.8 5.6 10.6 5.3 10.2 5.1 9.8 4.7 9.2 4.7 8.8 5.1 8.4 5.4 8.3 6 8.3 6.6 8.3 6.7 8.3 6.8 8.3 6.9 8.3 6.9 8.3 7 8.3 7.1L8.1 7.1 7.4 7.3C7.4 7.2 7.4 7.1 7.3 7.1 7.3 7 7.3 6.9 7.2 6.8 7 6.5 6.8 6.2 6.5 6 6 5.7 5.5 5.6 5 6 4.7 6.3 4.5 6.9 4.5 7.4 4.5 7.5 4.5 7.6 4.5 7.7 4.5 7.8 4.5 7.8 4.5 7.9L2.7 8.3 1 25.3 6.9 23.6 6.9 23.8 7 24 7.1 24.4 2.3 25.9 1 26.3 2.3 26.7 7.9 28.9 8 29 8 29 8 29 8.1 29 8.2 29 19.7 25.8 20.2 25.7 20.2 25.6 28.2 23.4 29 23.2ZM20.7 3C20.8 2.9 20.8 2.9 20.8 2.9 20.9 2.9 21 3 21.1 3.1 21.3 3.3 21.4 3.5 21.5 3.7 21.6 3.8 21.6 3.8 21.6 3.8L20.5 4.1C20.5 4.1 20.5 4 20.5 4 20.5 3.6 20.5 3.2 20.7 3ZM17 3.9C17 3.8 17 3.9 17 3.9 17.2 3.9 17.3 4 17.4 4.1 17.5 4.2 17.6 4.4 17.7 4.6 17.8 4.6 17.8 4.7 17.8 4.8L17.3 4.9 16.8 5C16.8 5 16.8 5 16.8 5 16.8 4.9 16.8 4.9 16.8 4.8 16.8 4.4 16.8 4.1 17 3.9ZM13 5.6C13 5.3 13.1 5 13.2 4.8 13.2 4.8 13.2 4.8 13.3 4.8 13.4 4.8 13.5 4.9 13.6 5 13.7 5.1 13.8 5.3 13.9 5.4 14 5.5 14 5.5 14 5.6 14 5.6 14.1 5.7 14.1 5.7L13 5.9C13 5.9 13 5.9 13 5.8 13 5.8 13 5.7 13 5.6ZM9.5 5.8C9.5 5.7 9.5 5.7 9.5 5.7 9.6 5.7 9.7 5.8 9.8 5.9 9.9 6 10 6.1 10.1 6.2 10.1 6.3 10.2 6.4 10.2 6.4 10.2 6.5 10.3 6.5 10.3 6.6 10.3 6.6 10.3 6.6 10.3 6.6L9.3 6.9C9.3 6.8 9.3 6.7 9.3 6.7 9.3 6.6 9.2 6.5 9.3 6.4 9.3 6.2 9.3 5.9 9.5 5.8ZM5.5 7.5C5.5 7.4 5.5 7.3 5.5 7.2 5.5 7 5.6 6.8 5.7 6.7 5.7 6.6 5.7 6.7 5.7 6.7 5.9 6.7 6 6.8 6.1 6.9 6.1 6.9 6.2 7 6.2 7.1 6.3 7.1 6.3 7.2 6.4 7.3 6.4 7.3 6.5 7.4 6.5 7.5L5.5 7.7C5.5 7.6 5.5 7.6 5.5 7.5ZM19.9 24.7L19.7 24.8 19.5 24.9 8.8 27.8 8.1 24.5 8 24.3 8 24.1 7.9 23.8 7.8 23.5 7.8 23.3 4.7 8.9 6.8 8.4C6.9 8.9 6.9 9.5 6.7 10 6.6 10.5 7.5 10.8 7.6 10.2 7.8 9.6 7.8 8.9 7.7 8.2L10.6 7.5C10.6 8 10.6 8.6 10.5 9 10.4 9.6 11.3 9.9 11.4 9.3 11.6 8.7 11.6 8 11.5 7.3L14.3 6.6C14.4 7.1 14.4 7.6 14.3 8.1 14.1 8.7 15 8.9 15.2 8.3 15.3 7.7 15.3 7 15.2 6.4L18.1 5.7C18.2 6.2 18.1 6.7 18 7.2 17.9 7.8 18.8 8 18.9 7.4 19.1 6.8 19.1 6.1 19 5.4L21.4 4.9 21.6 4.8 21.9 4.7C21.9 4.8 21.9 4.8 21.9 4.9 21.9 5.4 21.9 5.8 21.8 6.2 21.7 6.8 22.6 7.1 22.7 6.5 22.8 5.9 22.9 5.2 22.8 4.5L24 4.2 27.9 22.5 19.9 24.7ZM11.7 19.6L8.8 20.3 8.1 17.4 11 16.7 11.7 19.6ZM12.3 19.4L11.6 16.6 14.5 15.9 15.1 18.7 12.3 19.4ZM15.1 15.7L17.9 15 18.6 17.9 15.7 18.5 15.1 15.7ZM21.4 14.1L22.1 17 20.8 17.3 20.6 17.4 20.3 17.4 19.2 17.7 18.5 14.8 20.6 14.3 20.8 14.3 21.1 14.2 21.4 14.1ZM22 14L24.9 13.3 25.6 16.1 22.7 16.8 22 14ZM14.8 15.1L14.2 12.3 17 11.6 17.7 14.4 14.8 15.1ZM18.3 14.2L17.6 11.4 20.5 10.7 20.8 11.9 21 12.7 21.1 13.4 21.2 13.6 21.1 13.6 20.9 13.6 20.6 13.7 18.3 14.2ZM24 9.8L24.7 12.7 21.8 13.4 21.3 11.4 21.1 10.7 21.1 10.5 21.2 10.5 21.4 10.5 24 9.8ZM12.5 22.9L12.6 23 10.6 23.5 9.7 23.7 9.7 23.5 9.6 23.3 9.5 23 9.5 22.8 9 20.9 11.9 20.2 12.3 22 12.4 22.2 12.4 22.4 12.5 22.7 12.5 22.9ZM16 22L16 22.2 14.3 22.6 13.2 22.8 13.1 22.7 13.1 22.5 13 22.3 13 22 12.9 21.8 12.5 20 15.4 19.3 15.8 21 15.8 21.2 15.9 21.4 15.9 21.6 16 21.8 16 22ZM19.3 20.4L19.5 21.3 19 21.4 18.8 21.5 18.5 21.5 16.6 22 16.6 21.8 16.6 21.6 16.5 21.4 16.5 21.3 16.4 21 16.4 20.8 16 19.1 18.8 18.4 19.2 20 19.3 20.2 19.3 20.4Z"></path></svg></span> יומן תפוסה</button>
<?php
        }
    }
    else {
?>
		<button class="create-order" data-pagetype="order" onclick="openNewOrder(this)"><span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 33 30" width="33" height="30"><g id="דסקטופ"><g id="Group 4"><g id="Group 3"><g id="Group 2 copy 3"><path id="Shape 5" class="shp0" d="M28.45 20.68C28.18 20.86 27.89 21.02 27.6 21.17L27.9 22.55L19.9 24.75L19.68 24.82L19.45 24.88L8.75 27.83L8.05 24.55L8 24.32L7.95 24.09L7.89 23.78L7.84 23.55L7.79 23.33L4.73 8.99L6.79 8.48C6.87 9.01 6.84 9.56 6.73 10.03C6.6 10.61 7.5 10.86 7.64 10.27C7.78 9.66 7.81 8.95 7.69 8.26L10.55 7.57C10.63 8.09 10.6 8.63 10.5 9.1C10.36 9.68 11.27 9.93 11.4 9.34C11.54 8.73 11.57 8.03 11.46 7.35L14.32 6.65C14.39 7.17 14.36 7.71 14.26 8.17C14.12 8.75 15.03 9 15.16 8.41C15.31 7.81 15.33 7.11 15.22 6.43L18.08 5.74C18.15 6.25 18.13 6.78 18.02 7.24C17.88 7.82 18.79 8.07 18.93 7.48C19.07 6.88 19.09 6.19 18.99 5.52L21.39 4.93L21.64 4.88L21.85 4.82C21.85 4.87 21.86 4.92 21.86 4.97C21.91 5.43 21.88 5.9 21.78 6.31C21.77 6.34 21.78 6.37 21.78 6.4C22.09 6.31 22.41 6.24 22.74 6.19C22.81 5.68 22.83 5.14 22.75 4.6L23.97 4.31L24.35 6.1C24.68 6.11 25.01 6.15 25.33 6.2L24.88 4.11L24.68 3.18L23.74 3.4L22.52 3.7C22.31 3.16 21.98 2.68 21.51 2.34C21.06 2.02 20.52 1.99 20.09 2.36C19.59 2.78 19.54 3.59 19.56 4.24C19.56 4.3 19.56 4.36 19.56 4.42L18.75 4.62C18.73 4.55 18.69 4.5 18.67 4.44C18.46 3.98 18.16 3.57 17.75 3.27C17.3 2.95 16.75 2.92 16.32 3.29C15.86 3.69 15.79 4.43 15.79 5.06C15.79 5.14 15.79 5.22 15.8 5.3C15.8 5.31 15.8 5.32 15.8 5.34L14.98 5.53C14.98 5.52 14.97 5.5 14.96 5.48C14.93 5.41 14.89 5.34 14.86 5.27C14.65 4.85 14.37 4.48 13.99 4.2C13.54 3.88 12.99 3.85 12.56 4.22C12.12 4.59 12.03 5.28 12.03 5.88C12.03 5.96 12.03 6.04 12.03 6.12C12.03 6.16 12.03 6.21 12.04 6.25L11.22 6.45C11.2 6.4 11.17 6.36 11.15 6.31C11.12 6.24 11.08 6.16 11.04 6.09C10.84 5.72 10.58 5.38 10.22 5.13C9.77 4.81 9.23 4.78 8.8 5.15C8.39 5.5 8.29 6.12 8.27 6.7C8.27 6.78 8.27 6.86 8.27 6.94C8.27 7.01 8.27 7.09 8.27 7.17L8.06 7.22L7.44 7.35C7.41 7.28 7.37 7.21 7.34 7.14C7.3 7.07 7.27 6.99 7.23 6.92C7.04 6.59 6.78 6.29 6.46 6.06C6.01 5.74 5.47 5.71 5.04 6.08C4.65 6.41 4.54 6.97 4.51 7.51C4.51 7.59 4.51 7.67 4.51 7.75C4.51 7.83 4.51 7.91 4.51 7.99L2.69 8.39L1 25.29L6.88 23.59L6.93 23.81L6.98 24.04L7.05 24.37L2.29 25.86L1.02 26.26L2.26 26.74L7.93 28.94L8.03 28.98L8.03 28.99L8.04 28.98L8.08 29L8.23 28.96L19.7 25.78L20.15 25.66L20.15 25.65L28.15 23.45L29 23.21L28.81 22.36L28.45 20.68ZM20.72 3.05C20.76 3 20.76 3 20.78 3.01C20.9 3.03 21 3.11 21.12 3.23C21.29 3.4 21.43 3.6 21.54 3.81C21.56 3.85 21.58 3.89 21.6 3.93L20.49 4.19C20.49 4.14 20.49 4.09 20.49 4.04C20.49 3.67 20.53 3.26 20.72 3.05ZM16.98 3.98C17.02 3.93 17.01 3.94 17.03 3.94C17.15 3.96 17.25 4.04 17.37 4.16C17.51 4.3 17.62 4.47 17.72 4.64C17.76 4.71 17.8 4.77 17.83 4.84L17.28 4.99L16.83 5.11L16.83 5.1C16.83 5.02 16.78 4.94 16.78 4.86C16.79 4.52 16.81 4.17 16.98 3.98ZM12.97 5.67C12.99 5.37 13.04 5.08 13.19 4.91C13.24 4.86 13.24 4.87 13.26 4.87C13.38 4.89 13.48 4.97 13.6 5.09C13.72 5.2 13.81 5.33 13.9 5.47C13.95 5.54 13.99 5.61 14.02 5.68C14.04 5.71 14.05 5.73 14.06 5.76L12.97 6.02C12.97 5.99 12.97 5.95 12.97 5.91C12.97 5.83 12.97 5.75 12.97 5.67ZM9.44 5.84C9.49 5.79 9.48 5.79 9.5 5.8C9.62 5.82 9.72 5.9 9.84 6.02C9.93 6.11 10.01 6.2 10.08 6.3C10.13 6.37 10.16 6.44 10.2 6.51C10.23 6.57 10.27 6.62 10.3 6.67C10.3 6.68 10.3 6.68 10.3 6.69L9.3 6.94C9.3 6.87 9.25 6.8 9.25 6.73C9.26 6.65 9.24 6.57 9.24 6.49C9.27 6.23 9.31 5.99 9.44 5.84ZM5.45 7.55C5.45 7.46 5.46 7.38 5.47 7.3C5.5 7.09 5.56 6.9 5.67 6.77C5.71 6.72 5.72 6.73 5.74 6.73C5.86 6.75 5.96 6.83 6.08 6.95C6.13 7.01 6.18 7.07 6.23 7.14C6.29 7.2 6.34 7.27 6.38 7.34C6.43 7.41 6.47 7.48 6.51 7.55L5.44 7.79C5.44 7.71 5.44 7.63 5.45 7.55ZM14.83 15.15L16.04 14.86C16.01 14.59 16 14.33 16 14.05C16 13.27 16.12 12.51 16.33 11.79L14.15 12.31L14.83 15.15ZM15.74 18.57L17.19 18.23C16.68 17.4 16.31 16.47 16.13 15.48L15.05 15.73L15.74 18.57ZM16.41 21.07L16.47 21.29L16.49 21.41L16.55 21.64L16.6 21.86L16.64 22L18.54 21.55L18.76 21.49L18.99 21.44L19.51 21.32L19.31 20.5C18.64 20.02 18.06 19.44 17.57 18.78L15.95 19.16L16.36 20.84L16.41 21.07ZM12.97 22.06L13.02 22.29L13.07 22.48L13.13 22.71L13.16 22.86L14.25 22.6L16.03 22.18L16 22.05L15.95 21.83L15.89 21.6L15.86 21.47L15.81 21.24L15.75 21.02L15.34 19.34L12.48 20.02L12.91 21.84L12.97 22.06ZM25 7.08C21.13 7.08 18 10.2 18 14.05C18 17.9 21.13 21.03 25 21.03C28.87 21.03 32 17.9 32 14.05C32 10.2 28.87 7.08 25 7.08ZM28 15.05L26 15.05L26 17.04L24 17.04L24 15.05L22 15.05L22 13.06L24 13.06L24 11.06L26 11.06L26 13.06L28 13.06L28 15.05ZM9.53 23.06L9.59 23.29L9.65 23.56L9.69 23.72L10.57 23.51L12.56 23.04L12.53 22.9L12.47 22.67L12.42 22.47L12.37 22.24L12.31 22.01L11.87 20.2L9 20.88L9.48 22.84L9.53 23.06ZM12.26 19.43L15.13 18.75L14.44 15.91L11.57 16.6L12.26 19.43ZM8.1 17.46L8.79 20.29L11.66 19.61L10.97 16.78L8.1 17.46Z" /></g></g></g></g></svg></span>יצירת הזמנה</button>
		<button class="create-preorder" data-pagetype="preorder" onclick="openNewOrder(this)"><span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 33 30" width="33" height="30"><g id="דסקטופ"><g id="Group 4"><g id="Group 3"><g id="Group 2 copy 3"><path id="Shape 5" class="shp0" d="M28.45 20.68C28.18 20.86 27.89 21.02 27.6 21.17L27.9 22.55L19.9 24.75L19.68 24.82L19.45 24.88L8.75 27.83L8.05 24.55L8 24.32L7.95 24.09L7.89 23.78L7.84 23.55L7.79 23.33L4.73 8.99L6.79 8.48C6.87 9.01 6.84 9.56 6.73 10.03C6.6 10.61 7.5 10.86 7.64 10.27C7.78 9.66 7.81 8.95 7.69 8.26L10.55 7.57C10.63 8.09 10.6 8.63 10.5 9.1C10.36 9.68 11.27 9.93 11.4 9.34C11.54 8.73 11.57 8.03 11.46 7.35L14.32 6.65C14.39 7.17 14.36 7.71 14.26 8.17C14.12 8.75 15.03 9 15.16 8.41C15.31 7.81 15.33 7.11 15.22 6.43L18.08 5.74C18.15 6.25 18.13 6.78 18.02 7.24C17.88 7.82 18.79 8.07 18.93 7.48C19.07 6.88 19.09 6.19 18.99 5.52L21.39 4.93L21.64 4.88L21.85 4.82C21.85 4.87 21.86 4.92 21.86 4.97C21.91 5.43 21.88 5.9 21.78 6.31C21.77 6.34 21.78 6.37 21.78 6.4C22.09 6.31 22.41 6.24 22.74 6.19C22.81 5.68 22.83 5.14 22.75 4.6L23.97 4.31L24.35 6.1C24.68 6.11 25.01 6.15 25.33 6.2L24.88 4.11L24.68 3.18L23.74 3.4L22.52 3.7C22.31 3.16 21.98 2.68 21.51 2.34C21.06 2.02 20.52 1.99 20.09 2.36C19.59 2.78 19.54 3.59 19.56 4.24C19.56 4.3 19.56 4.36 19.56 4.42L18.75 4.62C18.73 4.55 18.69 4.5 18.67 4.44C18.46 3.98 18.16 3.57 17.75 3.27C17.3 2.95 16.75 2.92 16.32 3.29C15.86 3.69 15.79 4.43 15.79 5.06C15.79 5.14 15.79 5.22 15.8 5.3C15.8 5.31 15.8 5.32 15.8 5.34L14.98 5.53C14.98 5.52 14.97 5.5 14.96 5.48C14.93 5.41 14.89 5.34 14.86 5.27C14.65 4.85 14.37 4.48 13.99 4.2C13.54 3.88 12.99 3.85 12.56 4.22C12.12 4.59 12.03 5.28 12.03 5.88C12.03 5.96 12.03 6.04 12.03 6.12C12.03 6.16 12.03 6.21 12.04 6.25L11.22 6.45C11.2 6.4 11.17 6.36 11.15 6.31C11.12 6.24 11.08 6.16 11.04 6.09C10.84 5.72 10.58 5.38 10.22 5.13C9.77 4.81 9.23 4.78 8.8 5.15C8.39 5.5 8.29 6.12 8.27 6.7C8.27 6.78 8.27 6.86 8.27 6.94C8.27 7.01 8.27 7.09 8.27 7.17L8.06 7.22L7.44 7.35C7.41 7.28 7.37 7.21 7.34 7.14C7.3 7.07 7.27 6.99 7.23 6.92C7.04 6.59 6.78 6.29 6.46 6.06C6.01 5.74 5.47 5.71 5.04 6.08C4.65 6.41 4.54 6.97 4.51 7.51C4.51 7.59 4.51 7.67 4.51 7.75C4.51 7.83 4.51 7.91 4.51 7.99L2.69 8.39L1 25.29L6.88 23.59L6.93 23.81L6.98 24.04L7.05 24.37L2.29 25.86L1.02 26.26L2.26 26.74L7.93 28.94L8.03 28.98L8.03 28.99L8.04 28.98L8.08 29L8.23 28.96L19.7 25.78L20.15 25.66L20.15 25.65L28.15 23.45L29 23.21L28.81 22.36L28.45 20.68ZM20.72 3.05C20.76 3 20.76 3 20.78 3.01C20.9 3.03 21 3.11 21.12 3.23C21.29 3.4 21.43 3.6 21.54 3.81C21.56 3.85 21.58 3.89 21.6 3.93L20.49 4.19C20.49 4.14 20.49 4.09 20.49 4.04C20.49 3.67 20.53 3.26 20.72 3.05ZM16.98 3.98C17.02 3.93 17.01 3.94 17.03 3.94C17.15 3.96 17.25 4.04 17.37 4.16C17.51 4.3 17.62 4.47 17.72 4.64C17.76 4.71 17.8 4.77 17.83 4.84L17.28 4.99L16.83 5.11L16.83 5.1C16.83 5.02 16.78 4.94 16.78 4.86C16.79 4.52 16.81 4.17 16.98 3.98ZM12.97 5.67C12.99 5.37 13.04 5.08 13.19 4.91C13.24 4.86 13.24 4.87 13.26 4.87C13.38 4.89 13.48 4.97 13.6 5.09C13.72 5.2 13.81 5.33 13.9 5.47C13.95 5.54 13.99 5.61 14.02 5.68C14.04 5.71 14.05 5.73 14.06 5.76L12.97 6.02C12.97 5.99 12.97 5.95 12.97 5.91C12.97 5.83 12.97 5.75 12.97 5.67ZM9.44 5.84C9.49 5.79 9.48 5.79 9.5 5.8C9.62 5.82 9.72 5.9 9.84 6.02C9.93 6.11 10.01 6.2 10.08 6.3C10.13 6.37 10.16 6.44 10.2 6.51C10.23 6.57 10.27 6.62 10.3 6.67C10.3 6.68 10.3 6.68 10.3 6.69L9.3 6.94C9.3 6.87 9.25 6.8 9.25 6.73C9.26 6.65 9.24 6.57 9.24 6.49C9.27 6.23 9.31 5.99 9.44 5.84ZM5.45 7.55C5.45 7.46 5.46 7.38 5.47 7.3C5.5 7.09 5.56 6.9 5.67 6.77C5.71 6.72 5.72 6.73 5.74 6.73C5.86 6.75 5.96 6.83 6.08 6.95C6.13 7.01 6.18 7.07 6.23 7.14C6.29 7.2 6.34 7.27 6.38 7.34C6.43 7.41 6.47 7.48 6.51 7.55L5.44 7.79C5.44 7.71 5.44 7.63 5.45 7.55ZM14.83 15.15L16.04 14.86C16.01 14.59 16 14.33 16 14.05C16 13.27 16.12 12.51 16.33 11.79L14.15 12.31L14.83 15.15ZM15.74 18.57L17.19 18.23C16.68 17.4 16.31 16.47 16.13 15.48L15.05 15.73L15.74 18.57ZM16.41 21.07L16.47 21.29L16.49 21.41L16.55 21.64L16.6 21.86L16.64 22L18.54 21.55L18.76 21.49L18.99 21.44L19.51 21.32L19.31 20.5C18.64 20.02 18.06 19.44 17.57 18.78L15.95 19.16L16.36 20.84L16.41 21.07ZM12.97 22.06L13.02 22.29L13.07 22.48L13.13 22.71L13.16 22.86L14.25 22.6L16.03 22.18L16 22.05L15.95 21.83L15.89 21.6L15.86 21.47L15.81 21.24L15.75 21.02L15.34 19.34L12.48 20.02L12.91 21.84L12.97 22.06ZM25 7.08C21.13 7.08 18 10.2 18 14.05C18 17.9 21.13 21.03 25 21.03C28.87 21.03 32 17.9 32 14.05C32 10.2 28.87 7.08 25 7.08ZM28 15.05L26 15.05L26 17.04L24 17.04L24 15.05L22 15.05L22 13.06L24 13.06L24 11.06L26 11.06L26 13.06L28 13.06L28 15.05ZM9.53 23.06L9.59 23.29L9.65 23.56L9.69 23.72L10.57 23.51L12.56 23.04L12.53 22.9L12.47 22.67L12.42 22.47L12.37 22.24L12.31 22.01L11.87 20.2L9 20.88L9.48 22.84L9.53 23.06ZM12.26 19.43L15.13 18.75L14.44 15.91L11.57 16.6L12.26 19.43ZM8.1 17.46L8.79 20.29L11.66 19.61L10.97 16.78L8.1 17.46Z" /></g></g></g></g></svg></span>שיריון מקום</button>
		<button class="tfusa" onclick="window.location.href='?page=calendar_ver2'"><span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 31" width="30" height="31"><path class="shp0" d="M29 23.2L28.8 22.3 24.9 4 24.7 3.1 23.7 3.3 22.5 3.6C22.3 3.1 22 2.6 21.5 2.3 21.1 1.9 20.5 1.9 20.1 2.3 19.6 2.7 19.5 3.5 19.6 4.2 19.6 4.2 19.6 4.3 19.6 4.3L18.8 4.5C18.7 4.5 18.7 4.4 18.7 4.4 18.5 3.9 18.2 3.5 17.8 3.2 17.3 2.9 16.8 2.8 16.3 3.2 15.9 3.6 15.8 4.4 15.8 5 15.8 5.1 15.8 5.1 15.8 5.2 15.8 5.2 15.8 5.2 15.8 5.3L15 5.5C15 5.4 15 5.4 15 5.4 14.9 5.3 14.9 5.3 14.9 5.2 14.7 4.8 14.4 4.4 14 4.1 13.5 3.8 13 3.8 12.6 4.1 12.1 4.5 12 5.2 12 5.8 12 5.9 12 6 12 6 12 6.1 12 6.1 12 6.2L11.2 6.4C11.2 6.3 11.2 6.3 11.2 6.2 11.1 6.2 11.1 6.1 11.1 6 10.8 5.6 10.6 5.3 10.2 5.1 9.8 4.7 9.2 4.7 8.8 5.1 8.4 5.4 8.3 6 8.3 6.6 8.3 6.7 8.3 6.8 8.3 6.9 8.3 6.9 8.3 7 8.3 7.1L8.1 7.1 7.4 7.3C7.4 7.2 7.4 7.1 7.3 7.1 7.3 7 7.3 6.9 7.2 6.8 7 6.5 6.8 6.2 6.5 6 6 5.7 5.5 5.6 5 6 4.7 6.3 4.5 6.9 4.5 7.4 4.5 7.5 4.5 7.6 4.5 7.7 4.5 7.8 4.5 7.8 4.5 7.9L2.7 8.3 1 25.3 6.9 23.6 6.9 23.8 7 24 7.1 24.4 2.3 25.9 1 26.3 2.3 26.7 7.9 28.9 8 29 8 29 8 29 8.1 29 8.2 29 19.7 25.8 20.2 25.7 20.2 25.6 28.2 23.4 29 23.2ZM20.7 3C20.8 2.9 20.8 2.9 20.8 2.9 20.9 2.9 21 3 21.1 3.1 21.3 3.3 21.4 3.5 21.5 3.7 21.6 3.8 21.6 3.8 21.6 3.8L20.5 4.1C20.5 4.1 20.5 4 20.5 4 20.5 3.6 20.5 3.2 20.7 3ZM17 3.9C17 3.8 17 3.9 17 3.9 17.2 3.9 17.3 4 17.4 4.1 17.5 4.2 17.6 4.4 17.7 4.6 17.8 4.6 17.8 4.7 17.8 4.8L17.3 4.9 16.8 5C16.8 5 16.8 5 16.8 5 16.8 4.9 16.8 4.9 16.8 4.8 16.8 4.4 16.8 4.1 17 3.9ZM13 5.6C13 5.3 13.1 5 13.2 4.8 13.2 4.8 13.2 4.8 13.3 4.8 13.4 4.8 13.5 4.9 13.6 5 13.7 5.1 13.8 5.3 13.9 5.4 14 5.5 14 5.5 14 5.6 14 5.6 14.1 5.7 14.1 5.7L13 5.9C13 5.9 13 5.9 13 5.8 13 5.8 13 5.7 13 5.6ZM9.5 5.8C9.5 5.7 9.5 5.7 9.5 5.7 9.6 5.7 9.7 5.8 9.8 5.9 9.9 6 10 6.1 10.1 6.2 10.1 6.3 10.2 6.4 10.2 6.4 10.2 6.5 10.3 6.5 10.3 6.6 10.3 6.6 10.3 6.6 10.3 6.6L9.3 6.9C9.3 6.8 9.3 6.7 9.3 6.7 9.3 6.6 9.2 6.5 9.3 6.4 9.3 6.2 9.3 5.9 9.5 5.8ZM5.5 7.5C5.5 7.4 5.5 7.3 5.5 7.2 5.5 7 5.6 6.8 5.7 6.7 5.7 6.6 5.7 6.7 5.7 6.7 5.9 6.7 6 6.8 6.1 6.9 6.1 6.9 6.2 7 6.2 7.1 6.3 7.1 6.3 7.2 6.4 7.3 6.4 7.3 6.5 7.4 6.5 7.5L5.5 7.7C5.5 7.6 5.5 7.6 5.5 7.5ZM19.9 24.7L19.7 24.8 19.5 24.9 8.8 27.8 8.1 24.5 8 24.3 8 24.1 7.9 23.8 7.8 23.5 7.8 23.3 4.7 8.9 6.8 8.4C6.9 8.9 6.9 9.5 6.7 10 6.6 10.5 7.5 10.8 7.6 10.2 7.8 9.6 7.8 8.9 7.7 8.2L10.6 7.5C10.6 8 10.6 8.6 10.5 9 10.4 9.6 11.3 9.9 11.4 9.3 11.6 8.7 11.6 8 11.5 7.3L14.3 6.6C14.4 7.1 14.4 7.6 14.3 8.1 14.1 8.7 15 8.9 15.2 8.3 15.3 7.7 15.3 7 15.2 6.4L18.1 5.7C18.2 6.2 18.1 6.7 18 7.2 17.9 7.8 18.8 8 18.9 7.4 19.1 6.8 19.1 6.1 19 5.4L21.4 4.9 21.6 4.8 21.9 4.7C21.9 4.8 21.9 4.8 21.9 4.9 21.9 5.4 21.9 5.8 21.8 6.2 21.7 6.8 22.6 7.1 22.7 6.5 22.8 5.9 22.9 5.2 22.8 4.5L24 4.2 27.9 22.5 19.9 24.7ZM11.7 19.6L8.8 20.3 8.1 17.4 11 16.7 11.7 19.6ZM12.3 19.4L11.6 16.6 14.5 15.9 15.1 18.7 12.3 19.4ZM15.1 15.7L17.9 15 18.6 17.9 15.7 18.5 15.1 15.7ZM21.4 14.1L22.1 17 20.8 17.3 20.6 17.4 20.3 17.4 19.2 17.7 18.5 14.8 20.6 14.3 20.8 14.3 21.1 14.2 21.4 14.1ZM22 14L24.9 13.3 25.6 16.1 22.7 16.8 22 14ZM14.8 15.1L14.2 12.3 17 11.6 17.7 14.4 14.8 15.1ZM18.3 14.2L17.6 11.4 20.5 10.7 20.8 11.9 21 12.7 21.1 13.4 21.2 13.6 21.1 13.6 20.9 13.6 20.6 13.7 18.3 14.2ZM24 9.8L24.7 12.7 21.8 13.4 21.3 11.4 21.1 10.7 21.1 10.5 21.2 10.5 21.4 10.5 24 9.8ZM12.5 22.9L12.6 23 10.6 23.5 9.7 23.7 9.7 23.5 9.6 23.3 9.5 23 9.5 22.8 9 20.9 11.9 20.2 12.3 22 12.4 22.2 12.4 22.4 12.5 22.7 12.5 22.9ZM16 22L16 22.2 14.3 22.6 13.2 22.8 13.1 22.7 13.1 22.5 13 22.3 13 22 12.9 21.8 12.5 20 15.4 19.3 15.8 21 15.8 21.2 15.9 21.4 15.9 21.6 16 21.8 16 22ZM19.3 20.4L19.5 21.3 19 21.4 18.8 21.5 18.5 21.5 16.6 22 16.6 21.8 16.6 21.6 16.5 21.4 16.5 21.3 16.4 21 16.4 20.8 16 19.1 18.8 18.4 19.2 20 19.3 20.2 19.3 20.4Z"></path></svg></span> יומן תפוסה</button>
<?php
    }

$_timer->log();
?>
	</div>
<?if($_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN) || $_CURRENT_USER->suffix()!='member'){?>
	<div class="count" <?=$_CURRENT_USER->showstats?>>
		<?if($_CURRENT_USER->showstats == 1 && !$_CURRENT_USER->userType){?>
		<div class="yearly" onclick="window.location.href='?page=orders&from=<?=urlencode(date('01/01/Y'))?>&to=<?=urlencode(date('31/12/Y'))?>&otype=order&orderStatus=active'">
			<div class="title">הזמנות השנה</div>
			<div class="count-num"><?=$sumY['cnt']?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 31" width="30" height="31"><path class="shp0" d="M29 23.2L28.8 22.3 24.9 4 24.7 3.1 23.7 3.3 22.5 3.6C22.3 3.1 22 2.6 21.5 2.3 21.1 1.9 20.5 1.9 20.1 2.3 19.6 2.7 19.5 3.5 19.6 4.2 19.6 4.2 19.6 4.3 19.6 4.3L18.8 4.5C18.7 4.5 18.7 4.4 18.7 4.4 18.5 3.9 18.2 3.5 17.8 3.2 17.3 2.9 16.8 2.8 16.3 3.2 15.9 3.6 15.8 4.4 15.8 5 15.8 5.1 15.8 5.1 15.8 5.2 15.8 5.2 15.8 5.2 15.8 5.3L15 5.5C15 5.4 15 5.4 15 5.4 14.9 5.3 14.9 5.3 14.9 5.2 14.7 4.8 14.4 4.4 14 4.1 13.5 3.8 13 3.8 12.6 4.1 12.1 4.5 12 5.2 12 5.8 12 5.9 12 6 12 6 12 6.1 12 6.1 12 6.2L11.2 6.4C11.2 6.3 11.2 6.3 11.2 6.2 11.1 6.2 11.1 6.1 11.1 6 10.8 5.6 10.6 5.3 10.2 5.1 9.8 4.7 9.2 4.7 8.8 5.1 8.4 5.4 8.3 6 8.3 6.6 8.3 6.7 8.3 6.8 8.3 6.9 8.3 6.9 8.3 7 8.3 7.1L8.1 7.1 7.4 7.3C7.4 7.2 7.4 7.1 7.3 7.1 7.3 7 7.3 6.9 7.2 6.8 7 6.5 6.8 6.2 6.5 6 6 5.7 5.5 5.6 5 6 4.7 6.3 4.5 6.9 4.5 7.4 4.5 7.5 4.5 7.6 4.5 7.7 4.5 7.8 4.5 7.8 4.5 7.9L2.7 8.3 1 25.3 6.9 23.6 6.9 23.8 7 24 7.1 24.4 2.3 25.9 1 26.3 2.3 26.7 7.9 28.9 8 29 8 29 8 29 8.1 29 8.2 29 19.7 25.8 20.2 25.7 20.2 25.6 28.2 23.4 29 23.2ZM20.7 3C20.8 2.9 20.8 2.9 20.8 2.9 20.9 2.9 21 3 21.1 3.1 21.3 3.3 21.4 3.5 21.5 3.7 21.6 3.8 21.6 3.8 21.6 3.8L20.5 4.1C20.5 4.1 20.5 4 20.5 4 20.5 3.6 20.5 3.2 20.7 3ZM17 3.9C17 3.8 17 3.9 17 3.9 17.2 3.9 17.3 4 17.4 4.1 17.5 4.2 17.6 4.4 17.7 4.6 17.8 4.6 17.8 4.7 17.8 4.8L17.3 4.9 16.8 5C16.8 5 16.8 5 16.8 5 16.8 4.9 16.8 4.9 16.8 4.8 16.8 4.4 16.8 4.1 17 3.9ZM13 5.6C13 5.3 13.1 5 13.2 4.8 13.2 4.8 13.2 4.8 13.3 4.8 13.4 4.8 13.5 4.9 13.6 5 13.7 5.1 13.8 5.3 13.9 5.4 14 5.5 14 5.5 14 5.6 14 5.6 14.1 5.7 14.1 5.7L13 5.9C13 5.9 13 5.9 13 5.8 13 5.8 13 5.7 13 5.6ZM9.5 5.8C9.5 5.7 9.5 5.7 9.5 5.7 9.6 5.7 9.7 5.8 9.8 5.9 9.9 6 10 6.1 10.1 6.2 10.1 6.3 10.2 6.4 10.2 6.4 10.2 6.5 10.3 6.5 10.3 6.6 10.3 6.6 10.3 6.6 10.3 6.6L9.3 6.9C9.3 6.8 9.3 6.7 9.3 6.7 9.3 6.6 9.2 6.5 9.3 6.4 9.3 6.2 9.3 5.9 9.5 5.8ZM5.5 7.5C5.5 7.4 5.5 7.3 5.5 7.2 5.5 7 5.6 6.8 5.7 6.7 5.7 6.6 5.7 6.7 5.7 6.7 5.9 6.7 6 6.8 6.1 6.9 6.1 6.9 6.2 7 6.2 7.1 6.3 7.1 6.3 7.2 6.4 7.3 6.4 7.3 6.5 7.4 6.5 7.5L5.5 7.7C5.5 7.6 5.5 7.6 5.5 7.5ZM19.9 24.7L19.7 24.8 19.5 24.9 8.8 27.8 8.1 24.5 8 24.3 8 24.1 7.9 23.8 7.8 23.5 7.8 23.3 4.7 8.9 6.8 8.4C6.9 8.9 6.9 9.5 6.7 10 6.6 10.5 7.5 10.8 7.6 10.2 7.8 9.6 7.8 8.9 7.7 8.2L10.6 7.5C10.6 8 10.6 8.6 10.5 9 10.4 9.6 11.3 9.9 11.4 9.3 11.6 8.7 11.6 8 11.5 7.3L14.3 6.6C14.4 7.1 14.4 7.6 14.3 8.1 14.1 8.7 15 8.9 15.2 8.3 15.3 7.7 15.3 7 15.2 6.4L18.1 5.7C18.2 6.2 18.1 6.7 18 7.2 17.9 7.8 18.8 8 18.9 7.4 19.1 6.8 19.1 6.1 19 5.4L21.4 4.9 21.6 4.8 21.9 4.7C21.9 4.8 21.9 4.8 21.9 4.9 21.9 5.4 21.9 5.8 21.8 6.2 21.7 6.8 22.6 7.1 22.7 6.5 22.8 5.9 22.9 5.2 22.8 4.5L24 4.2 27.9 22.5 19.9 24.7ZM11.7 19.6L8.8 20.3 8.1 17.4 11 16.7 11.7 19.6ZM12.3 19.4L11.6 16.6 14.5 15.9 15.1 18.7 12.3 19.4ZM15.1 15.7L17.9 15 18.6 17.9 15.7 18.5 15.1 15.7ZM21.4 14.1L22.1 17 20.8 17.3 20.6 17.4 20.3 17.4 19.2 17.7 18.5 14.8 20.6 14.3 20.8 14.3 21.1 14.2 21.4 14.1ZM22 14L24.9 13.3 25.6 16.1 22.7 16.8 22 14ZM14.8 15.1L14.2 12.3 17 11.6 17.7 14.4 14.8 15.1ZM18.3 14.2L17.6 11.4 20.5 10.7 20.8 11.9 21 12.7 21.1 13.4 21.2 13.6 21.1 13.6 20.9 13.6 20.6 13.7 18.3 14.2ZM24 9.8L24.7 12.7 21.8 13.4 21.3 11.4 21.1 10.7 21.1 10.5 21.2 10.5 21.4 10.5 24 9.8ZM12.5 22.9L12.6 23 10.6 23.5 9.7 23.7 9.7 23.5 9.6 23.3 9.5 23 9.5 22.8 9 20.9 11.9 20.2 12.3 22 12.4 22.2 12.4 22.4 12.5 22.7 12.5 22.9ZM16 22L16 22.2 14.3 22.6 13.2 22.8 13.1 22.7 13.1 22.5 13 22.3 13 22 12.9 21.8 12.5 20 15.4 19.3 15.8 21 15.8 21.2 15.9 21.4 15.9 21.6 16 21.8 16 22ZM19.3 20.4L19.5 21.3 19 21.4 18.8 21.5 18.5 21.5 16.6 22 16.6 21.8 16.6 21.6 16.5 21.4 16.5 21.3 16.4 21 16.4 20.8 16 19.1 18.8 18.4 19.2 20 19.3 20.2 19.3 20.4Z"></path></svg></div>
			<?if ($_CURRENT_USER->is_spa()){?>
				<div class="total_line"><?=$sumY['cnt_Treatments']?> טיפולים</div>
				<div class="total_line"><?=$sumY['cnt_Rooms']?> שהות בחדרים</div>
			<?}?>
			<div class="price">₪<?=number_format($sumY['sum'] ?: 0)?></div>
		</div>
		<div class="monthly" onclick="window.location.href='?page=orders&from=<?=urlencode(date('01/m/Y'))?>&to=<?=urlencode(date('t/m/Y'))?>&otype=order&orderStatus=active'">
			<div class="title">הזמנות החודש</div>
			<div class="count-num"><?=$sumM['cnt']?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 31" width="30" height="31"><path class="shp0" d="M29 23.2L28.8 22.3 24.9 4 24.7 3.1 23.7 3.3 22.5 3.6C22.3 3.1 22 2.6 21.5 2.3 21.1 1.9 20.5 1.9 20.1 2.3 19.6 2.7 19.5 3.5 19.6 4.2 19.6 4.2 19.6 4.3 19.6 4.3L18.8 4.5C18.7 4.5 18.7 4.4 18.7 4.4 18.5 3.9 18.2 3.5 17.8 3.2 17.3 2.9 16.8 2.8 16.3 3.2 15.9 3.6 15.8 4.4 15.8 5 15.8 5.1 15.8 5.1 15.8 5.2 15.8 5.2 15.8 5.2 15.8 5.3L15 5.5C15 5.4 15 5.4 15 5.4 14.9 5.3 14.9 5.3 14.9 5.2 14.7 4.8 14.4 4.4 14 4.1 13.5 3.8 13 3.8 12.6 4.1 12.1 4.5 12 5.2 12 5.8 12 5.9 12 6 12 6 12 6.1 12 6.1 12 6.2L11.2 6.4C11.2 6.3 11.2 6.3 11.2 6.2 11.1 6.2 11.1 6.1 11.1 6 10.8 5.6 10.6 5.3 10.2 5.1 9.8 4.7 9.2 4.7 8.8 5.1 8.4 5.4 8.3 6 8.3 6.6 8.3 6.7 8.3 6.8 8.3 6.9 8.3 6.9 8.3 7 8.3 7.1L8.1 7.1 7.4 7.3C7.4 7.2 7.4 7.1 7.3 7.1 7.3 7 7.3 6.9 7.2 6.8 7 6.5 6.8 6.2 6.5 6 6 5.7 5.5 5.6 5 6 4.7 6.3 4.5 6.9 4.5 7.4 4.5 7.5 4.5 7.6 4.5 7.7 4.5 7.8 4.5 7.8 4.5 7.9L2.7 8.3 1 25.3 6.9 23.6 6.9 23.8 7 24 7.1 24.4 2.3 25.9 1 26.3 2.3 26.7 7.9 28.9 8 29 8 29 8 29 8.1 29 8.2 29 19.7 25.8 20.2 25.7 20.2 25.6 28.2 23.4 29 23.2ZM20.7 3C20.8 2.9 20.8 2.9 20.8 2.9 20.9 2.9 21 3 21.1 3.1 21.3 3.3 21.4 3.5 21.5 3.7 21.6 3.8 21.6 3.8 21.6 3.8L20.5 4.1C20.5 4.1 20.5 4 20.5 4 20.5 3.6 20.5 3.2 20.7 3ZM17 3.9C17 3.8 17 3.9 17 3.9 17.2 3.9 17.3 4 17.4 4.1 17.5 4.2 17.6 4.4 17.7 4.6 17.8 4.6 17.8 4.7 17.8 4.8L17.3 4.9 16.8 5C16.8 5 16.8 5 16.8 5 16.8 4.9 16.8 4.9 16.8 4.8 16.8 4.4 16.8 4.1 17 3.9ZM13 5.6C13 5.3 13.1 5 13.2 4.8 13.2 4.8 13.2 4.8 13.3 4.8 13.4 4.8 13.5 4.9 13.6 5 13.7 5.1 13.8 5.3 13.9 5.4 14 5.5 14 5.5 14 5.6 14 5.6 14.1 5.7 14.1 5.7L13 5.9C13 5.9 13 5.9 13 5.8 13 5.8 13 5.7 13 5.6ZM9.5 5.8C9.5 5.7 9.5 5.7 9.5 5.7 9.6 5.7 9.7 5.8 9.8 5.9 9.9 6 10 6.1 10.1 6.2 10.1 6.3 10.2 6.4 10.2 6.4 10.2 6.5 10.3 6.5 10.3 6.6 10.3 6.6 10.3 6.6 10.3 6.6L9.3 6.9C9.3 6.8 9.3 6.7 9.3 6.7 9.3 6.6 9.2 6.5 9.3 6.4 9.3 6.2 9.3 5.9 9.5 5.8ZM5.5 7.5C5.5 7.4 5.5 7.3 5.5 7.2 5.5 7 5.6 6.8 5.7 6.7 5.7 6.6 5.7 6.7 5.7 6.7 5.9 6.7 6 6.8 6.1 6.9 6.1 6.9 6.2 7 6.2 7.1 6.3 7.1 6.3 7.2 6.4 7.3 6.4 7.3 6.5 7.4 6.5 7.5L5.5 7.7C5.5 7.6 5.5 7.6 5.5 7.5ZM19.9 24.7L19.7 24.8 19.5 24.9 8.8 27.8 8.1 24.5 8 24.3 8 24.1 7.9 23.8 7.8 23.5 7.8 23.3 4.7 8.9 6.8 8.4C6.9 8.9 6.9 9.5 6.7 10 6.6 10.5 7.5 10.8 7.6 10.2 7.8 9.6 7.8 8.9 7.7 8.2L10.6 7.5C10.6 8 10.6 8.6 10.5 9 10.4 9.6 11.3 9.9 11.4 9.3 11.6 8.7 11.6 8 11.5 7.3L14.3 6.6C14.4 7.1 14.4 7.6 14.3 8.1 14.1 8.7 15 8.9 15.2 8.3 15.3 7.7 15.3 7 15.2 6.4L18.1 5.7C18.2 6.2 18.1 6.7 18 7.2 17.9 7.8 18.8 8 18.9 7.4 19.1 6.8 19.1 6.1 19 5.4L21.4 4.9 21.6 4.8 21.9 4.7C21.9 4.8 21.9 4.8 21.9 4.9 21.9 5.4 21.9 5.8 21.8 6.2 21.7 6.8 22.6 7.1 22.7 6.5 22.8 5.9 22.9 5.2 22.8 4.5L24 4.2 27.9 22.5 19.9 24.7ZM11.7 19.6L8.8 20.3 8.1 17.4 11 16.7 11.7 19.6ZM12.3 19.4L11.6 16.6 14.5 15.9 15.1 18.7 12.3 19.4ZM15.1 15.7L17.9 15 18.6 17.9 15.7 18.5 15.1 15.7ZM21.4 14.1L22.1 17 20.8 17.3 20.6 17.4 20.3 17.4 19.2 17.7 18.5 14.8 20.6 14.3 20.8 14.3 21.1 14.2 21.4 14.1ZM22 14L24.9 13.3 25.6 16.1 22.7 16.8 22 14ZM14.8 15.1L14.2 12.3 17 11.6 17.7 14.4 14.8 15.1ZM18.3 14.2L17.6 11.4 20.5 10.7 20.8 11.9 21 12.7 21.1 13.4 21.2 13.6 21.1 13.6 20.9 13.6 20.6 13.7 18.3 14.2ZM24 9.8L24.7 12.7 21.8 13.4 21.3 11.4 21.1 10.7 21.1 10.5 21.2 10.5 21.4 10.5 24 9.8ZM12.5 22.9L12.6 23 10.6 23.5 9.7 23.7 9.7 23.5 9.6 23.3 9.5 23 9.5 22.8 9 20.9 11.9 20.2 12.3 22 12.4 22.2 12.4 22.4 12.5 22.7 12.5 22.9ZM16 22L16 22.2 14.3 22.6 13.2 22.8 13.1 22.7 13.1 22.5 13 22.3 13 22 12.9 21.8 12.5 20 15.4 19.3 15.8 21 15.8 21.2 15.9 21.4 15.9 21.6 16 21.8 16 22ZM19.3 20.4L19.5 21.3 19 21.4 18.8 21.5 18.5 21.5 16.6 22 16.6 21.8 16.6 21.6 16.5 21.4 16.5 21.3 16.4 21 16.4 20.8 16 19.1 18.8 18.4 19.2 20 19.3 20.2 19.3 20.4Z"></path></svg></div>
			<?if ($_CURRENT_USER->is_spa()){?>
				<div class="total_line"><?=$sumM['cnt_Treatments']?> טיפולים</div>
				<div class="total_line"><?=$sumM['cnt_Rooms']?> שהות בחדרים</div>
			<?}?>
			<div class="price">₪<?=number_format($sumM['sum'] ?: 0)?></div>
		</div>
		<div class="weekly" onclick="window.location.href='?page=orders&from=<?=urlencode(date('d/m/Y', strtotime('last Saturday')))?>&to=<?=urlencode(date('d/m/Y', strtotime('this Saturday')))?>&otype=order&orderStatus=active'">
			<div class="title">הזמנות השבוע</div>
			<div class="count-num"><?=$sumW['cnt']?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 31" width="30" height="31"><path class="shp0" d="M29 23.2L28.8 22.3 24.9 4 24.7 3.1 23.7 3.3 22.5 3.6C22.3 3.1 22 2.6 21.5 2.3 21.1 1.9 20.5 1.9 20.1 2.3 19.6 2.7 19.5 3.5 19.6 4.2 19.6 4.2 19.6 4.3 19.6 4.3L18.8 4.5C18.7 4.5 18.7 4.4 18.7 4.4 18.5 3.9 18.2 3.5 17.8 3.2 17.3 2.9 16.8 2.8 16.3 3.2 15.9 3.6 15.8 4.4 15.8 5 15.8 5.1 15.8 5.1 15.8 5.2 15.8 5.2 15.8 5.2 15.8 5.3L15 5.5C15 5.4 15 5.4 15 5.4 14.9 5.3 14.9 5.3 14.9 5.2 14.7 4.8 14.4 4.4 14 4.1 13.5 3.8 13 3.8 12.6 4.1 12.1 4.5 12 5.2 12 5.8 12 5.9 12 6 12 6 12 6.1 12 6.1 12 6.2L11.2 6.4C11.2 6.3 11.2 6.3 11.2 6.2 11.1 6.2 11.1 6.1 11.1 6 10.8 5.6 10.6 5.3 10.2 5.1 9.8 4.7 9.2 4.7 8.8 5.1 8.4 5.4 8.3 6 8.3 6.6 8.3 6.7 8.3 6.8 8.3 6.9 8.3 6.9 8.3 7 8.3 7.1L8.1 7.1 7.4 7.3C7.4 7.2 7.4 7.1 7.3 7.1 7.3 7 7.3 6.9 7.2 6.8 7 6.5 6.8 6.2 6.5 6 6 5.7 5.5 5.6 5 6 4.7 6.3 4.5 6.9 4.5 7.4 4.5 7.5 4.5 7.6 4.5 7.7 4.5 7.8 4.5 7.8 4.5 7.9L2.7 8.3 1 25.3 6.9 23.6 6.9 23.8 7 24 7.1 24.4 2.3 25.9 1 26.3 2.3 26.7 7.9 28.9 8 29 8 29 8 29 8.1 29 8.2 29 19.7 25.8 20.2 25.7 20.2 25.6 28.2 23.4 29 23.2ZM20.7 3C20.8 2.9 20.8 2.9 20.8 2.9 20.9 2.9 21 3 21.1 3.1 21.3 3.3 21.4 3.5 21.5 3.7 21.6 3.8 21.6 3.8 21.6 3.8L20.5 4.1C20.5 4.1 20.5 4 20.5 4 20.5 3.6 20.5 3.2 20.7 3ZM17 3.9C17 3.8 17 3.9 17 3.9 17.2 3.9 17.3 4 17.4 4.1 17.5 4.2 17.6 4.4 17.7 4.6 17.8 4.6 17.8 4.7 17.8 4.8L17.3 4.9 16.8 5C16.8 5 16.8 5 16.8 5 16.8 4.9 16.8 4.9 16.8 4.8 16.8 4.4 16.8 4.1 17 3.9ZM13 5.6C13 5.3 13.1 5 13.2 4.8 13.2 4.8 13.2 4.8 13.3 4.8 13.4 4.8 13.5 4.9 13.6 5 13.7 5.1 13.8 5.3 13.9 5.4 14 5.5 14 5.5 14 5.6 14 5.6 14.1 5.7 14.1 5.7L13 5.9C13 5.9 13 5.9 13 5.8 13 5.8 13 5.7 13 5.6ZM9.5 5.8C9.5 5.7 9.5 5.7 9.5 5.7 9.6 5.7 9.7 5.8 9.8 5.9 9.9 6 10 6.1 10.1 6.2 10.1 6.3 10.2 6.4 10.2 6.4 10.2 6.5 10.3 6.5 10.3 6.6 10.3 6.6 10.3 6.6 10.3 6.6L9.3 6.9C9.3 6.8 9.3 6.7 9.3 6.7 9.3 6.6 9.2 6.5 9.3 6.4 9.3 6.2 9.3 5.9 9.5 5.8ZM5.5 7.5C5.5 7.4 5.5 7.3 5.5 7.2 5.5 7 5.6 6.8 5.7 6.7 5.7 6.6 5.7 6.7 5.7 6.7 5.9 6.7 6 6.8 6.1 6.9 6.1 6.9 6.2 7 6.2 7.1 6.3 7.1 6.3 7.2 6.4 7.3 6.4 7.3 6.5 7.4 6.5 7.5L5.5 7.7C5.5 7.6 5.5 7.6 5.5 7.5ZM19.9 24.7L19.7 24.8 19.5 24.9 8.8 27.8 8.1 24.5 8 24.3 8 24.1 7.9 23.8 7.8 23.5 7.8 23.3 4.7 8.9 6.8 8.4C6.9 8.9 6.9 9.5 6.7 10 6.6 10.5 7.5 10.8 7.6 10.2 7.8 9.6 7.8 8.9 7.7 8.2L10.6 7.5C10.6 8 10.6 8.6 10.5 9 10.4 9.6 11.3 9.9 11.4 9.3 11.6 8.7 11.6 8 11.5 7.3L14.3 6.6C14.4 7.1 14.4 7.6 14.3 8.1 14.1 8.7 15 8.9 15.2 8.3 15.3 7.7 15.3 7 15.2 6.4L18.1 5.7C18.2 6.2 18.1 6.7 18 7.2 17.9 7.8 18.8 8 18.9 7.4 19.1 6.8 19.1 6.1 19 5.4L21.4 4.9 21.6 4.8 21.9 4.7C21.9 4.8 21.9 4.8 21.9 4.9 21.9 5.4 21.9 5.8 21.8 6.2 21.7 6.8 22.6 7.1 22.7 6.5 22.8 5.9 22.9 5.2 22.8 4.5L24 4.2 27.9 22.5 19.9 24.7ZM11.7 19.6L8.8 20.3 8.1 17.4 11 16.7 11.7 19.6ZM12.3 19.4L11.6 16.6 14.5 15.9 15.1 18.7 12.3 19.4ZM15.1 15.7L17.9 15 18.6 17.9 15.7 18.5 15.1 15.7ZM21.4 14.1L22.1 17 20.8 17.3 20.6 17.4 20.3 17.4 19.2 17.7 18.5 14.8 20.6 14.3 20.8 14.3 21.1 14.2 21.4 14.1ZM22 14L24.9 13.3 25.6 16.1 22.7 16.8 22 14ZM14.8 15.1L14.2 12.3 17 11.6 17.7 14.4 14.8 15.1ZM18.3 14.2L17.6 11.4 20.5 10.7 20.8 11.9 21 12.7 21.1 13.4 21.2 13.6 21.1 13.6 20.9 13.6 20.6 13.7 18.3 14.2ZM24 9.8L24.7 12.7 21.8 13.4 21.3 11.4 21.1 10.7 21.1 10.5 21.2 10.5 21.4 10.5 24 9.8ZM12.5 22.9L12.6 23 10.6 23.5 9.7 23.7 9.7 23.5 9.6 23.3 9.5 23 9.5 22.8 9 20.9 11.9 20.2 12.3 22 12.4 22.2 12.4 22.4 12.5 22.7 12.5 22.9ZM16 22L16 22.2 14.3 22.6 13.2 22.8 13.1 22.7 13.1 22.5 13 22.3 13 22 12.9 21.8 12.5 20 15.4 19.3 15.8 21 15.8 21.2 15.9 21.4 15.9 21.6 16 21.8 16 22ZM19.3 20.4L19.5 21.3 19 21.4 18.8 21.5 18.5 21.5 16.6 22 16.6 21.8 16.6 21.6 16.5 21.4 16.5 21.3 16.4 21 16.4 20.8 16 19.1 18.8 18.4 19.2 20 19.3 20.2 19.3 20.4Z"></path></svg></div>
			<?if ($_CURRENT_USER->is_spa()){?>
				<div class="total_line"><?=$sumW['cnt_Treatments']?> טיפולים</div>
				<div class="total_line"><?=$sumW['cnt_Rooms']?> שהות בחדרים</div>
			<?}?>
			<div class="price">₪<?=number_format($sumW['sum'] ?: 0) ?></div>
		</div>
		<?}?>
		<?if (!$_CURRENT_USER->is_spa()){?>
		<div class="sign" onclick="window.location.href='?page=orders&orderSign=incomplete&otype=order&orderStatus=active'">
			<div class="title">הזמנות לחתימה</div>
			<div class="count-num"><?=$unapproved["upCount"]?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 31" width="30" height="31"><path class="shp0" d="M29 23.2L28.8 22.3 24.9 4 24.7 3.1 23.7 3.3 22.5 3.6C22.3 3.1 22 2.6 21.5 2.3 21.1 1.9 20.5 1.9 20.1 2.3 19.6 2.7 19.5 3.5 19.6 4.2 19.6 4.2 19.6 4.3 19.6 4.3L18.8 4.5C18.7 4.5 18.7 4.4 18.7 4.4 18.5 3.9 18.2 3.5 17.8 3.2 17.3 2.9 16.8 2.8 16.3 3.2 15.9 3.6 15.8 4.4 15.8 5 15.8 5.1 15.8 5.1 15.8 5.2 15.8 5.2 15.8 5.2 15.8 5.3L15 5.5C15 5.4 15 5.4 15 5.4 14.9 5.3 14.9 5.3 14.9 5.2 14.7 4.8 14.4 4.4 14 4.1 13.5 3.8 13 3.8 12.6 4.1 12.1 4.5 12 5.2 12 5.8 12 5.9 12 6 12 6 12 6.1 12 6.1 12 6.2L11.2 6.4C11.2 6.3 11.2 6.3 11.2 6.2 11.1 6.2 11.1 6.1 11.1 6 10.8 5.6 10.6 5.3 10.2 5.1 9.8 4.7 9.2 4.7 8.8 5.1 8.4 5.4 8.3 6 8.3 6.6 8.3 6.7 8.3 6.8 8.3 6.9 8.3 6.9 8.3 7 8.3 7.1L8.1 7.1 7.4 7.3C7.4 7.2 7.4 7.1 7.3 7.1 7.3 7 7.3 6.9 7.2 6.8 7 6.5 6.8 6.2 6.5 6 6 5.7 5.5 5.6 5 6 4.7 6.3 4.5 6.9 4.5 7.4 4.5 7.5 4.5 7.6 4.5 7.7 4.5 7.8 4.5 7.8 4.5 7.9L2.7 8.3 1 25.3 6.9 23.6 6.9 23.8 7 24 7.1 24.4 2.3 25.9 1 26.3 2.3 26.7 7.9 28.9 8 29 8 29 8 29 8.1 29 8.2 29 19.7 25.8 20.2 25.7 20.2 25.6 28.2 23.4 29 23.2ZM20.7 3C20.8 2.9 20.8 2.9 20.8 2.9 20.9 2.9 21 3 21.1 3.1 21.3 3.3 21.4 3.5 21.5 3.7 21.6 3.8 21.6 3.8 21.6 3.8L20.5 4.1C20.5 4.1 20.5 4 20.5 4 20.5 3.6 20.5 3.2 20.7 3ZM17 3.9C17 3.8 17 3.9 17 3.9 17.2 3.9 17.3 4 17.4 4.1 17.5 4.2 17.6 4.4 17.7 4.6 17.8 4.6 17.8 4.7 17.8 4.8L17.3 4.9 16.8 5C16.8 5 16.8 5 16.8 5 16.8 4.9 16.8 4.9 16.8 4.8 16.8 4.4 16.8 4.1 17 3.9ZM13 5.6C13 5.3 13.1 5 13.2 4.8 13.2 4.8 13.2 4.8 13.3 4.8 13.4 4.8 13.5 4.9 13.6 5 13.7 5.1 13.8 5.3 13.9 5.4 14 5.5 14 5.5 14 5.6 14 5.6 14.1 5.7 14.1 5.7L13 5.9C13 5.9 13 5.9 13 5.8 13 5.8 13 5.7 13 5.6ZM9.5 5.8C9.5 5.7 9.5 5.7 9.5 5.7 9.6 5.7 9.7 5.8 9.8 5.9 9.9 6 10 6.1 10.1 6.2 10.1 6.3 10.2 6.4 10.2 6.4 10.2 6.5 10.3 6.5 10.3 6.6 10.3 6.6 10.3 6.6 10.3 6.6L9.3 6.9C9.3 6.8 9.3 6.7 9.3 6.7 9.3 6.6 9.2 6.5 9.3 6.4 9.3 6.2 9.3 5.9 9.5 5.8ZM5.5 7.5C5.5 7.4 5.5 7.3 5.5 7.2 5.5 7 5.6 6.8 5.7 6.7 5.7 6.6 5.7 6.7 5.7 6.7 5.9 6.7 6 6.8 6.1 6.9 6.1 6.9 6.2 7 6.2 7.1 6.3 7.1 6.3 7.2 6.4 7.3 6.4 7.3 6.5 7.4 6.5 7.5L5.5 7.7C5.5 7.6 5.5 7.6 5.5 7.5ZM19.9 24.7L19.7 24.8 19.5 24.9 8.8 27.8 8.1 24.5 8 24.3 8 24.1 7.9 23.8 7.8 23.5 7.8 23.3 4.7 8.9 6.8 8.4C6.9 8.9 6.9 9.5 6.7 10 6.6 10.5 7.5 10.8 7.6 10.2 7.8 9.6 7.8 8.9 7.7 8.2L10.6 7.5C10.6 8 10.6 8.6 10.5 9 10.4 9.6 11.3 9.9 11.4 9.3 11.6 8.7 11.6 8 11.5 7.3L14.3 6.6C14.4 7.1 14.4 7.6 14.3 8.1 14.1 8.7 15 8.9 15.2 8.3 15.3 7.7 15.3 7 15.2 6.4L18.1 5.7C18.2 6.2 18.1 6.7 18 7.2 17.9 7.8 18.8 8 18.9 7.4 19.1 6.8 19.1 6.1 19 5.4L21.4 4.9 21.6 4.8 21.9 4.7C21.9 4.8 21.9 4.8 21.9 4.9 21.9 5.4 21.9 5.8 21.8 6.2 21.7 6.8 22.6 7.1 22.7 6.5 22.8 5.9 22.9 5.2 22.8 4.5L24 4.2 27.9 22.5 19.9 24.7ZM11.7 19.6L8.8 20.3 8.1 17.4 11 16.7 11.7 19.6ZM12.3 19.4L11.6 16.6 14.5 15.9 15.1 18.7 12.3 19.4ZM15.1 15.7L17.9 15 18.6 17.9 15.7 18.5 15.1 15.7ZM21.4 14.1L22.1 17 20.8 17.3 20.6 17.4 20.3 17.4 19.2 17.7 18.5 14.8 20.6 14.3 20.8 14.3 21.1 14.2 21.4 14.1ZM22 14L24.9 13.3 25.6 16.1 22.7 16.8 22 14ZM14.8 15.1L14.2 12.3 17 11.6 17.7 14.4 14.8 15.1ZM18.3 14.2L17.6 11.4 20.5 10.7 20.8 11.9 21 12.7 21.1 13.4 21.2 13.6 21.1 13.6 20.9 13.6 20.6 13.7 18.3 14.2ZM24 9.8L24.7 12.7 21.8 13.4 21.3 11.4 21.1 10.7 21.1 10.5 21.2 10.5 21.4 10.5 24 9.8ZM12.5 22.9L12.6 23 10.6 23.5 9.7 23.7 9.7 23.5 9.6 23.3 9.5 23 9.5 22.8 9 20.9 11.9 20.2 12.3 22 12.4 22.2 12.4 22.4 12.5 22.7 12.5 22.9ZM16 22L16 22.2 14.3 22.6 13.2 22.8 13.1 22.7 13.1 22.5 13 22.3 13 22 12.9 21.8 12.5 20 15.4 19.3 15.8 21 15.8 21.2 15.9 21.4 15.9 21.6 16 21.8 16 22ZM19.3 20.4L19.5 21.3 19 21.4 18.8 21.5 18.5 21.5 16.6 22 16.6 21.8 16.6 21.6 16.5 21.4 16.5 21.3 16.4 21 16.4 20.8 16 19.1 18.8 18.4 19.2 20 19.3 20.2 19.3 20.4Z"></path></svg></div>
			<div class="price">₪<?=number_format($unapproved["upSum"] ?: 0)?></div>
		</div>
		<?}?>
	</div>
	<?php if(count($_CURRENT_USER->sites()) == 1 && !$siteData['hasTerminal']) { ?>
		<div class="wanna-cc" onclick="window.location.href='?page=cc'">רוצים לסלוק באשראי דרך המערכת?<div>ליחצו כאן!</div></div>
	<?php } ?>
<?}?>

	<style>

</style>




<div class="luz-pop">
	<div class="pop-cont">
		<div class="close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
			<h1>הדפסת לו"ז יומי</h1>
<?php
			 if(!$_CURRENT_USER->single_site) {
?>
				<select name="luzsiteID">
					<option value="-1">כל בתי הספא</option>
<?php
                $allSites = udb::key_value("SELECT siteID,siteName FROM sites WHERE siteID IN (".$_CURRENT_USER->sites(true).")");
                foreach($allSites as $sid => $siteName) { ?>

						<option value="<?=$sid?>" <?=($sid == $_CURRENT_USER->select_site() ? 'selected' : '')?>><?=$siteName?></option>
<?php } ?>
			 	</select>
			<?php } ?>
	
			<input type="text" name="days" placeholder="תאריך" class="searchFrom" value="<?=date("d/m/Y")?>" readonly="">
			<?/*
			<select name="days">
				<option value="<?=date('Y-m-d', strtotime('-3 days'))?>">לפני  שלושה ימים - <?=$dayNames[date('D', strtotime('-3 days'))]?></option>
				<option value="<?=date('Y-m-d', strtotime('-2 days'))?>">לפני יומיים - <?=$dayNames[date('D', strtotime('-2 days'))]?></option>
				<option value="<?=date('Y-m-d', strtotime('-1 days'))?>">אתמול - <?=$dayNames[date('D', strtotime('-1 days'))]?></option>
				<option value="<?=date('Y-m-d')?>" selected>היום - <?=$dayNames[date('D')]?></option>
				<option value="<?=date('Y-m-d', strtotime('+1 days'))?>">מחר - <?=$dayNames[date('D', strtotime('+1 days'))]?></option>
				<option value="<?=date('Y-m-d', strtotime('+2 days'))?>">מחרתיים - <?=$dayNames[date('D', strtotime('+2 days'))]?></option>
				<option value="<?=date('Y-m-d', strtotime('+3 days'))?>">עוד שלושה ימים - <?=$dayNames[date('D', strtotime('+3 days'))]?></option>
			</select>
			*/?>
			<div id="printfr">הדפס
<svg width="8px" height="8px" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
  <path d="M2 0v2h4v-2h-4zm-1.91 3c-.06 0-.09.04-.09.09v2.81c0 .05.04.09.09.09h.91v-2h6v2h.91c.05 0 .09-.04.09-.09v-2.81c0-.06-.04-.09-.09-.09h-7.81zm1.91 2v3h4v-3h-4z" />
</svg></div>
			<iframe id="luzshow"></iframe>
	</div>
</div>

<script>
$(function() {
	$('.luz-pop .close').on('click', function() {
		$('.luz-pop').fadeOut('fast');
	});
	$('#printfr').on('click', function() {
		var iframe = $('#luzshow')[0]; iframe.contentWindow.focus(); iframe.contentWindow.print();
	});
	$('#today-luz').on('click', function() {
		$('.luz-pop').fadeIn('fast');
		$('input[name="days"]').trigger('change');
	});
	
	$('input[name="days"]').on('change', function() {
		let _siteval = $('select[name="luzsiteID"]').val();
		let _val = $(this).val();
		$('#luzshow').fadeIn('fast').attr('src', 'print_schedule.php?luz=1&day='+_val+'&siteID='+_siteval);
	})
	$('select[name="luzsiteID"]').on('change', function() {
		let _daysval = $('input[name="days"]').val();
		let _val = $(this).val();
		$('#luzshow').fadeIn('fast').attr('src', 'print_schedule.php?luz=1&day='+_daysval+'&siteID='+_val);
	})
})
</script>
<?if($next){?>
	 <div class="last-orders">
        <div class="title">מה הלו"ז</div>
        <div class="items">
		<style>
		
		</style>
            <?php 
			$cur_date = "";
			$nextCnt = 0;
			foreach($next as $order) {
				$nextCnt++;
                $order['paid'] = (new OrderSpaMain($order['orderID']))->get_paid_sum();
               
					if($cur_date != date('d-m-y', strtotime($order['timeFrom']))){
						$cur_date= date('d-m-y', strtotime($order['timeFrom']));
						?>
						<div class="day_line lineComp<?=$nextCnt?>" <?=$nextCnt>$nextLimitTop? "style='display:none'" : ""?>>יום <?=$weekday[date('w', strtotime($order['timeFrom']))]?>, <?=date('d', strtotime($order['timeFrom']))?> ב<?=$month_name[intval(date('m', strtotime($order['timeFrom'])))]?></div>
					<?}
					orderCompLine($order,$nextCnt);
				
					//orderComp($order);
				
            } ?>
			<div id="loadMoreTop">טען עוד</div>
        </div>
        
    </div>
<?}?>

<?php	
    if ($_CURRENT_USER->is_spa() && $_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN)) {?>
	<style>
		
	</style>
	<div class='dateTabs'>
		<div class="" style="font-weight:600;margin-bottom:10px;font-size:20px">טיפולים ביום זה</div>
	<?	for($iday = 0;$iday < 4 ; $iday++){
			$thisDate = date("d/m/y",strtotime("+". $iday ." days"));
			$thisDay = $dayNames[date("D",strtotime("+". $iday ." days"))];?>
			<div data-num='<?=$iday?>' class='dateTab <?=$iday==0? "selected" : ""?>'>יום <?=$thisDay?><br><?=$thisDate?></div>
			<?
		}
		?>
	</div>
	<div class='extras-wrapper'>
	<?
		
		for($iday = 0;$iday < 4 ; $iday++){
			//$thisDate = date("d/m/Y",strtotime("+". $iday ." days"));
			$thisDay = $dayNames[date("D",strtotime("+". $iday ." days"))];
			$thisDaySlot = date("Y-m-d",strtotime("+". $iday ." days"));
			?>
			<div data-num='<?=$iday?>' class="extras <?=!$iday? "show" : ""?>">
				<div class="extra"><span><?=$daily_treats[$thisDaySlot]['treat_count']?: "0"?></span><span>טיפולים ביום זה</span></div>
			</div>
			
		<?
		}
	?>
	</div>
	<div class='dateTabs'>
		<div class="" style="font-weight:600;margin-bottom:10px;font-size:20px">תוספים בתשלום</div>
	<?	for($iday = 0;$iday < 4 ; $iday++){
			$thisDate = date("d/m/y",strtotime("+". $iday ." days"));
			$thisDay = $dayNames[date("D",strtotime("+". $iday ." days"))];
			$thisDaySlot = date("Y-m-d",strtotime("+". $iday ." days"));
			
			?>
			<div data-num='<?=$iday?>' class='dateTab <?=$iday==0? "selected" : ""?>'>יום <?=$thisDay?><br><?=$thisDate?></div>
			<?
		}
		?>
	</div>
	<div class='extras-wrapper'>
	<?
		
		for($iday = 0;$iday < 4 ; $iday++){
			//$thisDate = date("d/m/Y",strtotime("+". $iday ." days"));
			$thisDay = $dayNames[date("D",strtotime("+". $iday ." days"))];
			$thisDaySlot = date("Y-m-d",strtotime("+". $iday ." days"));
			$hasbreakfast = (is_array($daily_ex[$thisDaySlot]) && array_key_exists(1686, $daily_ex[$thisDaySlot]))? 1 : 0;
			$hasVoucherPrints = (is_array($daily_print[$thisDaySlot]) )? 1 : 0;
			?>
			<div data-num='<?=$iday?>' class="extras <?=!$iday? "show" : ""?>">
					
			<?
			
			//print_r($daily_ex[[$thisDaySlot]]);
			if(is_array($daily_ex[$thisDaySlot])){
				foreach($daily_ex[$thisDaySlot] as $key => $ex){?>
					<div class="extra" data="<?=$key?>"><span><?=$ex?></span><span><?=$extrasNames[$key]?></span></div>
			<?
				}?>
				
			<div class="print_extras">
				<div class="printExtras" data-date='<?=$thisDaySlot?>'>פירוט והדפסה</div>
				<?if($hasVoucherPrints){?>
				<div class="printVouchers"  data-date='<?=$thisDaySlot?>'>שוברים להדפסה</div>
				<?}?>
			</div>
			<?}else{?>
				<span style="display:block;text-align:center">אין תוספים ליום זה</span>
			<?}
			?>
				
			</div>
			
		<?
		}
	?>
	</div>
<script>
$('.dateTab').on('click',function(){
	var tnum = $(this).data('num');	
	$('.dateTab').removeClass('selected');
	$('.extras').removeClass('show');
	$('.dateTab[data-num="'+ tnum +'"]').addClass('selected');
	$('.extras[data-num="'+ tnum +'"]').addClass('show');
})

$('.printExtras').click(function(){
	var _date = $(this).data('date');
	$.post('ajax_print_extras.php',{date:_date},function(res){		
		$("body").append(res._txt);
	})
});

$('.printVouchers').click(function(){
	var _date = $(this).data('date');
	$.post('ajax_print_vouchers.php',{date:_date},function(res){		
		$("body").append(res._txt);
	})
});
</script>
<?}?>	

	<div class="last-orders">
		<div class="title">הזמנות אחרונות</div>
		<div class="items">
<?php
    foreach($last as $order) {
        $order['paid'] = (new OrderSpaMain($order['orderID']))->get_paid_sum();

        orderComp($order);
    }
?>
		</div>
		<?if($_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN)){?>
        <div class="show-more"><a href="?page=orders">לכל ההזמנות</a></div>
		<?}?>
	</div>
    <div class="last-orders">
        <div class="title">אירועים קרובים</div>
        <div class="items">
		
            <?php 
			if ($_CURRENT_USER->is_spa()){
				$next = $nextBtm;
			}
			$cur_date = "";
			$nextCnt2 = 0;
			foreach($next as $order) {
				if($nextCnt2<$nextLimitBtm){
					$nextCnt2++;
					$order['paid'] = (new OrderSpaMain($order['orderID']))->get_paid_sum();
					orderComp($order);
				}
				
            } ?>
        </div>
		<?if($_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN)){?>
        <div class="show-more"><a href="?page=orders&from=<?=date('d/m/Y')?>">לכל האירועים</a></div>
		<?}?>
    </div>
</section>

<script>
var nextPos = <?=$nextLimitTop?>;
var nextAdd = <?=$nextLimitTop?>;
var nextTotal = <?=($nextCnt ?: 0)?>;

$('#loadMoreTop').click(function(){
	//debugger;
	for(i=nextPos+1;i<=nextPos + nextAdd;i++){
		$('.lineComp'+i).fadeIn('fast');
	}
	nextPos+=nextAdd;
	if(nextPos > nextTotal){
		$(this).fadeOut('fast');
	}

})

</script>
<?php
$_timer->log();
