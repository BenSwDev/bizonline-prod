<?php
class OrderSpaMain {
    protected $id;

    public function __construct($id = 0){
        $this->id = intval($id);
    }

    public function temp_get($name){
        return udb::single_value("SELECT `" . udb::escape_string($name) . "` FROM `orders` WHERE `orderID` = " . $this->id);
    }

    public function updatePrice($updateExtras = false){
        if (!$this->id)
            return 0;

        if ($updateExtras)
            $this->recalculate_extra_prices();

        $siteID = $this->temp_get('siteID');
        $rooms    = udb::single_value("SELECT SUM(`base_price`) AS `price` FROM `orderUnits` WHERE `orderID` = " . $this->id) ?: 0;
        list($treats, $minDate)  = udb::single_row("SELECT SUM(`price`) AS `price`, MIN(DATE(`timeFrom`)) AS `minDate` FROM `orders` WHERE `orderID` <> " . $this->id . " AND `parentOrder` = " . $this->id . " AND `status` = 1", UDB_NUMERIC) ?: [0, null];
        list($discount, $extras) = udb::single_row("SELECT `discount`, `extras` FROM `orders` WHERE `orderID` = " . $this->id, UDB_NUMERIC);

        $que = "SELECT s.packDiscount 
                FROM `sites_weekly_hours` AS `s` LEFT JOIN `sites_periods` AS `p` ON (p.periodID = -s.holidayID AND '" . $minDate . "' BETWEEN p.dateFrom AND p.dateTo) 
                WHERE s.siteID = " . $siteID . " AND `weekday` = " . date('w', strtotime($minDate)) . "
                    AND (s.holidayID = 0 OR p.periodID IS NOT NULL)
                ORDER BY s.holidayID
                LIMIT 1";
        $packDiscount = udb::single_value($que);

        $extra = 0;
        if ($extras)
            $extra = intval(json_decode($extras)->total ?? 0);

        $totalSum = $rooms + $treats + $extra;
        if ($packDiscount && round($totalSum * $packDiscount / 100, 2) > $discount)
            $discount = round($totalSum * $packDiscount / 100, 2);

        udb::update('orders', ['price' => $totalSum - ($discount ?: 0), 'discount' => $discount], '`orderID` = ' . $this->id);

        return $totalSum - $discount;
    }

    public function create($data)
    {
        // checking that all required fields exists
        $required = ['siteID'];
        foreach($required as $key)
            if (!$data[$key])
                throw new Exception(__CLASS__ . "->create: required key missing - " . $key);

        // filling nessesary data that can be produced automatically
        $data['SentReview'] = 0;

        if (!isset($data['mail_sent']))
            $data['mail_sent']  = -1;

        if (!$data['form_to_sign'])
            $data['form_to_sign'] = udb::single_value("SELECT `defaultAgr` FROM `sites_langs` WHERE `domainID` = 1 AND `langID` = 1 AND `siteID` = " . $data['siteID']) ?: 1;
//        if (!$data['showTimeFrom'])
//            $data['showTimeFrom'] = $data['timeFrom'];
//        if (!$data['showTimeUntil'])
//            $data['showTimeUntil'] = $data['timeUntil'];


        udb::query("LOCK TABLE `orders` WRITE");

        if (!$data['guid']){
            do {
                $data['guid'] = self::newGUID();
            } while(udb::single_value("SELECT `guid` FROM `orders` WHERE `siteID` = " . $data['siteID'] . " AND `guid` = '" . udb::escape_string($data['guid']) . "'"));
        }

        if (!$data['orderIDBySite'])
            $data['orderIDBySite'] = udb::single_value("SELECT MAX(`orderIDBySite`) FROM `orders` WHERE `siteID` = " . $data['siteID']) + 1;

        $this->id = udb::insert('orders', $data);
        udb::update('orders', ['parentOrder' => $this->id], "`orderID` = " . $this->id);

        udb::query("UNLOCK TABLES");

        return $this->id;
    }


    public function restore()
    {
        // order data for parent order
        $orderData = udb::single_row("SELECT * FROM `orders` WHERE `orderID` = " . $this->id . " AND `parentOrder` = `orderID`");
        if (!$orderData)
            throw new Exception("Cannot find booking " . $this->id);
        if ($orderData['status'] > 0)
            throw new Exception("הזמנה #" . $orderData['orderIDBySite'] . " לא בוטלה");

        // units for without treatment
        $units = udb::single_column("SELECT `unitID` FROM `orderUnits` WHERE `orderID` = " . $this->id);
        if ($units && !self::units_available($orderData['timeFrom'], $orderData['timeUntil'], $units, $this->id))
            throw new Exception("ישנם חדרים תפוסים בהזמנה #" . $orderData['orderIDBySite']);

        foreach($units as &$unit)
            $unit = ['unitID' => $unit, 'orderID' => $this->id, 'from' => $orderData['timeFrom'], 'till' => $orderData['timeUntil']];
        unset($unit);

        // treatments
        $treatments = udb::key_row("SELECT orders.* FROM `orders` WHERE `parentOrder` = " . $this->id . " AND `orderID` <> " . $this->id, 'orderID');
        foreach($treatments as $i => $treat){
            $tdate = substr($treat['timeFrom'], 0, 10);

            if ($treat['treatmentID'] && $treat['treatmentLen']){
                $treatName = udb::single_value("SELECT `treatmentName` FROM `treatments` WHERE `treatmentID` = " . $treat['treatmentID']);

                $price = udb::single_value("SELECT `price1` FROM `treatmentsPricesSites` WHERE `siteID` = " . $orderData['siteID'] . " AND `treatmentID` = " . $treat['treatmentID'] . " AND `duratuion` = " . $treat['treatmentLen']);
                if (!$price)
                    throw new Exception($treatName . " is not available for duration of " . $treat['treatmentLen'] . " minutes");
            }
            else
                $treatName = 'טיפול ' . ($i + 1);

            if ($treat['therapistID']){
                // checking if master still exists and working
                $ts = udb::single_row("SELECT * FROM `therapists` WHERE `therapistID` = " . $treat['therapistID'] . " AND (`active` = 1 OR `workerType` = 'fictive') AND (`workStart` IS NULL OR `workStart` <= '" . $tdate . "') AND (`workEnd` IS NULL OR `workEnd` >= '" . $tdate . "')");
                if (!$ts)
                    throw new Exception("aster for " . $treatName . " is inactive or no longer exists");

                if ($treat['treatmentMasterSex'] && !($ts['gender_self'] & $treat['treatmentMasterSex']))
                    throw new Exception($ts['siteName'] . " is " . ($ts['gender_self'] == 1 ? 'male' : 'female'));

                // checking if master still working with gender
                if ($ts['workerType'] != 'fictive'){
                    if ($treat['treatmentClientSex'] && !($ts['gender_client'] & $treat['treatmentClientSex']))
                        throw new Exception($ts['siteName'] . " doesn't work with " . ($treat['treatmentClientSex'] == 1 ? 'males' : 'females'));
                }

                // checking if master not busy already at required time
                $que = "SELECT `orders`.`orderID`, `orders`.`timeFrom`, `orders`.`timeUntil` 
                        FROM `orders` LEFT JOIN `orders` AS `parent` ON (`orders`.`parentOrder` = `parent`.`orderID`)
                        WHERE `orders`.`siteID` = " . $orderData['siteID'] . " AND `orders`.`therapistID` = " . $treat['therapistID'] . " AND `orders`.`timeFrom` < '" . $treat['timeUntil'] . "' AND `orders`.`timeUntil` > '" . $treat['timeFrom'] . "' 
                            AND `parent`.`status` = 1 
                        LIMIT 1";
                $busy = udb::single_row($que);
                if ($busy)
                    throw new Exception($ts['siteName'] . " is already booked from " . $busy['timeFrom'] . " till " . $busy['timeUntil']);
            }

            // checking if treatment room is available
            $unit = udb::single_value("SELECT `unitID` FROM `orderUnits` WHERE `orderID` = " . $treat['orderID']);
            if ($unit){
                if (!self::units_available($treat['timeFrom'], $treat['timeUntil'], [$unit], $treat['orderID']))
                    throw new Exception("חדר תפוס לטיפול " . $treatName);

                // adding unit to list of booked units
                $units[] = ['unitID' => $unit, 'orderID' => $treat['orderID'], 'from' => $treat['timeFrom'], 'till' => $treat['timeUntil']];
            }
        }

        // END OF CHECKS: IF ARRIVED HERE - NO PROBLEM WITH RESTORING ORDER

        foreach($units as $unit)
            self::add_tfusa($unit['orderID'], $unit['unitID'], $unit['from'], $unit['till']);

        udb::update('orders', ['status' => 1], '`parentOrder` = ' . $this->id);

        return true;
    }


    public function get_paid_sum(){
        return udb::single_value("SELECT SUM(`sum`) FROM `orderPayments` WHERE `complete` = 1 AND `cancelled` = 0 AND `subType` NOT IN ('card_test', 'freeze_sum') AND `orderID` = " . $this->id) ?: 0;
    }


    public static function units_available($from, $till, $units, $orderID = 0)
    {
        list($fromDate, $fromTime) = explode(' ', date('Y-m-d H:i:s', strtotime($from." -3 minutes")));
        list($tillDate, $tillTime) = explode(' ', date('Y-m-d H:i:s', strtotime($till." -3 minutes")));

        $orders_arr = $orderID ? udb::single_column("SELECT `orderID` FROM `orders` WHERE `parentOrder` = " . intval($orderID)) : [];
        if (!strcmp($fromDate, $tillDate))
            $que = "SELECT COUNT(*) FROM `tfusa` WHERE tfusa.unitID ".(is_array($units) ? " in (" . implode(',', $units) . ")" : " = " . $units)." 
					AND `date` = '" . $fromDate . "'
					AND `hour` BETWEEN '" . $fromTime . "' 
					AND '" . $tillTime . "'" . (count($orders_arr) ? " AND `orderID` NOT IN( " . implode(",",$orders_arr).")" : "");
        else
            $que = "SELECT COUNT(*) FROM `tfusa` WHERE tfusa.unitID ".(is_array($units) ? " in (" . implode(',', $units) . ")" : " = " . $units)." 
		            AND ((`date` = '" . $fromDate . "' AND `hour` > '" . $fromTime . "') 
		              OR (`date` = '" . $tillDate . "' AND `hour` < '" . $tillTime . "') 
		              OR (`date` < '" . $tillDate . "' AND `date` > '" . $fromDate . "')) " .
                (count($orders_arr) ? " AND `orderID` NOT IN( " . implode(",",$orders_arr).")" : "");

        return !udb::single_value($que);

    }

    public static function add_tfusa($orderID, $unitID, $from, $to)
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $tf   = strtotime($from);
        $diff = abs((strtotime($to) - $tf) / 60);
        $timeUnits = ceil($diff / 15);

        $que = [];
        for($i = 0; $i < $timeUnits; $i++){
            list($d, $t) = explode(' ', date("Y-m-d H:i:00", $tf + 900 * $i));
            $que[] = "(" . $orderID . "," . $unitID . ", '" . $d . "', '" . $t . "')";
        }

        if ($que)
            udb::query("INSERT INTO `tfusa`(`orderID`, `unitID`, `date`, `hour`) VALUES" . implode(',', $que));
        date_default_timezone_set($tz);
    }

    public static function newGUID()
    {
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    public static function cascadePrice($priceArr, $cnt, $group = false){
        if ($group && $priceArr['price3'])
            return $priceArr['price3'];
        elseif ($cnt > 1 && $priceArr['price2'])
            return $priceArr['price2'];
        return $priceArr['price1'];
    }

    public static function full_description($orderID, $siteID = 0, $extras = []){
        $desc = [];

        $que = "SELECT orders.treatmentLen, treatments.treatmentName FROM `orders` INNER JOIN `treatments` USING (`treatmentID`) WHERE orders.parentOrder = " . $orderID . " AND  orders.orderID <> " . $orderID . " ORDER BY orders.orderID";
        $treatments  = udb::single_list($que);
        foreach($treatments as $treatment)
            $desc[] = $treatment['treatmentName'] . " " . $treatment['treatmentLen'] . " דקות";

        if ($extras){
            if (!$siteID)
                $siteID = udb::single_value("SELECT `siteID` FROM `orders` WHERE `orderID` = " . $orderID);
            if (!is_array($extras))
                $extras = json_decode($extras, true);

            if ($extras){
                $que = "SELECT `extraID`, `extraName` FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) WHERE s.siteID = " . $siteID . " AND included = 0 ORDER BY e.showOrder";
                $names = udb::key_value($que);

                foreach($extras['extras'] as $extra)
                    if($names[$extra['extraID']])
                        $desc[] = $extra['count'] . " x " . $names[$extra['extraID']];
            }
        }

        return implode(" | ", $desc);
    }


    public function recalculate_extra_prices()
    {
        list($siteID, $extras) = udb::single_row("SELECT `siteID`, `extras` FROM `orders` WHERE `orderID` = " . $this->id, UDB_NUMERIC);
        if (!$extras)
            return;

        $extras = json_decode($extras, true);
        if (!$extras || empty($extras['extras']))
            return;

        $eprices  = udb::key_row("SELECT * FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) WHERE s.siteID = " . $siteID . " AND s.active = 1", 'extraID');

        list($baseDate, $trCount)  = udb::single_row("SELECT DATE(MIN(`timeFrom`)), COUNT(*) FROM `orders` WHERE `parentOrder` = " . $this->id . " AND `status` = 1 AND `orderID` <> `parentOrder`", UDB_NUMERIC);
        if (!$baseDate || $trCount == 0)
            $baseDate = udb::single_value("SELECT DATE(`timeFrom`) FROM `orders` WHERE `orderID` = " . $this->id) ?: '0000-00-00';

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
                $pin = min(3, max(1, $trCount));
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

        udb::update('orders', ['extras' => json_encode($extras, JSON_NUMERIC_CHECK)], "`orderID` = " . $this->id);
    }
}
