<?php
require_once "auth.php";
$_timer = new BizTimer;
require_once "functions.php";

$orderID = intval($_POST['id']);
$orderParent = intval($_POST['parent']);


$result = new JsonResult;

function inDate($date){
    return preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[012])\/20\d{2}$/', trim($date)) ? implode('-', array_reverse(explode('/', trim($date)))) : null;
}

function inTime($time){
    return preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', trim($time)) ? substr(trim($time) . ':00', 0, 8) : null;
}

class CheckFail extends Exception {};

/*function canBookRooms($from, $till, $units, $orderID = 0){
    list($fromDate, $fromTime) = explode(' ', $from);
    list($tillDate, $tillTime) = explode(' ', $till);

    if (!strcmp($fromDate, $tillDate))
        $que = "SELECT COUNT(*) FROM `tfusa` WHERE tfusa.unitID " . (is_array($units) ? " in (" . implode(',', $units) . ")" : " = " . $units)." AND `date` = '" . $fromDate . "'
                    AND `hour` >= '" . $fromTime . "' AND `hour` < '" . $tillTime . "'" . ($orderID ? " AND `orderID` " . (is_array($orderID) ? " NOT IN (" . implode(',', $orderID) . ")" : " <> " . $orderID) : "");
    else
        $que = "SELECT COUNT(*) FROM `tfusa` WHERE tfusa.unitID ".(is_array($units) ? " in (" . implode(',', $units) . ")" : " = " . $units)."
		            AND ((`date` = '" . $fromDate . "' AND `hour` >= '" . $fromTime . "')
		              OR (`date` = '" . $tillDate . "' AND `hour` < '" . $tillTime . "')
		              OR (`date` < '" . $tillDate . "' AND `date` > '" . $fromDate . "')) " .
                ($orderID ? " AND `orderID` " . (is_array($orderID) ? " NOT IN (" . implode(',', $orderID) . ")" : " <> " . $orderID) : "");
    return !udb::single_value($que);
}

function addTfusaSpa($from, $to, $unitID, $orderID)
{
    $tz = date_default_timezone_get();
    date_default_timezone_set('UTC');
    $diff = abs((strtotime($to) - strtotime($from)) / 60);
    $timeUnits = ceil($diff / 15);
    for($i=0;$i<$timeUnits;$i++){
        udb::insert('tfusa',[
                'orderID' => $orderID,
                'unitID' => $unitID,
                'date' => date("Y-m-d", strtotime($from)+900*$i),
                'hour' => date("H:i:s", strtotime($from)+900*$i)
        ]);
    }
    date_default_timezone_set($tz);
}*/

// this function is a HACK to re-use existing ajax
function call_parent($act, $data){
    global $_CURRENT_USER, $result;

    $post = $_POST;                                         // saving original $_POST data
    $_POST = array_merge($data, ['action' => $act]);        // replacing with new data needed for parent order

    $preResult = $result;                                   // link to existing JsonResult
    $preValues = $preResult->value(true);                   // current data in JsonResult
    $preResult->flush();                                    // clearing JsonResult

    include "ajax_spaPlus.php";                             // calling parent order code

    $orderID = $result['orderID'];                          // pulling orderID
    $error   = $result['error'];                            // pulling error

    $result->flush();                                       // clearing data from ajax_spaPlus
    $result = $preResult;                                   // restoring previous JsonResult object
    foreach($preValues as $key => $val)                     // restoring previous values
        $result[$key] = $val;

    $_POST = $post;                                         // restoring original $_POST

    return ['orderID' => $orderID, 'error' => $error];
}

try {
//    if (!$orderID && !$orderParent)
//        throw new Exception('Internal error: no ID and no parent');

    
	if($_POST['act'] == "moveSpaSingle"){
		$order = udb::single_row("SELECT `orders`.*, `orderUnits`.`unitID` FROM `orders` LEFT JOIN `orderUnits` USING(`orderID`) WHERE `orderID` = " . intval($_POST['id']));
		if($order){
			$startDate = implode("/",array_reverse(explode("-",explode(" ",$_POST['slotstart'])[0])));
			$startTime = strtotime(explode(" ",$_POST['slotstart'])[1]);
			$startTime = date("H:i", strtotime('+'.intval($_POST['minutes']).' minutes', $startTime)); 
			$result['time'] = $startTime;
			$therapist = intval($_POST['tsid']);
			$_POST = [
			'act'			=> 'saveSpaSingle',	
			/*'sourceID'    => 'string',
			'settlementID'  => 'int',
			'clientAddress' => 'string',*/
			'name'        => $order['customerName'],
			'malefemale'  => $order['treatmentClientSex'],
			'phone'       => $order['customerPhone'],
			'treatmentID' => $order['treatmentID'],
			'duration'    => $order['treatmentLen'],
			'tmalefemale' => $order['treatmentMasterSex'],
			'lockedTherapist' => $order['lockedTherapist'],
			'startDate'   => $startDate,
			'startTime'   => $startTime,
			'therapist'   => $therapist,
			'roomID'      => $order['unitID'],
			'cleanTime'  =>  $order['cleanTime']
			];

			if($order['lockedTherapist'] && $order["therapistID"]!=$therapist)
				throw new Exception("לא ניתן להחליף מטפל/ת נעול/ה");	

		}else{
			throw new Exception("לא אותרה הזמנה");
		}
$_timer->log();
	}
	
	$user_log = new UserActionLog($_CURRENT_USER);
$_timer->log();
    switch($_POST['act']){
        case 'saveSpaSingle':
            $input = typemap($_POST, [
                'sourceID'    => 'string',
                'settlementID'  => 'int',
                'clientAddress' => 'string',
                'name'        => 'string',
                'malefemale'  => 'int',
                'phone'       => 'numeric',
                'treatmentID' => 'int',
                'duration'    => 'int',
                'tmalefemale' => 'int',
                'lockedTherapist' => 'int',
                'startDate'   => 'inDate',
                'startTime'   => 'inTime',
                'therapist'   => 'int',
                'roomID'      => 'int',
                '!cleanTime'  => 'int',
                'pdata'       => 'string',
                'comments_customer'    => 'text',
                'carry'       => 'string'
            ]);

            // completion checks
			if (!$input['malefemale'])
                throw new Exception("יש לבחור מין מטופל");
            if (!$input['treatmentID'])
                throw new Exception("יש לבחור סוג טיפול");
            if (!$input['startDate'])
                throw new Exception("יש לבחור תאריך לטיפול");
            if (!$input['startTime'])
                throw new Exception("יש לבחור שעה לטיפול");
            if (!$input['duration'])
                throw new Exception("זמן טיפול לא חוקי : 0");
            if (!$input['therapist'])
                throw new Exception("יש לבחור מטפל/ת");

			//$preg = "/^(\d(\W*)){9,12}$/";

			$match = preg_replace('/\D+/', '', $input['phone']);
			if ($input['phone'] && (!$match || strlen($match) < 9 || strlen($match) > 12))
                throw new Exception('טלפון לא תקין');

            // collecting order data
            $orderData = [
                'customerName'       => $input['name'],
                'customerPhone'      => $input['phone'],
                'treatmentID'        => $input['treatmentID'] ?? 0,
                'treatmentLen'       => $input['duration'] ?? 0,
                'treatmentClientSex' => $input['malefemale'] ?? 0,
                'treatmentMasterSex' => $input['tmalefemale'] ?? 0,
                'lockedTherapist'	 => $input['lockedTherapist'] ?? 0,
                'therapistID'        => $input['therapist'],
                'cleanTime'          => $input['cleanTime'],
                'comments_customer'  => $input['comments_customer'],
                'timeFrom'           => ($input['startDate'] && $input['startTime']) ? $input['startDate'] . ' ' . $input['startTime'] : ''
            ];

            // getting correct $siteID and $orderParent values
            if ($orderID){
                $preOrder = udb::single_row("SELECT * FROM `orders` WHERE `orderID` = " . $orderID);
                if (!$preOrder)
                    throw new Exception("Cannot find order #" . $orderID);	

                $orderParent = $preOrder['parentOrder'];
                $siteID = $preOrder['siteID'];
$_timer->log();
            }			
            elseif ($orderParent) {
                $siteID = udb::single_value("SELECT `siteID` FROM `orders` WHERE `orderID` = " . $orderParent);
                if (!$siteID)
                    throw new Exception("Cannot find order #" . $orderParent);
            }
            elseif ($input['pdata']){
                $pdata = [];
                parse_str(base64_decode($input['pdata']), $pdata);

                $siteID = intval($pdata['orderSite']);
                if (!$siteID)
                    throw new Exception ("לא נבחר מתחם להזמנה1");
                $pdata["sourceID"] = $input['sourceID'];
                $pdata["clientAddress"] = $input['clientAddress'];
                $pdata["settlementID"] = $input['settlementID'];

                $input['pdata'] = $pdata;
$_timer->log();
                //--- no $orderParent yet !!! ---
            }
            else
                throw new Exception('Internal error: no ID and no parent');

            // checking that user has access to this $siteID
            if (!$_CURRENT_USER->has($siteID))
                throw new Exception("גישה נדחתה לעסק מספר #" . $siteID);

            $siteData = udb::single_row("SELECT * FROM sites WHERE siteID = " . $siteID);

            // setting correct times for booking
            if ($orderData['timeFrom']){
                $orderData['timeUntil'] = date('Y-m-d H:i:s', strtotime($orderData['timeFrom'] . ' +' . ($orderData['treatmentLen'] + $input['cleanTime']). ' minutes'));
                $orderData['showTimeFrom'] = $orderData['timeFrom'];
                $orderData['showTimeUntil'] = $orderData['timeUntil'];
            } else {
                $orderData['timeFrom'] = $orderData['timeUntil'] = $orderData['showTimeFrom'] = $orderData['showTimeUntil'] = '0000-00-00 00:00:00';
            }
$_timer->log();
            // checking therapist acceptability
            if ($orderData['therapistID']){
                $ts = udb::single_row("SELECT * FROM `therapists` WHERE `active` = 1 AND `deleted` = 0 AND `therapistID` = " . $orderData['therapistID'] . " AND `siteID` = " . $siteID . " AND (`workStart` IS NULL OR `workStart` <= '" . $input['startDate'] . "') AND (`workEnd` IS NULL OR `workEnd` >= '" . $input['startDate'] . "')");   // ts = therapist status
                if (!$ts)
                    throw new Exception("לא ניתן לאתר את המטפל הנבחר");

                if ($ts['gender_self'] != 3 && $input['tmalefemale'] && !($ts['gender_self'] & $input['tmalefemale']))
                    throw new Exception("מין המטפל לא מתאים לבחירה ");
                if ($ts['workerType'] != 'fictive' && !($ts['gender_client'] & $input['malefemale']))
                    throw new Exception("המטפל אינו מטפל ב " . ($input['malefemale'] == 1 ? 'גברים' : 'נשים'));

                if($ts['workerType'] != 'fictive' && $orderData['treatmentID']){
                    // checking if treatment type acceptable
                    $que = "SELECT COUNT(*) FROM `therapists_treats` WHERE `therapistID` = " . $orderData['therapistID'] . " AND `treatmentID` = " . $orderData['treatmentID'];
                    if (!udb::single_value($que))
                        throw new CheckFail('המטפל לא מבצע את הטיפול הנבחר');
                }
            }
$_timer->log();
            if ($input['roomID']){
                // must have start time
                if (!$input['startDate'] || !$input['startTime'])
                    throw new Exception("לא ניתן לבחור חדר ללא בחירת תאריך ושעה");

                $sid = udb::single_value("SELECT `siteID` FROM `rooms_units` INNER JOIN `rooms` USING(`roomID`) WHERE `unitID` = " . $input['roomID']);
                if (!$sid || !$_CURRENT_USER->has($sid))
                    throw new Exception("לא ניתן לבחור חדר, גישה חסומה לעסק מספר #" . $sid);
                if ($sid != $siteID)
                    throw new Exception("שגיאת מערכת - החדרים או הטיפול אינם שייכים לעסק מספר #" . $sid);
            }
            elseif ($siteData['roomRequired'])
                throw new Exception("חייב בחירת חדר לטיפול");
$_timer->log();
            // checking therapist availability
            $que = "SELECT `orders`.`orderID`, `orders`.`timeFrom`, `orders`.`timeUntil` 
			FROM `orders` 
			LEFT JOIN `orders` AS `parent` ON (`orders`.`parentOrder` = `parent`.`orderID`)
                            WHERE `orders`.`parentOrder` > 0 AND `orders`.`therapistID` = " . $orderData['therapistID'] . " AND `orders`.`timeFrom` < '" . $orderData['timeUntil'] . "' AND `orders`.`timeUntil` > '" . $orderData['timeFrom'] . "' AND `parent`.`status` = 1 AND `orders`.`orderID` <> " . $orderID . " LIMIT 1";
            $busy = udb::single_row($que);
            if ($busy)
                throw new Exception("This therapist is already booked from " . $busy['timeFrom'] . " till " . $busy['timeUntil']);
$_timer->log($que);
			//checking therapist's breaks colision
            $que = "SELECT COUNT(*) FROM `spaShifts` WHERE `status` = 0 AND `masterID` = " . $orderData['therapistID'] . " AND '" . $orderData['timeUntil'] . "' > `timeFrom` AND '" . $orderData['timeFrom'] . "' < `timeUntil`";
            if (udb::single_value($que))
                throw new CheckFail('למטפל קיימת הפסקה בשעה זו');
$_timer->log();
            // if no parent order created yet - now is the time to create it
            if (!$orderParent){
                $temp = call_parent('insertOrder', $input['pdata']);
                if (!$temp['orderID'] || $temp['error'])
                    throw new Exception($temp['error'] ?: $temp['_txt'] ?: 'אין אפשרות ליצור הזמנת אב');

                $orderParent = intval($temp['orderID']);
            }
$_timer->log();
            $linkedOrders = udb::single_column("SELECT `orderID` FROM `orders` WHERE `orderID` <> `parentOrder` AND `parentOrder` = " . $orderParent);
            $tfusaSpa = new TfusaSpa;

            // pulling late hour fee
            list($lateLimit, $latePrice) = udb::single_row("SELECT `ExtraPriceAfterTime`, `ExtraPriceAfterPrice` FROM `sites` WHERE `siteID` = " . $siteID, UDB_NUMERIC);

            // checking treatment and getting it's price for duration
            if ($input['treatmentID'] && $input['duration']){
                list($price1, $price2, $price3) = udb::single_row("SELECT `price1`, `price2`, `price3` FROM `treatmentsPricesSites` WHERE `siteID` = " . $siteID . " AND `treatmentID` = " . $input['treatmentID'] . " AND `duratuion` = " . $input['duration'], UDB_NUMERIC);
                //if (!$price1 && $price1 != 0)
                //throw new Exception("לא אותר מחיר לטיפול");//THIS CONDITION NEED TO BE CHECKED WITH ROY

                $rc = count($linkedOrders) + ($orderID ? 0 : 1);
                $orderData['price'] = ($rc > 2) ? ($price3 ?: $price2 ?: $price1) : (($rc == 2) ? ($price2 ?: $price1) : $price1);

                // adding extra price per weekday and late hours
                if (strcmp($orderData['timeFrom'], '0000-00-00 00:00:00')){
                    //$orderData['price'] += udb::single_value("SELECT `extraPrice` FROM `sites_weekly_hours` WHERE `siteID` = " . $siteID . " AND `weekday` = " . date('w', strtotime($orderData['timeFrom']))) ?: 0;

                    // selecting extra price for date/weekday
                    $que = "SELECT s.extraPrice 
                            FROM `sites_weekly_hours` AS `s` LEFT JOIN `sites_periods` AS `p` ON (p.periodID = -s.holidayID AND '" . substr($orderData['timeFrom'], 0, 10) . "' BETWEEN p.dateFrom AND p.dateTo) 
                            WHERE s.siteID = " . $siteID . " AND `weekday` = " . date('w', strtotime($orderData['timeFrom'])) . "
                                AND (s.holidayID = 0 OR p.periodID IS NOT NULL)
                            ORDER BY s.holidayID
                            LIMIT 1";
                    $extraDay = udb::single_value($que);

                    // checking if there's late hour fee and adding it if applicable
                    if ($lateLimit && $latePrice && strcmp(substr($orderData['timeFrom'], 11, 5), $lateLimit) >= 0)
                        $extraTime = $latePrice;

                    $orderData['price'] += ($extraDay ?: 0) + ($extraTime ?? 0);
                }
            }
            else
                $orderData['price'] = 0;
$_timer->log();
            // checking room availability
            if ($input['roomID']){
                //udb::query("LOCK TABLES `tfusa` WRITE, `orders` WRITE, `orderUnits` WRITE, `rooms_units` READ");
$_timer->log();
                if (!$tfusaSpa->can_book($input['roomID'], $orderData['timeFrom'], $orderData['timeUntil'], array_merge([$orderParent], $linkedOrders)))
                    throw new Exception("Cannot book room - already booked");
$_timer->log();
            }

$_timer->log();
            $updateSiblings = false;
            $updateExtras   = false;

            if ($orderID){
                // selecting fields influencing price
                $priceable = ['treatmentID', 'treatmentLen', 'timeFrom'];
                $oldVals   = udb::single_row("SELECT `" . implode('`,`', $priceable) . "` FROM `orders` WHERE `orderID` = " . $orderID);
                $newVals   = array_intersect_key($orderData, array_combine($priceable, $priceable));

                // if no changes in those fields - ignore price change
                if ($newVals == $oldVals)
                    unset($orderData['price']);
                elseif (strcmp($newVals['timeFrom'], $oldVals['timeFrom']))
                    $updateExtras = true;

                udb::update('orders', $orderData, "`orderID` = " . $orderID);
$_timer->log();
                udb::query("DELETE FROM `orderUnits` WHERE `orderID` = " . $orderID);
                $tfusaSpa->clean_order($orderID);
$_timer->log();
            }
            else {
                $orderData['parentOrder'] = $orderParent;
                $orderData['siteID']      = $siteID;
                $orderData['guid']        = GUID();

                $orderID = udb::insert('orders', $orderData);
                $linkedOrders[] = $orderID;

                $updateSiblings = $updateExtras = true;
$_timer->log();
            }

            $status = udb::single_value("SELECT `status` FROM `orders` WHERE `orderID` = " . $orderID);

            if ($input['roomID']){
                udb::query("INSERT INTO `orderUnits`(`orderID`, `unitID`, `extraRoomName`)
                                SELECT '" . $orderID . "', `unitID`, `unitName` FROM `rooms_units` WHERE `unitID` = " . $input['roomID']);

                if ($status == 1)
                    $tfusaSpa->book($input['roomID'], $orderData['timeFrom'], $orderData['timeUntil'], $orderID);
            }

            udb::query("UNLOCK TABLES");
$_timer->log();
            // adding customer phone and name in main order if empty
            if ($orderData['customerPhone'])
                udb::query("UPDATE `orders` SET `customerPhone` = '" . udb::escape_string($orderData['customerPhone']) . "' WHERE `customerPhone` = '' AND `orderID` = " . $orderParent);
            if ($orderData['customerName'])
                udb::query("UPDATE `orders` SET `customerName` = '" . udb::escape_string($orderData['customerName']) . "' WHERE `customerName` = '' AND `orderID` = " . $orderParent);
$_timer->log();
            // checking if there's need to change other orders
            if (count($linkedOrders) > 1 && $updateSiblings){
                $rc = count($linkedOrders);

                $que = "SELECT orders.orderID, orders.timeFrom, t.*, IFNULL(b.extraPrice, a.extraPrice) AS `extraPrice` 
                        FROM `orders` INNER JOIN `treatmentsPricesSites` AS `t` USING(`siteID`, `treatmentID`)
                            LEFT JOIN `sites_weekly_hours` AS `a` ON (a.siteID = orders.siteID AND orders.timeFrom <> '0000-00-00 00:00:00' AND a.holidayID = 0 AND a.weekday = DAYOFWEEK(orders.timeFrom) - 1)
                            LEFT JOIN `sites_periods` AS `p` ON (p.siteID = orders.siteID AND orders.timeFrom <> '0000-00-00 00:00:00' AND DATE(orders.timeFrom) BETWEEN p.dateFrom AND p.dateTo)
                            LEFT JOIN `sites_weekly_hours` AS `b` ON (b.siteID = p.siteID AND b.holidayID = -p.periodID AND a.weekday = DAYOFWEEK(orders.timeFrom) - 1)
                        WHERE orders.treatmentLen = t.duratuion AND orders.orderID IN (" . implode(',', $linkedOrders) . ")";
                $orders = udb::key_row($que, 'orderID');
$_timer->log();
                foreach($orders as $orid => $order){
                    $extraTime = ($lateLimit && $latePrice && strcmp(substr($order['timeFrom'], 11, 5), $lateLimit) >= 0) ? $latePrice : 0;
                    udb::update('orders', ['price' => (($rc == 2) ? ($order['price2'] ?: $order['price1']) : ($order['price3'] ?: $order['price2'] ?: $order['price1'])) + ($order['extraPrice'] ?: 0) + $extraTime], '`orderID` = ' . $orid);
                }
            }
$_timer->log();

/*            if ($updateExtras){
                $extras  = udb::single_value("SELECT `extras` FROM `orders` WHERE `orderID` = " . $orderParent);
                if ($extras && ($extras = json_decode($extras, true)) && $extras['extras']){
                    $eprices  = udb::key_row("SELECT * FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) WHERE s.siteID = " . $siteID . " AND s.active = 1", 'extraID');
                    $baseDate = udb::single_value("SELECT DATE(MIN(`timeFrom`)) FROM `orders` WHERE `parentOrder` = " . $orderParent . " AND `orderID` <> `parentOrder` AND `status` = 1");

                    $que = "SELECT IFNULL(b.isWeekend2, a.isWeekend2) AS `isWeekend2`
                            FROM `sites_weekly_hours` AS `a` 
                                LEFT JOIN `sites_periods` AS `sp` ON (sp.siteID = a.siteID AND sp.periodType = 0 AND sp.dateFrom <= '" . $baseDate . "' AND sp.dateTo >= '" . $baseDate . "')
                                LEFT JOIN `sites_weekly_hours` AS `b` ON (b.siteID = a.siteID AND b.holidayID = -sp.periodID AND b.weekday = a.weekday AND b.active = 1)
                            WHERE a.holidayID = 0 AND a.siteID = " . $siteID . " AND a.weekday = " . date('w', strtotime($baseDate));
                    $isWeekend2 = udb::single_value($que);

                    $eTotal = 0;
                    foreach($extras['extras'] as $i => &$extra){
                        if (!$extra)
                            continue;
                        if (empty($eprices[$i]))
                            continue;

                        $prs = $eprices[$i];

                        if ($prs['extraType'] == 'package'){
                            $pin = min(3, max(1, count($linkedOrders)));
                            $extra['price'] = $price = ($prs['price' . $pin] ?: $prs['price' . ($pin - 1)] ?: $prs['price1']) + ($isWeekend2 ? $prs['priceWE'] : 0);
                        }
                        elseif ($prs['extraType'] == 'rooms'){
                            if ($extra['forNight']){     // overnight
                                $extra['price'] = $price = $prs['price3'] + ($isWeekend2 ? $prs['priceWE3'] : 0);
                            }
                            elseif (!empty($extra['extraHours'])){     // extra hours
                                $extra = array_merge($extra, [
                                    'basePrice'  => $prs['price1'] + ($isWeekend2 ? $prs['priceWE'] : 0),
                                    'hourPrice'  => $prs['price2'] + ($isWeekend2 ? $prs['priceWE2'] : 0)
                                ]);

                                $extra['price'] = $price = $extra['basePrice'] + $extra['hourPrice'] * $extra['extraHours'];
                            }
                            else {     // base price
                                $extra['price'] = $price = $prs['price1'] + ($isWeekend2 ? $prs['priceWE'] : 0);
                            }
                        }
                        else
                            $extra['price'] = $price = $prs['price1'] + ($isWeekend2 ? $prs['priceWE'] : 0);

                        $eTotal += $extra['count'] * $price;
                    }
                    unset($extra);

                    $extras['total'] = $eTotal;

                    udb::update('orders', ['extras' => json_encode($extras, JSON_NUMERIC_CHECK)], "`orderID` = " . $orderParent);
                }
            }
*/

$_timer->log();
            (new OrderSpaMain($orderParent))->updatePrice($updateExtras);
$_timer->log();
			if ($input['carry'] && ($carry = json_decode($input['carry'], true))){
                if ($carry['stID']){
                    udb::query("LOCK TABLES `subscriptions` READ, `subscriptionTreatments` WRITE, `orderPayments` WRITE");

                    $que = "SELECT * 
                            FROM `subscriptions` INNER JOIN `subscriptionTreatments` USING(`subID`) 
                            WHERE subscriptions.active = 1 AND subscriptionTreatments.stID = " . $carry['stID'];
                    $treat = udb::single_row($que);

                    if (!$treat)
                        $result['warning'] = "Subscription " . $carry['stID'] . " is inactive or doesn't exist.";
                    else if ($orderData['siteID'] && $treat['siteID'] != $orderData['siteID'])
                        $result['warning'] = "Subscription " . $carry['stID'] . " belongs to different owner.";
                    else if ($treat['payID'])
                        $result['warning'] = "This treatment was already used";
                    else {
                        $lineID = udb::insert("orderPayments", [
                            'payType'    => 'member2',
                            'orderID'    => $orderParent,
                            'buserID'    => $_CURRENT_USER->id(),
                            'startTime'  => date('Y-m-d H:i:s'),
                            'endTime'    => date('Y-m-d H:i:s'),
                            'provider'   => 'BizOnline',
                            'complete'   => 1,
                            'sum'        => $treat['price'],
                            'invoice'    => '-',
                            'inputData'  => json_encode(['type' => 1, 'mbr' => $treat['subNumber'], 'via' => 'member2', 'sum' => $treat['price']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                            'resultData' => json_encode(['success' => true, 'subID' => $treat['subID'], 'stID' => $treat['stID']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                        ]);

                        udb::update('subscriptionTreatments', ['payID' => $lineID], "`stID` = " . $treat['stID']);
                    }
$_timer->log();
                    udb::query("UNLOCK TABLES");
                }
            }
$_timer->log();

            $result['success'] = 'טיפול הוזמן בהצלחה';
            $result['orderID'] = $orderID;
            $result['parent']  = $orderParent;
            break;

        case 'freeFictive':
            if (!$orderID)
                throw new Exception("Missing orderID");

            $order = udb::single_row("SELECT * FROM `orders` WHERE `parentOrder` > 0 AND `parentOrder` <> `orderID` AND `orderID` = " . $orderID);
            if (!$order)
                throw new Exception("Cannot find order " . $orderID);

            $siteID = $order['siteID'];
            if (!$_CURRENT_USER->has($siteID))
                throw new Exception("Access denied to order #" . $orderID);
$_timer->log();
            $dateOnly = substr($order['timeFrom'], 0, 10);
            $que = "SELECT COUNT(DISTINCT t.therapistID)
                    FROM `therapists` AS `t`
                        " . ($order['treatmentID'] ? "LEFT JOIN `therapists_treats` AS `tt` ON (t.workerType <> 'fictive' AND tt.therapistID = t.therapistID AND tt.treatmentID = " . $order['treatmentID'] . ")" : "") . "
                        LEFT JOIN `spaShifts` AS `ss` ON (ss.timeFrom < '" . $order['timeUntil'] . "' AND ss.timeUntil > '" . $order['timeFrom'] . "' AND ss.masterID = t.therapistID AND ss.status <> 1)
                        LEFT JOIN `spaShifts` AS `sp` ON (sp.timeFrom <= '" . $order['timeFrom'] . "' AND sp.timeUntil >= '" . $order['timeUntil'] . "' AND sp.masterID = t.therapistID AND sp.status = 1)
                        LEFT JOIN `orders` AS `o` ON (t.therapistID = o.therapistID AND o.siteID = " . $siteID . " AND o.parentOrder > 0 AND o.status = 1 AND '" . $order['timeUntil'] . "' > o.timeFrom AND '" . $order['timeFrom'] . "' < o.timeUntil)
                        LEFT JOIN `orders` AS `p` ON (o.parentOrder = p.orderID AND p.status = 1)
                    WHERE t.siteID = " . $siteID . " AND t.active = 1 AND t.deleted = 0 AND (t.workStart IS NULL OR t.workStart <= '" . $dateOnly . "') AND (t.workEnd IS NULL OR t.workEnd >= '" . $dateOnly . "') 
                        AND ss.orderID IS NULL AND p.orderID IS NULL" . ($order['treatmentMasterSex'] ? " AND (t.gender_self & " . $order['treatmentMasterSex'] . ")" : "") . "
                        AND (t.workerType = 'fictive' OR (sp.orderID IS NOT NULL AND " . ($order['treatmentClientSex'] ? "(t.gender_client & " . $order['treatmentClientSex'] . ")" : "1") . " AND " . ($order['treatmentID'] ? "tt.treatmentID IS NOT NULL" : "1") . "))";

            /*$que = "SELECT COUNT(*)
                    FROM `therapists` AS `t` LEFT JOIN `orders` AS `o` ON (t.therapistID = o.therapistID AND t.siteID = o.siteID AND o.parentOrder > 0 AND '" . $order['timeUntil'] . "' > o.timeFrom AND '" . $order['timeFrom'] . "' < o.timeUntil AND o.status = 1)
                        LEFT JOIN `orders` AS `p` ON (o.parentOrder = p.orderID AND p.status = 1)
                    WHERE t.siteID = " . $siteID . " AND t.active = 1 AND t.deleted = 0 AND (t.workStart IS NULL OR t.workStart <= '" . $dateOnly . "') AND (t.workEnd IS NULL OR t.workEnd >= '" . $dateOnly . "') AND t.workerType = 'fictive' AND p.orderID IS NULL";*/
            $frees = udb::single_value($que);
$_timer->log($que);
            $select = '<div class="dup-select" style="margin-left:60px"><select>' . implode('', array_map(function($i){return '<option value=' . $i . '>' . $i . '</option>';}, range(1, $frees))) . '<select></div>';

            $result['max']  = $frees ?: 0;
            $result['html'] = $frees ? '<span>שיכפול עד ' . $frees . ' פעמים בשעה ' . substr($order['timeFrom'], 11,5) . '</span>' . $select : '<span style="color:red">אין מטפלים פנויים בזמן המבוקש.</span>';

            $copy = OrderCopy::createFromOrder($orderID);
            $groupC = $copy->countEmptySlots();
$result['gc'] = $groupC;
            $select2 = [];
            if ($groupC['count']){
                $sts = strtotime($groupC['first'][0]);
                $ets = strtotime($groupC['last'][0]);

                $min = ($sts % 3600);
                $sts = $sts - $min + ceil(round($min / 900, 3)) * 900;

                for(; $sts <= $ets; $sts += 900)
                    $select2[] = '<option value="' . $sts . '">' . date('H:i', $sts) . '</option>';
            }

            $select = '<div class="dup-select"><select name="cpg_start" style="width:85px;margin-left:5px">' . implode('', $select2) . '</select><select name="cpg_num">' . implode('', array_map(function($i){return '<option value=' . $i . '>' . $i . '</option>';}, range(1, $groupC['count'] ?: 0))) . '<select></div>';

            $result['groupMax'] = $groupC['count'];
            $result['groupHTML'] = $groupC['count'] ? '<span>שכפול גמיש, בחרו שעת תחילה וכמות</span>' . $select . '<span>טיפול אחרון בשעה ' . substr($groupC['last'][0], 11,5) . '-' . substr($groupC['last'][1], 11,5) . '</span>' : '<span style="color:red">אין מטפלים פנויים ביום המבוקש.</span>';

            $result['select'] = '';         // custom selector for amoutn <select> in html, if there are more than one or isn't "select" tag.
            $result['success'] = true;
$_timer->log();
            break;

        case 'copyOrder':
        case 'copyGroup':
            if (!$orderID)
                throw new Exception("Missing orderID");

            $order = udb::single_row("SELECT * FROM `orders` WHERE `parentOrder` > 0 AND `parentOrder` <> `orderID` AND `orderID` = " . $orderID);
            if (!$order)
                throw new Exception("Cannot find order " . $orderID);

            $siteID = $order['siteID'];
            if (!$_CURRENT_USER->has($siteID))
                throw new Exception("Access denied to order #" . $orderID);

            $count = intval($_POST['mult'] ?? $_POST['cpg_num']);
            if ($count <= 0)
                throw new Exception("Illegal count value: " . $count);

            $dateOnly = substr($order['timeFrom'], 0, 10);
$_timer->log();
            if ($_POST['act'] == 'copyGroup'){
                $minTime = intval($_POST['cpg_start']);

                $copy  = OrderCopy::createFromOrder($orderID);
                $slots = $copy->prepareEmptySlots($minTime);
$_timer->log();
                if (count($slots) < $count)
                    throw new Exception("Not enough available slots: need - " . $count . ", availalbe - " . count($slots));

                $remove = ['orderID', 'approved', 'create_date', 'signature', 'file', 'sign_time', 'contract_time', 'SentReview', 'reviewSmsSentDate', 'reviewEmailSentDate', 'mail_sent', 'healthMailSent', 'review_mail_sent', 'order_mail_bymail', 'order_mail_bysms','treatmentMasterSex'];
                $order = array_diff_key($order, array_combine($remove, $remove));

                for($i = 0; $i < $count; ++$i){
                    $order['guid'] = GUID();
                    $order['therapistID'] = $slots[$i]['master'];
                    $order['timeFrom']    = $order['showTimeFrom'] = date('Y-m-d H:i:00', $slots[$i]['from']);
                    $order['timeUntil']   = $order['showTimeUntil'] = date('Y-m-d H:i:00', $slots[$i]['till']);
                    udb::insert('orders', $order);
                }
$_timer->log();
            }
            else {          // copyOrder
                $que = "SELECT DISTINCT t.therapistID
                        FROM `therapists` AS `t`
                            " . ($order['treatmentID'] ? "LEFT JOIN `therapists_treats` AS `tt` ON (t.workerType <> 'fictive' AND tt.therapistID = t.therapistID AND tt.treatmentID = " . $order['treatmentID'] . ")" : "") . "
                            LEFT JOIN `spaShifts` AS `ss` ON (ss.timeFrom < '" . $order['timeUntil'] . "' AND ss.timeUntil > '" . $order['timeFrom'] . "' AND ss.masterID = t.therapistID AND ss.status <> 1)
                            LEFT JOIN `spaShifts` AS `sp` ON (sp.timeFrom <= '" . $order['timeFrom'] . "' AND sp.timeUntil >= '" . $order['timeUntil'] . "' AND sp.masterID = t.therapistID AND sp.status = 1)
                            LEFT JOIN `orders` AS `o` ON (t.therapistID = o.therapistID AND o.siteID = " . $siteID . " AND o.parentOrder > 0 AND o.status = 1 AND '" . $order['timeUntil'] . "' > o.timeFrom AND '" . $order['timeFrom'] . "' < o.timeUntil)
                            LEFT JOIN `orders` AS `p` ON (o.parentOrder = p.orderID AND p.status = 1)
                        WHERE t.siteID = " . $siteID . " AND t.active = 1 AND t.deleted = 0 AND (t.workStart IS NULL OR t.workStart <= '" . $dateOnly . "') AND (t.workEnd IS NULL OR t.workEnd >= '" . $dateOnly . "') 
                            AND ss.orderID IS NULL AND p.orderID IS NULL" . ($order['treatmentMasterSex'] ? " AND (t.gender_self & " . $order['treatmentMasterSex'] . ")" : "") . "
                            AND (t.workerType = 'fictive' OR (sp.orderID IS NOT NULL AND " . ($order['treatmentClientSex'] ? "(t.gender_client & " . $order['treatmentClientSex'] . ")" : "1") . " AND " . ($order['treatmentID'] ? "tt.treatmentID IS NOT NULL" : "1") . "))
                        ORDER BY IF(t.workerType = 'fictive', 1, 0)";

    /*            $que = "SELECT t.therapistID
                        FROM `therapists` AS `t` LEFT JOIN `orders` AS `o` ON (t.therapistID = o.therapistID AND t.siteID = o.siteID AND o.parentOrder > 0 AND '" . $order['timeUntil'] . "' > o.timeFrom AND '" . $order['timeFrom'] . "' < o.timeUntil AND o.status = 1)
                            LEFT JOIN `orders` AS `p` ON (o.parentOrder = p.orderID AND p.status = 1)
                        WHERE t.siteID = " . $siteID . " AND t.active = 1 AND t.deleted = 0 AND (t.workStart IS NULL OR t.workStart <= '" . $dateOnly . "') AND (t.workEnd IS NULL OR t.workEnd >= '" . $dateOnly . "') AND t.workerType = 'fictive' AND p.orderID IS NULL";
    */
                $frees = udb::single_column($que);
$_timer->log();
                if (count($frees) < $count)
                    throw new Exception("Not enough free masters: need - " . $count . ", free - " . count($frees));

                $remove = ['orderID', 'approved', 'create_date', 'signature', 'file', 'sign_time', 'contract_time', 'SentReview', 'reviewSmsSentDate', 'reviewEmailSentDate', 'mail_sent', 'healthMailSent', 'review_mail_sent', 'order_mail_bymail', 'order_mail_bysms','treatmentMasterSex'];
                $order = array_diff_key($order, array_combine($remove, $remove));

                for($i = 0; $i < $count; ++$i){
                    $order['guid'] = GUID();
                    $order['therapistID'] = array_pop($frees);
                    udb::insert('orders', $order);
                }
$_timer->log();
            }

            // pulling late hour fee
            list($lateLimit, $latePrice) = udb::single_row("SELECT `ExtraPriceAfterTime`, `ExtraPriceAfterPrice` FROM `sites` WHERE `siteID` = " . $siteID, UDB_NUMERIC);

            $que = "SELECT orders.orderID, orders.timeFrom, t.*, IFNULL(b.extraPrice, a.extraPrice) AS `extraPrice` 
                    FROM `orders` INNER JOIN `treatmentsPricesSites` AS `t` USING(`siteID`, `treatmentID`)
                        LEFT JOIN `sites_weekly_hours` AS `a` ON (a.siteID = orders.siteID AND orders.timeFrom <> '0000-00-00 00:00:00' AND a.holidayID = 0 AND a.weekday = DAYOFWEEK(orders.timeFrom) - 1)
                        LEFT JOIN `sites_periods` AS `p` ON (p.siteID = orders.siteID AND orders.timeFrom <> '0000-00-00 00:00:00' AND DATE(orders.timeFrom) BETWEEN p.dateFrom AND p.dateTo)
                        LEFT JOIN `sites_weekly_hours` AS `b` ON (b.siteID = p.siteID AND b.holidayID = -p.periodID AND a.weekday = DAYOFWEEK(orders.timeFrom) - 1)
                    WHERE orders.treatmentLen = t.duratuion AND orders.parentOrder = " . $order['parentOrder'] . " AND orders.orderID <> " . $order['parentOrder'] . " AND orders.status = 1";
            $orders = udb::key_row($que, 'orderID');
$_timer->log();
            $rc = count($orders);
            foreach($orders as $orid => $ord){
                $extraTime = ($lateLimit && $latePrice && strcmp(substr($ord['timeFrom'], 11, 5), $lateLimit) >= 0) ? $latePrice : 0;
                udb::update('orders', ['price' => (($rc == 2) ? ($ord['price2'] ?: $ord['price1']) : ($ord['price3'] ?: $ord['price2'] ?: $ord['price1'])) + ($ord['extraPrice'] ?: 0) + $extraTime], '`orderID` = ' . $orid);
            }
$_timer->log();
            (new OrderSpaMain($order['parentOrder']))->updatePrice();
            //updateParentOrderPrice($order['parentOrder']);
$_timer->log();
            $result['success'] = 'Done!';
            $result['orderID'] = $order['parentOrder'];
            break;

        case 'deleteSpaSingle':
            if (!$orderID)
                throw new Exception("Missing orderID");

            $siteID = udb::single_value("SELECT `siteID` FROM `orders` WHERE `orderID` = " . $orderID);
            if (!$_CURRENT_USER->has($siteID))
                throw new Exception("Access denied to order #" . $orderID);

            $parentOrder = udb::single_value("SELECT `parentOrder` FROM `orders` WHERE `orderID` = " . $orderID);

            udb::query("DELETE FROM `orderUnits` WHERE `orderID` = " . $orderID);
            udb::query("DELETE FROM `tfusa` WHERE `orderID` = " . $orderID);
            udb::query("DELETE FROM `orders` WHERE `orderID` = " . $orderID);

            if ($parentOrder){      // should ALWAYS happen
                $que = "SELECT orders.orderID, orders.timeFrom, t.*, IFNULL(b.extraPrice, a.extraPrice) AS `extraPrice` 
                        FROM `orders` INNER JOIN `treatmentsPricesSites` AS `t` USING(`siteID`, `treatmentID`)
                            LEFT JOIN `sites_weekly_hours` AS `a` ON (a.siteID = orders.siteID AND orders.timeFrom <> '0000-00-00 00:00:00' AND a.weekday = DAYOFWEEK(orders.timeFrom) - 1)
                            LEFT JOIN `sites_periods` AS `p` ON (p.siteID = orders.siteID AND orders.timeFrom <> '0000-00-00 00:00:00' AND DATE(orders.timeFrom) BETWEEN p.dateFrom AND p.dateTo)
                            LEFT JOIN `sites_weekly_hours` AS `b` ON (b.siteID = p.siteID AND b.holidayID = -p.periodID AND a.weekday = DAYOFWEEK(orders.timeFrom) - 1)
                        WHERE orders.treatmentLen = t.duratuion AND orders.parentOrder = " . $parentOrder . " AND orders.orderID <> " . $parentOrder . " AND orders.status = 1";
                $orders = udb::key_row($que, 'orderID');

                // pulling late hour fee
                list($lateLimit, $latePrice) = udb::single_row("SELECT `ExtraPriceAfterTime`, `ExtraPriceAfterPrice` FROM `sites` WHERE `siteID` = " . $siteID, UDB_NUMERIC);

                $rc = count($orders);
                foreach($orders as $orid => $ord){
                    $extraTime = ($lateLimit && $latePrice && strcmp(substr($ord['timeFrom'], 11, 5), $lateLimit) >= 0) ? $latePrice : 0;
                    udb::update('orders', ['price' => (($rc > 2) ? ($ord['price3'] ?: $ord['price2'] ?: $ord['price1']) : (($rc == 2) ? ($ord['price2'] ?: $ord['price1']) : $ord['price1'])) + ($ord['extraPrice'] ?: 0) + $extraTime], '`orderID` = ' . $orid);
                }

                (new OrderSpaMain($parentOrder))->updatePrice();

                $user_log->save('order_treat_delete', $siteID, $parentOrder, ['deleted' => $orderID]);
            }
$_timer->log();
            $result['success'] = 'הטיפול הוסר בהצלחה';
            break;

        case 'shifts':
            $input = typemap($_POST, [
                'id'     => 'int',
                'tsid'   => 'int',
                'parent' => 'int',
                'treatmentID' => 'int',
                'duration' => 'int',
                'tmalefemale' => 'int',
                'malefemale'  => 'int',
                'startDate'   => 'inDate',
                'startTime'   => 'time',
                'therapist'   => 'int',
                'roomID'      => 'int',
                '!cleanTime'  => 'int'
            ]);

            if (!$input['tmalefemale'])
                $input['tmalefemale'] = 3;
            if (!$input['duration'])
                $input['duration'] = 5;

            if ($input['id']){
                list($orderID, $siteID, $parentID) = udb::single_row("SELECT `orderID`, `siteID`, `parentOrder` FROM `orders` WHERE `parentOrder` > 0 AND `orderID` = " . $input['id'], UDB_NUMERIC);
            } else {
                $orderID  = 0;
                $parentID = $input['parent'] ?? 0;
                $siteID   = $parentID ? udb::single_value("SELECT `siteID` FROM `orders` WHERE `parentOrder` = `orderID` AND `orderID` = " . $parentID) : ($input['tsid'] ?? 0);
            }
$_timer->log();
            if (!$_CURRENT_USER->has($siteID))
                throw new Exception("Access denied to order #" . $siteID);

            $result['master'] = 'ok';
            $result['room']   = 'ok';
$_timer->log();
            // if selected master
            if ($masterID = $input['therapist']){
                try {
                    // checking if master exists/active
                    $master = udb::single_row("SELECT * FROM `therapists` WHERE `therapistID` = " . $masterID . " AND `siteID` = " . $siteID . " AND `active` = 1 AND `deleted` = 0 " .
                            ($input['startDate'] ? " AND (`workStart` IS NULL OR `workStart` <= '" . $input['startDate'] . "') AND (`workEnd` IS NULL OR `workEnd` >= '" . $input['startDate'] . "')" : ""));
                    if (!$master)
                        throw new CheckFail('המטפל אינו פעיל יותר');

                    // checking if master gender acceptable
                    if ($input['tmalefemale'] && !($input['tmalefemale'] & $master['gender_self']))
                        throw new CheckFail('מין המטפל אינו מתאים לבחירה');
					
					// for real masters only
                    if ($master['workerType'] != 'fictive'){
                        // checking if client gender acceptable
                        if ($input['malefemale'] && !($input['malefemale'] & $master['gender_client']))
                            throw new CheckFail('המטפל אינו מטפל ב'.(($input['malefemale']==1)? "גברים" : "נשים" ));

                       
                        // checking if treatment type acceptable
                        $que = "SELECT COUNT(*) FROM `therapists_treats` WHERE `therapistID` = " . $masterID . " AND `treatmentID` = " . $input['treatmentID'];
                        if ($input['treatmentID'] && !udb::single_value($que))
                            throw new CheckFail('המטפל לא מבצע את הטיפול הנבחר');
                    }
$_timer->log();
                    if ($input['startDate'] && $input['startTime']){
                        $start = $input['startDate'] . " " . $input['startTime'];
                        $end   = date('Y-m-d H:i:s', strtotime($start) + ($input['duration'] + $input['cleanTime']) * 60);

                        $where = $orderID ? ["o.orderID <> " . $orderID] : ['1'];

                        // checking if master already has treatment for that time
                        $que = "SELECT COUNT(*)
                                FROM `orders` AS `o` INNER JOIN `orders` AS `parent` ON (`o`.parentOrder = `parent`.`orderID`)
                                WHERE o.siteID = " . $siteID . " AND o.status = 1 AND `o`.therapistID = " . $masterID . " AND `parent`.`status` = 1 
                                    AND '" . $start . "' < o.timeUntil AND '" . $end . "' > o.timeFrom AND " . implode(' AND ', $where);
                        if (udb::single_value($que))
                            throw new CheckFail('למטפל טיפול  נוסף  בשעות אלו');
$_timer->log();
						//checking therapist's breaks colision
						$que = "SELECT COUNT(*) FROM `spaShifts` WHERE `status` = 0 AND `masterID` = " . $masterID . " AND '" . $end . "' > `timeFrom` AND '" . $start . "' < `timeUntil`";                            
						if (udb::single_value($que))
							throw new CheckFail('למטפל קיימת הפסקה בשעה זו');
$_timer->log();
                        // for real masters only
                        if ($master['workerType'] != 'fictive'){
                            // checking if master has shift at the time
                            $que = "SELECT COUNT(*) FROM `spaShifts` WHERE `status` = 1 AND `masterID` = " . $masterID . " AND '" . $start . "' >= `timeFrom` AND '" . $end . "' <= `timeUntil`";
                            if (!udb::single_value($que))
                                $result['master'] = 'no-shift';
$_timer->log();
                        }else{
							 $que = "SELECT COUNT(*) FROM `spaShifts` WHERE `status` = 0 AND `masterID` = " . $masterID . " AND '" . $start . "' >= `timeFrom` AND '" . $end . "' <= `timeUntil`";
							  if (udb::single_value($que))
								throw new CheckFail('המטפל נעול ביום זה');
						}
                    }
$_timer->log();
                }
                catch (CheckFail $fail){
                    $result['master'] = $fail->getMessage();
                }
            }

            // if selected room
            if ($input['roomID'] && $input['startDate'] && $input['startTime']){
                $roomID = $input['roomID'];

                try {
                    // checking if unit exists/active
                    $room = udb::single_value("SELECT COUNT(*) FROM `rooms` INNER JOIN `rooms_units` AS `u` USING(`roomID`) WHERE rooms.siteID = " . $siteID . " AND rooms.active = 1 AND u.unitID = " . $roomID . " AND u.hasTreatments = 1");
                    if (!$room)
                        throw new CheckFail('החדר אינו פעיל יותר');

					 // checking if unit allowed for specific treatment
					if($input['treatmentID']){
						$room = udb::single_value("SELECT COUNT(*) 	
						FROM  units_treats 
						WHERE units_treats.unitID = " . $roomID . " AND units_treats.treatmentID = ".$input['treatmentID']);

						if (!$room)
							throw new CheckFail('לא ניתן לבצע את הטיפול בחדר זה');

					}
$_timer->log();
                    // checking if room available
                    $start = $input['startDate'] . " " . $input['startTime'];
                    $end   = date('Y-m-d H:i:s', strtotime($start) + ($input['duration'] + $input['cleanTime']) * 60);

                    $where = $parentID ? ["o.parentOrder <> " . $parentID] : ['1'];

                    $que = "SELECT COUNT(*)
                            FROM `orderUnits` INNER JOIN `orders` AS `o` USING(`orderID`)
                                INNER JOIN `orders` AS `parent` ON (`o`.parentOrder = `parent`.`orderID`)
                            WHERE o.siteID = " . $siteID . " AND o.status = 1 AND parent.status = 1 AND orderUnits.unitID = " . $roomID . "
                                AND '" . $start . "' < o.timeUntil AND '" . $end . "' > o.timeFrom AND " . implode(' AND ', $where);
                    if (udb::single_value($que))
                        throw new CheckFail('fail-busy');
$_timer->log();
                }
                catch (CheckFail $fail){
                    $result['room'] = $fail->getMessage();
                }
            }
$_timer->log();
            break;

        case 'openSpaSingle':
            $input = typemap($_POST, [
                'terid' => 'int',
                'date'  => 'date',
                'hour'  => 'time',
                'subTr' => 'int'
            ]);

            $sDate = strtotime($input['date'] ?? 'tomorrow');
            $sourceList = [];

            if ($orderID){
                $order = udb::single_row("SELECT * FROM `orders` WHERE `orderID` = " . $orderID);
$_timer->log();
                if (!$_CURRENT_USER->has($order['siteID']))
                    throw new Exception("Access denied to order #" . $order['orderIDBySite']);

                if ($order['timeFrom'][0] != '0')
                    list($order['startDate'], $order['startTime']) = explode(' ', substr($order['timeFrom'], 0, 16));

                $order['unitID'] = udb::single_value("SELECT `unitID` FROM `orderUnits` WHERE `orderID` = " . $orderID) ?: 0;
                $sourceList = SourceList::site_list($order['siteID'], false, $order['sourceID']);
$_timer->log();
            }
            elseif ($orderParent) {
$_timer->log();
                $parent = udb::single_row("SELECT * FROM `orders` WHERE `orderID` = " . $orderParent);
                if (!$_CURRENT_USER->has($parent['siteID'] ?? 0))
                    throw new Exception("Access denied to site #" . $parent['siteID']);

                $clean  = udb::single_value("SELECT `waitingTime` FROM `sites` WHERE `siteID` = " . $parent['siteID']);
                $prev   = udb::single_value("SELECT COUNT(*) FROM `orders` WHERE `orderID` <> " . $orderParent . " AND `parentOrder` = " . $orderParent);

                $order  = [
                    'domainID'      => 0,
                    'siteID'        => $parent['siteID'],
                    'parentID'      => $orderParent,
                    'customerName'  => $prev ? '' : $parent['customerName'],
                    'customerPhone' => $prev ? '' : $parent['customerPhone'],
                    'pastePhone'	=>  $parent['customerPhone'],
                    'unitID'        => 0,
                    'startDate'     => date('Y-m-d', $sDate),
                    'startTime'     => substr($input['hour'] ?? '12:00', 0, 5),
                    'therapistID'   => $input['terid'] ?? 0,
                    'cleanTime'     => max(0, $clean)
                ];

                if ($prev){
                    $que = "SELECT MAX(orders.timeFrom) AS `maxDate` FROM `orders` WHERE orders.parentOrder = " . $orderParent . " AND orders.orderID <> " . $orderParent . " AND orders.status = 1";
                    if ($dTime = udb::single_value($que))
                        list($order['startDate'], $order['startTime']) = explode(' ', substr($dTime, 0, 16));
                }

                $sourceList = SourceList::site_list($parent['siteID'], false, $parent['sourceID']);
                $treatData = [];
$_timer->log();
            }
            elseif ($_POST['parent']) {
$_timer->log();
                $pdata = [];
                parse_str(base64_decode($_POST['parent']), $pdata);

                if (!($sid = intval($pdata['orderSite'])))
                    throw new Exception ("2לא נבחר מתחם להזמנה");
                if (!$_CURRENT_USER->has($sid))
                    throw new Exception("Access denied to site #" . $sid);

                $clean  = udb::single_value("SELECT `waitingTime` FROM `sites` WHERE `siteID` = " . $sid);

                $order  = [
                    'domainID'      => 0,
                    'siteID'        => $sid,
                    'parentID'      => 0,
                    'customerName'  => typemap($pdata['name'], 'string'),
                    'customerPhone' => typemap($pdata['phone'], 'string'),
                    'unitID'        => 0,
                    'startDate'     => date('Y-m-d', $sDate),
                    'startTime'     => substr($input['hour'] ?? '12:00', 0, 5),
                    'therapistID'   => $input['terid'] ?? 0,
                    'pdata'         => $_POST['parent'],
                    'cleanTime'     => max(0, $clean)
                ];

                $sourceList = SourceList::site_list($sid);
                $treatData = [];
$_timer->log();
            }
            else
                throw new Exception('Internal error: no ID and no parent');

            if ($input['subTr']){
                $que = "SELECT tr.*, s.*, sett.TITLE AS `settName` 
                        FROM `subscriptions` AS `s` INNER JOIN `subscriptionTreatments` AS `tr` USING(`subID`) 
                            LEFT JOIN `settlements` AS `sett` ON (sett.settlementID = s.clientCity) 
                        WHERE s.active = 1 AND tr.stID = " . $input['subTr'];
                $treat = udb::single_row($que);

                if (!$treat)
                    throw new Exception("Subscription " . $input['subTr'] . " is inactive or doesn't exist.");
                if (!$_CURRENT_USER->has($treat['siteID']))
                    throw new Exception("You cannot access subscription " . $treat['subID']);
                if ($treat['payID'])
                    throw new Exception("This treatment was already used");

                $tdata = json_decode($treat['data'], true);

                $order['siteID']       = $treat['siteID'];
                $order['treatmentID']  = $treat['treatmentID'];
                $order['treatmentLen'] = $treat['duration'];

                if ($tdata['name'])
                    $order['customerName'] = $tdata['name'];
                if ($tdata['phone'])
                    $order['customerPhone'] = $tdata['phone'];
                if ($tdata['gen_m'])
                    $order['treatmentMasterSex'] = $tdata['gen_m'];
                if ($tdata['gen_c'])
                    $order['treatmentClientSex'] = $tdata['gen_c'];
                if ($treat['clientAddress'])
                    $order['customerAddress'] = $tdata['clientAddress'];
                if ($treat['clientCity']){
                    $order['settlementID'] = $treat['clientCity'];
                    $order['clientCity']   = $treat['settName'];
                }

                $order['carry'] = ['stID' => $treat['stID']];

                $treatData = [];
            }
$_timer->log();

            $siteData = udb::single_row("SELECT * FROM sites WHERE siteID = " . $order['siteID']);

            $units = udb::key_value("SELECT u.unitID, u.unitName FROM `rooms_units` AS `u` INNER JOIN `rooms` USING(`roomID`) WHERE (rooms.active = 1 AND rooms.siteID = " . $order['siteID'] . " AND u.hasTreatments > 0) OR rooms.roomID = " . $order['unitID'] . " ORDER BY `unitName`");
            $treatTypes = udb::key_value("SELECT `treatmentID` AS `treatID`, `treatmentName` FROM `treatments` WHERE 1 ORDER BY `treatmentName`");
            $masters = udb::key_row("SELECT `therapistID`, `siteName` AS `name`, `gender_client`, `gender_self`, IF(`workerType` = 'fictive', 1, 0) AS fictive FROM `therapists` WHERE ((`active` = 1 " . ($order['startDate'] ? " AND (`workStart` IS NULL OR `workStart` <= '" . $order['startDate'] . "') AND (`workEnd` IS NULL OR `workEnd` >= '" . $order['startDate'] . "')" : "") . ") " . ($order['therapistID'] ? " OR `therapistID` = " . $order['therapistID'] : "") . ") AND `siteID` = " . $order['siteID']." AND deleted < 1 ORDER BY fictive DESC, `name`", 'therapistID');
            $prices  = udb::key_column("SELECT `treatmentID`, `duratuion` FROM `treatmentsPricesSites` WHERE `siteID` = " . $order['siteID'] . " ORDER BY `duratuion`", 'treatmentID', 'duratuion');
            $locked  = udb::key_column("SELECT `masterID`, DATE(`timeFrom`) AS `date` FROM `spaShifts` WHERE `masterID` IN (" . implode(',', array_keys($masters) ?: [0]) . ") AND `status` = 0 AND `timeUntil` > '" . date('Y-m-d 00:00:00') . "'", 'masterID', 'date');
            $mastTreats = udb::key_column("SELECT * FROM `therapists_treats` WHERE `therapistID` IN (" . implode(',', array_keys($masters) ?: [0]) . ")", 'therapistID', 'treatmentID');
$_timer->log();
            ob_start();
?>
<div class="create_order spa" id="create_orderSpa">
    <div class="container">
		<style>
		#selectpop.create_order {position: absolute;right: 0;width: auto;display:none}
		 .create_order .container .selectTitle {display: block;font-weight: 500;color: #333;font-size: 30px;text-align: center;padding: 12px 0 13px;background: #fff;box-shadow: 0 0 10px rgb(0 0 0 / 50%);z-index: 1;position: relative;}
		.single-select {display: block;padding: 20px;box-sizing: border-box;overflow: auto;position: absolute;left: 0;right: 0;top: 60px;bottom: 0;height: auto;align-items: center;}
		.single-select-row {text-align:right;display: flex;height: 50px;background: white;border-bottom: 1px #ccc solid;font-size: 16px;padding: 0 10px;cursor: pointer;align-items: center;}
		.single-select-row.Allowed:hover, .single-select-row.noShifts:hover{background: #cfeef0;}
		.single-select-row.notAvailable {cursor: not-allowed; color:red}
		.single-select-row.noShifts {color:red}
		.single-select-row.notAllowed {cursor: not-allowed; color:#AAA}
		.single-select-row.selected {border:3px #0dabb6 solid}
		.single-select-row div div {font-size: 14px;opacity: 0.5;}
		.single-select-row .gender,.single-select-row .client {padding:0 10px}
		.single-select-row .gender.m,.single-select-row .client.t1 {background:#dbf2f4}
		.single-select-row .gender.f,.single-select-row .client.t2 {background:#fbe4e1}
		.single-select-row .gender.x,.single-select-row .client.t3 {background:#fcf2e2}
		.create_order .container #selectcontent .selectTitle {height: 60px;display: flex;font-size: 16px;padding: 0 40px;align-items: center;justify-content: center;}

		</style>
		<div class="create_order select-pop" id="selectpop">
			<div class="container">
				<div class="close" onclick="$('#selectpop').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
				<div id="selectcontent">
				</div>
			</div>
		</div>
        <div class="close" onclick="closeSpaForm()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
        <div class="title mainTitle">
            <div class="domain-icon" style="display: none;background-image:url(<?=$domain_icon[$order['domainID']]?>)"></div>טיפול ספא
			<div class="order"  data-orderid="<?=$order['orderID']?>" ><div class="c_status c_s<?=$order['client_status']?>" onclick="change_c_s($(this))"></div></div>
        </div>

        <form class="form" id="spaOrderForm" data-guid="<?=$order['guid']?>" method="post" autocomplete="off">
            <input type="hidden" name="id" value="<?=$orderID?>" />
            <input type="hidden" name="parent" value="<?=$order['parentID']?>" />
            <input type="hidden" name="tsid" id="spa_sid" value="<?=$order['siteID']?>" />
<?php
            if ($order['pdata'])
                echo '<input type="hidden" name="pdata" value="' , $order['pdata'] , '" />';
			if ($order['carry'])
                echo '<input type="hidden" name="carry" value="' , htmlspecialchars(json_encode($order['carry'], JSON_UNESCAPED_UNICODE)) , '" />';

            if ($orderID) {
?>
            <div class="signOpt inOrder" style="text-align:right">
                <div style="font-size:16px;font-weight:bold;color:#424242;display:inline-block;line-height:34px">הצהרת בריאות <?=$health_declare?"<style>.V {display: inline-block;vertical-align:middle;position: relative;width: 10px;height: 10px;}.V::before {content: '';position: absolute;top: 0;right: 5px;border-bottom: 2px solid #0dabb6;border-left: 2px solid #0dabb6;width: 10px;height: 3px;transform: rotate(-45deg);}</style><span class=\"V\"></span>":""?></div>
                <div style="float:left;margin:-5px -5px -5px 0">
<?php
                $health_declare = udb::single_row("SELECT * FROM health_declare WHERE orderID = " . $orderID);

                if ($health_declare) {
?>
                    <a target="_blank" style="font-size:18px;background:#0dabb6;color:#fff;padding:10px;box-sizing:border-box;text-decoration:none;font-weight:500;display:inline-block;vertical-align:middle" href="/health/<?=$order['siteID']?>/<?=$health_declare['guid']?>">למעבר להצהרת בריאות</a>
<?php
                }
                else {
                    $link = WEBSITE . "health/".$order['siteID']."/?fill_form=1%26orderGuid=".$order['guid'];
                    $subject = "טופס לאישור הזמנה ב". $siteData['siteName'] ." בתאריך".date('d.m.y', strtotime($order['timeFrom']));
                    $body = $order['customerName'].' שלום, מצורף קישור למילוי הצהרת בריאות לשם קבלת טיפול ספא ב' . $siteData['siteName'] . ', ביום ' .$weekday[date('w', strtotime($order['timeFrom']))].' '.date('d.m.y', strtotime($order['timeFrom'])).' - יש ללחוץ על הקישור הבא '.$link;
					$phoneClean = str_replace("-","",$order['customerPhone']);
                    if ($order['customerPhone']) {
?>
                    <a href="<?whatsappBuild($order['customerPhone'],$body)?>" data-href="<?whatsappBuild($order['customerPhone'],$body)?>" data-sign-href="<?whatsappBuild($order['customerPhone'],$body_sign)?>" target="_blank"><span class="icon whatsapp" data-phone="<?=$phoneClean?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30"><defs><image width="30" height="30" id="img-whatsapp" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAeCAYAAAA7MK6iAAAAAXNSR0IB2cksfwAABflJREFUeJyVV1tQk1cQ/tvOtB1n2ulbx7f2odOnTh/74JOdcaZ96XQ6tRWRiwGttyqOTK1a1FrFqtXqTG3pxV69VFsFL4iAIMpNQcFKQAhggBACJIAJt5AA2+874fwkGJGczE7+nPOf/c7ufrtnY4iIoWVoaMjo7Ow0nE6nKV1dXYbD4Xga8wtcLldmd3d3ocfjae7v7++F4NFj7+npKcPad3j3Pay/ADH3Y6/67ujoMOx2uynGbMB4ngdl8VB80+fzSSAQkMnJSYk2gsGgYL/09fW14BCbsfflmIGnZAEAa0dHR6MCTUxOKIk2eEB4Aio6kwkYC/Dqhw8f+sOt8/l9UuEol1N1J+VA+T7ZcS1Dthdvk8zS3fJb7TG5Zi8W16Ar4gD0AHQdgTw3K/Dw8DBPtp0bTBdOBOVC03nZkPepLDu7ROL//VgSzi6VpHPLlCSeWyrxU/MrL6SoQwyMDpj7x8bGBK7PaW9vfyYCmFZSBgcHDbjHgm9zk33ggXx1facshdLk7ARJPb98VrHkJKl3N15ZL3e6bkeAw+pDzc3NhhbD6/Uqcbvdb4Clw/rl5j6brLm0SlnzJMCZkpS9TEmxvSjC7bDcAgyDYvT29lKeBZEqJiZCZHF4HbI2dzXcGK8U0bUJZ+PmZLWW5TmJan+1s8oEB2Af3Dy/ra3NMHAKsm4R4qsWSaj95V+blvLkp+pOSKWjQnaV7FBxjcXyNHBDx5wph/TMYG1QFsPNFzWDr7eVgChLpiyNU6B63Hc3KGUp55PnDM6YH6v52dSBPHfCuy8xwV8dGBhQyRqYCMi2os+VVSTKOrjb6/eamybx2XNjlxmCuQj1fHIxVTzDHqVjZGSERFvM9En1+/1qsqG33lTK7y/h2pmjqvOWJCCFYiEbw3YDnuQYHx8XePl3Foss/uAoaLmi8lHHZ8vVzTKOPA4f+S15MQMz/3+6nWXqAPAtkqtAx/ev//4w48s4Ls9OlCZPo7nhrqtWMTslZ+4x1t4jMRmqKXbbDF4AWvEPVUdxujhzA8n1beVB82IguRgzS3ZSTMA87JbCzyQwHlB6wKkOsrpCA2dVfx8BrN1U1l5qWs2aTK/wAI8UjcekmgJG2EjecODL2qKT946brg7ftCFvHS6ALhP8jPVv5b4QkEU9ZxRtlQxcGnyeyXr+3o1sCEupRhaPI5pcRQ+uPgKsXA4y8cRDY9N1/LazWjZdSZPFZz5QKTiINRLxpqNS3VrhHqFOXh5h5Cpn8YhjbnG09DXDwsSoBYIh2Fu6R9zDblMBwfKRCU6fM4L5vNEOVhxQN5fee6szRCVWLxD6KC2eD5b5OMmLfVfJzsemC+O9KT9NbJ4medI4DFKSnKzZa3NXmYWIlwWK1juqM8AJTug404WM3ePKItdWIK5Mvdb+1qigNa4aVa2YdiyZp62nwuNrA7nmGbigKW/hHg7oRabQbEWC8WM14jdL6GXbJanvtYq1p06O3/tTzdNS6tgKbowGQ6FkSwQj1yCFDdWMAXg+TqGYwyTfi3ZmLvWYFvE9HiLUkcSreHKe6bXigkVsuNfDSNWKm+lFdqGqfUXZXKibuu4hF1qY1FChgKhKFcNtpMlEV9/trp0mIjobdB7v1tfXGw0NDQYrFyVTp9TV1kL58PT7KgXYQ20uSFelk5bNdgCuJcNKxnRnyXbEv8UEZdbg8k9vamoytDCdnkKvVa3d/GvNL7KvbK8UtOZLh7dD/EG/agJ2FH8Rii0ORNfSKrKWTNc9WXr+Rrlku2hWKA42GKwVBKurqzNoMcWAv19HzzWmgUcCI1GZylRj88ciw2JwuPKQHKr4Rt06uQBrdN8HiaZ7cGYJWuQg9K9nX22z2Qyr1RoBvDJa007X00U8se7F5jIISH0g0h0weCE8qnj0CDBeyOPLVM4NOOUo5qpxoP0g3SI8vw35h6znOg80828M97KZgOfGwZcSAH2Evc9PtVXRgQFQzqLNIoIXLPj9GlNsqgk0sKY2Q9ErWE/B/I9QXsTLHPNVeL6BueOQNKy/yX8i+k/bbMD/A9JqvbnfYMIJAAAAAElFTkSuQmCC"></image></defs><use id="L0001" href="#img-whatsapp" x="0" y="0"></use></svg></span></a>
                    <a href="sms:<?=$phoneClean?>?&body=<?=$body?>" data-phone="<?=$phoneClean?>" data-msg="<?=$body?>" target="_blank" class="a_sms">
                        <span class="icon sms" data-sms="<?=$phoneClean?>"><img src="/user/assets/img/icon_sms.png" alt="sms" /></span>
                    </a>
<?php
                    }

                    if ($order['customerEmail']) {
?>
                    <a href="mailto:<?=$order['customerEmail']?>?subject=<?=$subject?>&body=<?=$body?>" target="_blank"><span class="icon mail" data-mail="<?=$order['customerEmail']?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 27" width="32" height="27"><style>.message-ic{fill:#fff}</style><path class="message-ic" d="M29.9 25L24.1 19.4 23.5 18.8 23 18.3 22.6 17.8 22.1 17.4 24.9 15.5 29.8 12 30 11.9 30 14 30 17.2 30 25 29.9 25ZM22.2 6L5 10.9 5.7 13.3 2.1 10.7 2.8 7.8C4.1 7 12.5 2.1 15.9 2 19.3 2.1 27.7 7 29 7.8L29.9 10.7 24.7 14.5 22.2 6ZM6.2 14.8L9.7 17.4 9 18.1 8.5 18.6 8.1 19.1 7.5 19.6 2.1 25 2 25 2 13.1 2 12 2 11.9 2.7 12.4 6.2 14.8ZM9.5 19L10 18.5 10.4 18.1 10.8 17.7 11.2 17.2 11.3 17.2C11.4 17.1 11.6 16.9 11.8 16.8 12.9 15.8 14.4 15.3 15.9 15.3 17.4 15.3 18.9 15.8 20 16.8 20.2 16.9 20.3 17 20.5 17.2L20.6 17.3 21.1 17.7 21.2 17.9 21.7 18.3 22.2 18.8 23 19.7 28.4 25 3.5 25 7.8 20.7 9.5 19Z"></path></svg></span></a>
<?php
                    }
?>
                    <span class="icon plusSend" onclick='$("#sendPopMsg").val($(this).data("msg"));$("#sendPopSubject").val($(this).data("subject"));$(".sendPop").fadeIn("fast");$("#SendPopTitle").text($(this).data("title"));' data-title="<?=$order['approved']?"יצירת קשר":"שליחה לחתימה"?>" data-msg="<?=$body?>" data-subject="<?=$subject?>"></span>
<?php
                }
?>
                </div>
            </div>
<?php
            }
//			echo $orderParent;
$_timer->log();
?>

			<div class="inputWrap select orderOnly" <?=($siteData['sourceRequired'] && !$orderParent && !$orderID)?  "" : "style='display:none'"?>>
				<select class='required' onchange="$('#sourceID').val($(this).val());if($(this).val()!='novalue'){$(this).removeClass('required')}else{$(this).addClass('required')}" name="sourceID" id="sourceID_inner" <?=($order['apiSource']=='spaplus' || $order['sourceID']=='online') ? "readonly style='pointer-events:none'":""?>>
					<?if($siteData['sourceRequired']){?><option style='color:red' value="novalue">יש לבחור</option><?}?>
					<option value="0">הזמנה רגילה</option>
<?php
            foreach($sourceList as $source)
                echo '<option value="' . $source['key'] . '" ' . (($source['key'] == $order['sourceID']) ? 'selected' : '') . '>' . $source['fullname'] . '</option>';

/*					UserUtilsNew::init($_CURRENT_USER->active_site());
					$cuponTypes = UserUtilsNew::$CouponsfullList;
					foreach($cuponTypes as $k=>$source) { ?>
					<option value="<?=$k?>" <?=$order['sourceID']==$k?"selected":""?>><?=$source?></option>
					<?php }
					foreach(UserUtilsNew::guestMember() as $k => $source){
						?>
						<option value="<?=$k?>" <?=$order['sourceID']==$k?"selected":""?>><?=$source?></option>
					<?php }
					foreach(UserUtilsNew::otherSources() as $k => $source){
						?>
						<option value="<?=$k?>" <?=$order['sourceID']==$k?"selected":""?>><?=$source?></option>
						<?php
					}
?>
					<option value="online" <?=$order['sourceID']=='online' ? "selected":""?>>הזמנת Online</option> */
?>
				</select>
				<label for="sourceID_inner">מקור ההזמנה</label>
			</div>
			<div style="z-index:11;position:relative;<?=($siteData['addressRequired'] && !$orderParent && !$orderID)? "" : "display:none"?>">
				<div class="inputWrap half orderOnly">
					<input type="hidden" name="settlementID" value="<?=$order['settlementID']?>"  class="hide_next <?=($siteData['addressRequired']==2 && !$orderID)? "required" : ""?> <?=$order['settlementID']? "valid" : ""?>">
					<div class="settlementName"><?=$order['clientCity']?></div>					
					<input  type="text"  class="ac-inp2" name="clientCity" id="clientCity2" value="<?=$order['clientCity']?>">
					<label for="clientCity2">עיר</label>
					<div class="autoBox"><div class="autoComplete"></div></div>
				</div>
				<div class="inputWrap half orderOnly">
					<input type="text" name="clientAddress" onchange="$('#clientAddress').val($(this).val());if($(this).val()){$(this).removeClass('required')}else{$(this).addClass('required')}" id="clientAddress_inner" class="<?=($siteData['addressRequired']==2 && !$orderID)? "required" : ""?>" value="<?=$order['customerAddress']?>">
					<label for="clientAddress_inner">רחוב ומספר</label>
				</div>

			</div>
           


            <div class="inputWrap half" style="z-index:10">
                <input type="text" name="name" id="spa_name" value="<?=htmlspecialchars($order['customerName'])?>" class="ac-inp" />
                <label for="spa_name">שם המזמין</label>
                <div class="autoBox"><div class="autoComplete"></div></div>
            </div>

            <div class="inputWrap  date four" style="z-index:5">
                <input type="text" name="phone" id="spa_phone" value="<?=$order['customerPhone']?>" class="ac-inp" />
                <label for="spa_phone">טלפון</label>
				<?if(!$order['customerPhone'] && $order['pastePhone']){?><div class="copyinput" onclick="$('#spa_phone').val('<?=$order['pastePhone']?>')"></div><?}?>
                <div class="autoBox"><div class="autoComplete"></div></div>
            </div>
			<div class="inputWrap date four time gender">
                <div class="radios">
                    <div>
                        <input type="radio" name="malefemale" id="male" value="1" <?=($order['treatmentClientSex'] == 1 ? 'checked' : '')?> />
                        <label for="male">גבר</label>
                    </div>
                    <div>
                        <input type="radio" name="malefemale" id="female" value="2" <?=($order['treatmentClientSex'] == 2 ? 'checked' : '')?> />
                        <label for="female">אשה</label>
                    </div>
                </div>
            </div>
            <div class="inputWrap half select orderOnly">
                <select name="treatmentID" id="treatmentID">
                    <option value="0">- - - בחר - - -</option>
<?php
    foreach($treatTypes as $id => $name)
        if ($prices[$id] || $order['treatmentID'] == $id)
            echo '<option value="' , $id , '" ' , ($order['treatmentID'] == $id ? 'selected' : '') , ' data-prices="' , htmlspecialchars(json_encode($prices[$id], JSON_NUMERIC_CHECK)) , '">' , $name , '</option>';
?>
                </select>
                <label for="treatmentID">סוג טיפול</label>
            </div>
            <div class="inputWrap date four time">
                <select name="duration" id="duration">
<?php
    $durs = $order['treatmentID'] ? array_unique(array_reduce($prices, function($res, $a){ return array_merge($res, $a); }, [])) : [];
    foreach($durs as $id)
        echo '<option value="' , $id , '" ' , ($order['treatmentLen'] == $id ? 'selected' : '') , '>' , $id , ' דקות</option>';
?>
                </select>
                <label for="duration">משך</label>
            </div>
            <div class="inputWrap date four prefer">
                <div class="radios">
                    <div>
                        <input type="radio" name="tmalefemale" id="tmale" value="1" <?=($order['treatmentMasterSex'] == 1 ? 'checked' : '')?> />
                        <label for="tmale">מטפל</label>
                    </div>
                    <div>
                        <input type="radio" name="tmalefemale" id="tfemale" value="2" <?=($order['treatmentMasterSex'] == 2 ? 'checked' : '')?> />
                        <label for="tfemale">מטפלת</label>
                    </div>
                    <div>
                        <input type="radio" name="tmalefemale" id="tnone" value="0" <?=($order['treatmentMasterSex'] ? '' : 'checked')?> />
                        <label for="tnone">ללא העדפה</label>
                    </div>
                </div>
            </div>
			<?php
//			if(!$orderID && $orderParent){
//				$que = "SELECT orders.timeFrom
//				FROM `orders`
//					LEFT JOIN `orderUnits` USING(`orderID`)
//					LEFT JOIN `therapists` USING(`therapistID`)
//					LEFT JOIN `treatments` USING(`treatmentID`)
//					LEFT JOIN `health_declare` ON (orders.orderID = health_declare.orderID)
//				WHERE orders.parentOrder = " . $orderParent . " AND orders.orderID <> " . $orderParent . "
//				GROUP BY orders.timeFrom DESC LIMIT 1";
//				echo $que;
//				$default_time = udb::single_row($que);
//				if(!$default_time['timeFrom'])
//					$default_time['timeFrom'] = date("Y-m-d 12:00",strtotime('+ 1 day'));
//
//				list($order['startDate'], $order['startTime']) = explode(' ', substr($default_time['timeFrom'], 0, 16));
//			}

$_timer->log();
            ?>
            <div class="inputWrap date four">
                <input type="text" value="<?=db2date($order['startDate'], '/')?>" name="startDate" class="datePick fromDate" readonly id="spa_from">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23 23" width="23" height="23"><path class="shp0" d="M12 16.1C12 16.9 12.7 17.6 13.6 17.6L15.4 17.6C16.2 17.6 16.9 16.9 16.9 16.1L16.9 14.2C16.9 13.4 16.2 12.7 15.4 12.7L13.6 12.7C12.7 12.7 12 13.4 12 14.2L12 16.1ZM13.6 14.2L15.4 14.2 15.4 16.1C15.4 16.1 15.4 16.1 15.4 16.1L13.6 16.1 13.6 14.2ZM16.2 9.3C16.6 9.3 16.9 9.7 16.9 10.1 16.9 10.5 16.6 10.9 16.2 10.9 15.7 10.9 15.4 10.5 15.4 10.1 15.4 9.7 15.7 9.3 16.2 9.3ZM12.8 9.3C13.2 9.3 13.6 9.7 13.6 10.1 13.6 10.5 13.2 10.9 12.8 10.9 12.4 10.9 12 10.5 12 10.1 12 9.7 12.4 9.3 12.8 9.3ZM20.3 15.6C20.7 15.6 21.1 15.3 21.1 14.8L21.1 6.6C21.1 4.9 19.7 3.5 18 3.5L16.9 3.5 16.9 2.7C16.9 2.3 16.6 2 16.2 2 15.7 2 15.4 2.3 15.4 2.7L15.4 3.5 11.9 3.5 11.9 2.7C11.9 2.3 11.5 2 11.1 2 10.7 2 10.3 2.3 10.3 2.7L10.3 3.5 6.8 3.5 6.8 2.7C6.8 2.3 6.5 2 6.1 2 5.6 2 5.3 2.3 5.3 2.7L5.3 3.5 4.3 3.5C2.6 3.5 1.2 4.9 1.2 6.6L1.2 18.7C1.2 20.4 2.6 21.8 4.3 21.8L18 21.8C19.7 21.8 21.1 20.4 21.1 18.7 21.1 18.3 20.7 17.9 20.3 17.9 19.9 17.9 19.5 18.3 19.5 18.7 19.5 19.6 18.8 20.3 18 20.3L4.3 20.3C3.5 20.3 2.8 19.6 2.8 18.7L2.8 6.6C2.8 5.8 3.5 5.1 4.3 5.1L5.3 5.1 5.3 5.8C5.3 6.3 5.6 6.6 6.1 6.6 6.5 6.6 6.8 6.3 6.8 5.8L6.8 5.1 10.3 5.1 10.3 5.8C10.3 6.3 10.7 6.6 11.1 6.6 11.5 6.6 11.9 6.3 11.9 5.8L11.9 5.1 15.4 5.1 15.4 5.8C15.4 6.3 15.7 6.6 16.2 6.6 16.6 6.6 16.9 6.3 16.9 5.8L16.9 5.1 18 5.1C18.8 5.1 19.5 5.8 19.5 6.6L19.5 14.8C19.5 15.3 19.9 15.6 20.3 15.6ZM6.1 16.1C6.5 16.1 6.8 16.4 6.8 16.8 6.8 17.3 6.5 17.6 6.1 17.6 5.6 17.6 5.3 17.3 5.3 16.8 5.3 16.4 5.6 16.1 6.1 16.1ZM6.1 9.3C6.5 9.3 6.8 9.7 6.8 10.1 6.8 10.5 6.5 10.9 6.1 10.9 5.6 10.9 5.3 10.5 5.3 10.1 5.3 9.7 5.6 9.3 6.1 9.3ZM6.1 12.7C6.5 12.7 6.8 13 6.8 13.5 6.8 13.9 6.5 14.2 6.1 14.2 5.6 14.2 5.3 13.9 5.3 13.5 5.3 13 5.6 12.7 6.1 12.7ZM9.4 12.7C9.9 12.7 10.2 13 10.2 13.5 10.2 13.9 9.9 14.2 9.4 14.2 9 14.2 8.6 13.9 8.6 13.5 8.6 13 9 12.7 9.4 12.7ZM9.4 9.3C9.9 9.3 10.2 9.7 10.2 10.1 10.2 10.5 9.9 10.9 9.4 10.9 9 10.9 8.6 10.5 8.6 10.1 8.6 9.7 9 9.3 9.4 9.3ZM9.4 16.1C9.9 16.1 10.2 16.4 10.2 16.8 10.2 17.3 9.9 17.6 9.4 17.6 9 17.6 8.6 17.3 8.6 16.8 8.6 16.4 9 16.1 9.4 16.1Z"></path></svg>
                <label for="spa_from">מתאריך</label>
            </div>
            <div class="inputWrap date four time">
                <input type="text" value="<?=$order['startTime']?>" name="startTime" class="timePicks readonlymob"  id="spa_frot">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>
                <label for="spa_frot">שעת כניסה</label>
            </div>
            <div class="inputWrap half select orderOnly">
                <input type="checkbox" style="display:none" name="lockedTherapist" id="lockedTherapist" value="1" <?=$order['lockedTherapist']? "checked" : ""?>>				
				<input type="hidden" value="<?=$order['therapistID']?>" name="therapist" id="therapist" >
                <input type="text" readonly value="<?=$masters[$order['therapistID']]['name']?:"--- בחר ---"?>" name="therapistName" id="therapistName" onclick="openSingleSelect('therapist')" style="background:transparent;cursor:pointer">
				<label for="lockedTherapist" class="lockedTherapist"></label>
				<?/*
                <select name="therapist" id="therapist">
                    <option value="0">- - - בחר - - -</option>
<?php
    foreach($masters as $id => $master)
        echo '<option value="' , $id , '" data-weight="' , ($master['gender_self'] == 3 ? 0 : 1) , '" ' , ($order['therapistID'] == $id ? 'selected' : '') ,
                    ' data-does="' , (isset($mastTreats[$id]) ? htmlspecialchars(json_encode($mastTreats[$id], JSON_NUMERIC_CHECK)) : '') , '" data-client="' , $master['gender_client'] , '" data-self="' , $master['gender_self'] , '" data-locked="' , (isset($locked[$id]) ? htmlspecialchars(json_encode($locked[$id])) : '') , '">' , $master['name'] , '</option>';
?>
                </select>*/?>
                <label for="therapist">מטפל/ת</label>
            </div>
            <div class="inputWrap half select orderOnly">
				<input type="hidden" value="<?=$order['unitID']?>" name="roomID" id="spa_roomID" >
                <input type="text" readonly value="<?=$units[$order['unitID']]?:"--- בחר ---"?>" name="therapistName" id="spa_roomIDName" onclick="openSingleSelect('spa_roomID')" style="background:transparent;cursor:pointer">
				<?/*
                <select name="roomID" id="spa_roomID">
                    <option value="0">-</option>
<?php
    foreach($units as $id => $name)
        echo '<option value="' , $id , '" ' , ($order['unitID'] == $id ? 'selected' : '') , '>' , $name , '</option>';
?>
                </select>*/?>
                <label for="spa_roomID">חדר הטיפול</label>
            </div>



            <div class="inputWrap half select orderOnly">
                <select name="cleanTime" id="spa_cleanTime">
<?php
    for($i = 0; $i <= 30; $i += 5)
        echo '<option value="' , $i , '" ' , ($i == $order['cleanTime'] ? 'selected' : '') , '>' , $i , " דק'</option>";
?>
                </select>
                <label for="spa_cleanTime">זמן נקיון</label>
            </div>
			<div class="inputWrap textarea" style="height:90px">
				<textarea style="height:90px" id="comments_customer" name="comments_customer"><?=$order['comments_customer']?></textarea>
				<label for="comments_customer">הערות מטפל</label>
			</div>


            <div class="statusBtn">
                <button type="button" onclick="saveSpaSingle(this)" class="inputWrap submit"><?=($orderID ? 'עדכן' : 'שמור')?></button>
            </div>

            <style>
.create_order .inputWrap .lockedTherapist {position: absolute;left: 0px;top: 0px;height: 58px;border-radius: 2px;background-color: #f5f5f5;width: 58px;right: auto;z-index: 999;cursor: pointer;}
.create_order .inputWrap .lockedTherapist::after {position: absolute;width: 20px;height: 17px;content: "";opacity: 0.2;background-image: url(/user/assets/img/lock.svg);background-size: contain;background-repeat: no-repeat;left: 0;right: 0;top: 0;bottom: 2px;margin: auto;background-position: center;}
.create_order .inputWrap #lockedTherapist:checked ~ .lockedTherapist{background:#0dabb6}
.create_order .inputWrap #lockedTherapist:checked ~ .lockedTherapist::after {opacity: 1; filter: contrast(0) brightness(6.5);}
.create_order .inputWrap #therapist[value="0"] ~ .lockedTherapist{display:none}
.create_order .inputWrap #lockedTherapist:checked ~ #therapistName{pointer-events:none;background:#effcfd !important}
</style>
            <div class="treats">
                <div class="title">תוספות כלולות</div>
                <div class="items">

                <?php
$que = "SELECT * FROM `sites_treatment_extras` AS `se` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) WHERE se.active = 1 AND se.included = 1 AND se.siteID = " . $order['siteID'];
$extras = udb::key_list($que, 'extraType');
//print_r($extras);

    foreach($extras['package'] as $extra){
        if(!$extra['included'])continue;
?>
        <div>
            <?=$extra['extraName']?>
			<div><?=$extra['description']?></div>
        </div>
<?php
    }
?>

                </div>
            </div>
        </form>
    </div>
</div>
<?
$_timer->log();
$city_list = json_encode(udb::full_list("SELECT settlementID , TITLE AS clientCity FROM `settlements` WHERE 1"));
?>
<script>
(typeof 'autoComplete' == 'function' ? Promise.resolve() : $.getScript('/user/assets/js/autoComplete.min.js')).then(function(){
    var cache = {tm: 0, cache: []};

    function caller(str){
        if (str.length < 3)
            return Promise.resolve([]);

        return new Promise(function(res){
            var c = {text:str, res:res};

            cache.cache.push(c);
            if (cache.tm)
                window.clearTimeout(cache.tm);

            cache.tm = window.setTimeout(function(){
                var last = cache.cache.pop();

                for(var i = 0; i < cache.cache.length; ++i)
                    cache.cache[i].res([]);

                cache.tm = null;
                cache.cache = [];

                last.res($.get('ajax_client.php', 'act=clientInfo&sid=<?=($siteID ?: SITE_ID)?>&val=' + last.text).then(res => res.clients));
            }, 500);
        });
    }


    $('.ac-inp', '#create_orderSpa').each(function(){
        var inp = this;

        const autoCompleteJS = new autoComplete({
            selector: '#' + inp.id,
            data: {
                src: caller,
                cache: false,
                keys: ['_text', 'email']
            },
            resultsList: {
                maxResults: 20
            },
            resultItem: {
                element: function(item, data){
                    item.setAttribute("data-auto", JSON.stringify(data.value));
                },
                highlight: {
                    render: true
                }
            },
            events: {
                list: {
                    click: function(e){
                        var li = e.target.nodeName.toUpperCase() == 'LI' ? e.target : $(e.target).closest('li').get(0), data = JSON.parse(li.dataset.auto || '{}'),
                                form = document.getElementById('create_orderSpa'), el;

                        Object.keys(data).forEach(function(key){
                            if (data[key] && (el = form.querySelector('input[name="' + key + '"]')))
                                el.value = String(data[key]).trim();
                        });

                        this.setAttribute('hidden', '');
                    }
                }
            }
        });
    });


	$('#create_orderSpa .ac-inp2').each(function(){
		var inp = this;
		const autoCompleteJS = new autoComplete({
			selector: '#' + inp.id,
			data: {
				src: <?=$city_list?>,
				cache: false,
				keys: ['clientCity']
			},
			resultsList: {
				maxResults: 20
			},
			resultItem: {
				element: function(item, data){
					item.setAttribute("data-auto", JSON.stringify(data.value));
				},
				highlight: {
					render: true
				}
			},
			events: {
				input: {
					focus: (event) => {		
						//debugger;
						searchval = ($(this).val()? $(this).val() : "*" );
						console.log(searchval);							
						autoCompleteJS.open();
						$(this).closest('.inputWrap').css('z-index','9')
					},
					blur: (event) => {	
						
						console.log("blur");							
						$(this).closest('.inputWrap').attr('style','');												
						autoCompleteJS.close();
					}
				},
				list: {
					click: function(e){							
						var li = e.target.nodeName.toUpperCase() == 'LI' ? e.target : $(e.target).closest('li').get(0), data = JSON.parse(li.dataset.auto || '{}'),
								form = document.getElementById('orderForm'), el;

						Object.keys(data).forEach(function(key){
							//debugger;
							if (data[key] && key=="settlementID" && (el = form.querySelector('input[name="settlementID"]'))){
								$('input[name="settlementID"]').val(String(data[key]).trim());
								$('input[name="settlementID"]').addClass('valid');
							}

							if (data[key] && key=="clientCity" && (el = form.querySelector('input[name="clientCity"]'))){
								$('input[name="clientCity"]').val(String(data[key]).trim());
								$('.settlementName').html(String(data[key]).trim())
							}

							
						});
						$(e.target).closest('.inputWrap').next('.inputWrap').find('input').focus();
						this.setAttribute('hidden', '');
					}
				}
			}
		});
	});
});
</script>
<?php
            $result['html'] = ob_get_clean();
            $result['success'] = true;
$_timer->log();
            break;

        default:
            throw new Exception('Unknown operation code');
    }
}
catch (Exception $e){
    udb::query("UNLOCK TABLES");
    $result['error'] = $e->getMessage();
}
