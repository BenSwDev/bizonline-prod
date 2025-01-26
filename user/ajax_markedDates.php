<?php
require_once "auth.php";
include "functions.php";

$result = new JsonResult();

try {
    switch($_POST['act']){
        case 'extraPrices':
            $input = typemap($_POST, [
                'ssid' => 'int',
                'orid' => 'int',
                'date' => 'UtilsDate::date2db'
            ]);

            $result['prices'] = [];

            if (!$input['ssid'] || !$_CURRENT_USER->has($input['ssid']))
                break;

            $siteID = $input['ssid'];
            if ($siteID != 392)
                break;

            $result['sid'] = $siteID;

            // pulling only extras that have changing prices depending on date
            $eprices  = udb::key_row("SELECT * FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) WHERE s.siteID = " . $siteID . " AND s.active = 1 AND s.priceWE + s.priceWE2 + s.priceWE3 > 0", 'extraID');
            if (!$eprices)
                break;

            // if existing order and there are treatments - change of date doesn't matter
            $trCount = 0;
            if ($input['orid']){
                $trCount = udb::single_value("SELECT COUNT(*) FROM `orders` WHERE `parentOrder` = " . $input['orid'] . " AND `status` = 1 AND `orderID` <> `parentOrder`");
                if ($trCount)
                    break;
            }

            $que = "SELECT IFNULL(b.isWeekend2, a.isWeekend2) AS `isWeekend2`
                FROM `sites_weekly_hours` AS `a` 
                    LEFT JOIN `sites_periods` AS `sp` ON (sp.siteID = a.siteID AND sp.periodType = 0 AND sp.dateFrom <= '" . $input['date'] . "' AND sp.dateTo >= '" . $input['date'] . "')
                    LEFT JOIN `sites_weekly_hours` AS `b` ON (b.siteID = a.siteID AND b.holidayID = -sp.periodID AND b.weekday = a.weekday AND b.active = 1)
                WHERE a.holidayID = 0 AND a.siteID = " . $siteID . " AND a.weekday = " . date('w', strtotime($input['date']));
            $isWeekend2 = udb::single_value($que);

            $newPrices = [];
            foreach($eprices as $extraID => $prs){
                if ($prs['extraType'] == 'package'){
                    $pin = min(3, max(1, $trCount));
                    $newPrices[$extraID] = ($prs['price' . $pin] ?: $prs['price' . ($pin - 1)] ?: $prs['price1']) + ($isWeekend2 ? $prs['priceWE'] : 0);
                }
                elseif ($prs['extraType'] == 'rooms'){
                    $newPrices[$extraID] = [1 => $prs['price1'] + ($isWeekend2 ? $prs['priceWE'] : 0)];

                    // extra hours
                    if ($prs['price2'] && round($prs['countMax'], 1) > round($prs['countMin'], 1))
                        for($l = $prs['countMin'] + 1; $l <= $prs['countMax']; ++$l)
                            $newPrices[$extraID][$l] = $newPrices[$extraID][1] + ($prs['price2'] + ($isWeekend2 ? $prs['priceWE2'] : 0)) * ($l - $prs['countMin']);

                    // overnight
                    if ($prs['price3'])
                        $newPrices[$extraID][99] = $prs['price3'] + ($isWeekend2 ? $prs['priceWE3'] : 0);
                }
                else
                    $newPrices[$extraID] = $prs['price1'] + ($isWeekend2 ? $prs['priceWE'] : 0);
            }

            $result['prices'] = $newPrices;
            break;

        default:
            $result['dates'] = returnMarkedDates($_POST['fromDate'], $_POST['toDate']);
    }
}
catch (Exception $e){
    $result['error']  = $e->getMessage();
    $result['status'] = $e->getCode() ?: 99;
}
