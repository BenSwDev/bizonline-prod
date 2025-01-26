<?php
class DatesManager {
    public static $cache = [];


    public static function current_rooms_state($date, $nights, $rooms){
        if (!is_array($rooms))
            $rooms = [intval($rooms)];

        $que = "SELECT `roomID`, `date`, GREATEST(rooms.roomCount - tmp.units, 0) AS `free` FROM (
                    SELECT rooms_units.roomID, year.date, COUNT(DISTINCT tfusa.unitID) AS `units`
                    FROM `year` INNER JOIN `rooms_units`
                        LEFT JOIN `tfusa` ON (tfusa.date = year.date AND tfusa.hour >= '17:00:00' AND tfusa.unitID = rooms_units.unitID)
                    WHERE year.date BETWEEN '" . $date . "' AND '" . $date . "' + INTERVAL " . ($nights - 1) . " DAY AND rooms_units.roomID IN (" . implode(',', $rooms) . ")
                    GROUP BY rooms_units.roomID, year.date
                ) AS `tmp` INNER JOIN `rooms` USING(`roomID`)
                WHERE 1";
        return udb::key_value($que, ['roomID', 'date'], 'free');
    }


    public static function update_wubook($date, $nights, $rooms, $orderID = 0){
        $avails = self::current_rooms_state($date, $nights, $rooms);

        $wuClient = new BizWubook;
        return $avails ? $wuClient->update_rooms($avails) : false;
    }


    public static function fill_period($unitID, $from, $to, $orderID = 0){
        $tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $ts_from = strtotime($from);
        $ts_till = strtotime($to);
        $insert  = [];

        for($i = $ts_from; $i < $ts_till; $i += 900){
            list($date, $time) = explode(' ', date('Y-m-d H:i', $i));
            $insert[] = "(" . $orderID . ", " . $unitID . ", '" . $date . "', '" . $time . ":00')";
        }

        if ($insert)
            udb::query("INSERT INTO `tfusa`(`orderID`, `unitID`, `date`, `hour`) VALUES" . implode(',', $insert));

        date_default_timezone_set($tz);
    }


    public static function getInOut($siteID, $dateIn, $dateOut){
        return [
            'checkIn'  => udb::single_value("SELECT sites.checkInHour FROM `sites` WHERE sites.siteID = " . $siteID),
            'checkOut' => udb::single_value("SELECT IF(year.weekday = 6, sites.checkOutHour, sites.checkOutHourSat) FROM `sites` INNER JOIN `year` WHERE sites.siteID = " . $siteID . " AND year.date = '" . $dateOut . "'")
        ];
    }


    public static function busy_day($unitID, $date, $domainID = 0){
        $endDate = date('Y-m-d', strtotime($date . ' +1 day'));
        $siteID  = udb::single_value("SELECT `siteID` FROM `rooms` INNER JOIN `rooms_units` USING(`roomID`) WHERE `unitID` = " . $unitID);

        if (self::$cache['inOut'][$siteID])
            $inOut = self::$cache['inOut'][$siteID];
        else
            $inOut = self::$cache['inOut'][$siteID] = self::getInOut($siteID, $date, $endDate);

        $orderData = [
            'domainID'  => $domainID,
            'siteID'    => $siteID,
            'timeFrom'  => $date . ' ' . $inOut['checkIn'],
            'timeUntil' => $endDate . ' ' . $inOut['checkOut'],
            'allDay'    => 1
        ];

        $orderID = udb::insert('orders', $orderData);

        udb::insert("orderUnits", [
            "orderID" => $orderID,
            "unitID"  => $unitID
        ]);

        self::fill_period($unitID, $orderData['timeFrom'], $orderData['timeUntil'], $orderID);

        return $orderID;
    }

    public static function direct_update($roomID, $date, $available, $domainID = 0){
        $current  = self::current_rooms_state($date, 1, [$roomID]);     // current state for room
        $diff     = $current[$roomID][$date] - abs($available);              // required change

        if (!$current || !$diff)        // if there's no current data or there's nothing to change - stop
            return;

        $units  = udb::single_column("SELECT `unitID` FROM `rooms_units` WHERE `roomID` = " . $roomID);      // all units
        $booked = udb::key_value("SELECT DISTINCT `unitID`, `orderID` FROM `tfusa` WHERE `unitID` IN (" . implode(',', $units) . ") AND `date` = '" . $date . "' AND `hour` >= '17:00:00'");   // booked units

        // diff > 0  - need to make some units unavailable
        if ($diff > 0){
            $free = array_diff($units, array_keys($booked));        // available units

            if ($free < $diff)      // not enough available units
                throw new Exception('Not enough units to book: need ' . $diff . ', has (' . implode(',', $free) . ')');

            sort($free, SORT_NUMERIC);
            for($i = 0; $i < $diff; ++$i)
                self::busy_day($free[$i], $date, $domainID);    // make unavailable
        }
        // $diff < 0 - need to free some units
        else {
            $diff = abs($diff);

            // if there aren't enough units to free - stop
            if (count($booked) < $diff)
                throw new Exception('Not enough units to free: need ' . $diff . ', has (' . implode(',', array_keys($booked)) . ')');

            // getting "fake" orders
            $orders = udb::key_value("SELECT u.unitID, u.orderID FROM `orders` INNER JOIN `orderUntis` AS `u` USING(`orderID`) WHERE orders.orderID IN (" . implode(',', $booked) . ") AND orders.allDay = 1");
            if (count($orders) < $diff)         // cannot free "real" orders - can only free "fake" ones
                throw new Exception('Not enough units to free: need ' . $diff . ', has ' . print_r($orders, true));

            krsort($orders, SORT_NUMERIC);
            foreach($orders as $unitID => $orderID){
                udb::query("DELETE FROM `orders` WHERE `orderID` = " . $orderID);
                udb::query("DELETE FROM `orderUnits` WHERE `orderID` = " . $orderID);
                udb::query("DELETE FROM `tfusa` WHERE `orderID` = " . $orderID);

                if (--$diff <= 0)       // stop once $diff reaches zero (freed enough units)
                    break;
            }
        }
    }
}
