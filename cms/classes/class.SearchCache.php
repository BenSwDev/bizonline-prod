<?php
class SearchCache {
    const NIGHTS_MIN = 1;
    const NIGHTS_MAX = 3;

    public static function rebuild_price_cache($from, $till, $extra = [])
    {
        $today = date('Y-m-d');

        // creating insert/update conditions
        $where  = ["year.date BETWEEN '" . $from . "' AND '" . $till . "' + INTERVAL 2 DAY"];
        $where2 = ["a.date BETWEEN '" . $from . "' AND '" . $till . "'"];

        if ($extra['roomID']){               // search only given rooms
            $tmp = is_array($extra['roomID']) ? implode(',', $extra['roomID']) : intval($extra['roomID']);

            $where[]  = "rooms.roomID IN (" . $tmp . ")";
            $where2[] = "a.roomID IN (" . $tmp . ")";
        }
        elseif ($extra['siteID']){                    // search only given sites
            $tmp = is_array($extra['siteID']) ? implode(',', $extra['siteID']) : intval($extra['siteID']);

            $where[]  = "sites.siteID IN (" . $tmp . ")";
            $where2[] = "a.siteID IN (" . $tmp . ")";
        }

        $tmptab = 'cache' . mt_rand();

        $where  = implode(' AND ', $where);
        $where2 = implode(' AND ', $where2);

        // temp table of inserting/updating rooms
        $que = "CREATE TEMPORARY TABLE `" . $tmptab . "` ENGINE=MEMORY
                    SELECT rooms.roomID, rooms.siteID, year.date, year.weekday
                        , IFNULL(p3.periodID, IF(holidays.holidayID, p2.periodID, p1.periodID)) as `periodID`
                        , IF(p3.periodID OR holidays.holidayID, p2.periodID, p1.periodID) as `bpID`
                        , IF(rp3.roomID, p3.periodID, IF(p3.periodID OR holidays.holidayID, IF(rp2.roomID, p2.periodID, p1.periodID), p1.periodID)) as `ppID`
                    FROM `year` INNER JOIN `rooms` INNER JOIN `sites` USING(`siteID`)
                        INNER JOIN `sites_periods` as `p1` ON (sites.siteID = p1.siteID AND p1.periodType = 1)
                        INNER JOIN `sites_periods` as `p2` ON (sites.siteID = p2.siteID AND p2.periodType = 2)
                        LEFT JOIN `sites_periods` as `p3` ON (sites.siteID = p3.siteID AND p3.periodType = 0 AND year.date BETWEEN p3.dateFrom AND p3.dateTo)
                        LEFT JOIN `rooms_prices` as `rp2` ON (rp2.periodID = p2.periodID AND rooms.roomID = rp2.roomID AND rp2.day = year.weekday)
                        LEFT JOIN `rooms_prices` as `rp3` ON (rp3.periodID = p3.periodID AND rooms.roomID = rp3.roomID AND rp3.day = year.weekday)
                        LEFT JOIN `holidays` ON (year.date BETWEEN holidays.dateStart AND holidays.dateEnd)
                        LEFT JOIN `unitsDates` AS `ud` ON (ud.roomID = rooms.roomID AND ud.date = year.date)
                    WHERE rooms.active = 1 AND sites.active = 1 AND year.date BETWEEN '" . $from . "' AND '" . $till . "' + INTERVAL 2 DAY AND " . $where;
        udb::query($que);

        // inserting/updating room price for single night
        for($i = 1; $i <= 3; ++$i){
            $que = "INSERT INTO `cache_room_prices`(`roomID`, `siteID`, `date`, `periodID`, `nights`, `basePrice`, `extraAdult`, `extraKid`, `minNights`, `minVoid`)
                        SELECT a.roomID, a.siteID, a.date, a.periodID, " . $i . "
                            , IF( FIND_IN_SET(a.weekday, p.weekend), pr.weekend" . $i . ", pr.weekday" . $i . ") AS `base`
                            , IF( FIND_IN_SET(a.weekday, p.weekend), pr.halfNightEnd, pr.halfNight) AS `adult`
                            , IF( FIND_IN_SET(a.weekday, p.weekend), pr.allDayEnd, pr.allDay) AS `kid`
                            , IF( min1.periodID, IF( DATEDIFF(a.date, '" . $today . "') > min1.minVoid, min1.minNights, 1), IF(min2.periodID, IF( DATEDIFF(a.date, '" . $today . "') > min2.minVoid, min2.minNights, 1), 1)) AS `minNights`
                            , IFNULL(min1.minVoid, IFNULL(min2.minVoid, 1)) AS `minVoid`
                        FROM `" . $tmptab . "` AS `a` INNER JOIN `sites_periods` AS `p` USING(`periodID`)
                            INNER JOIN `rooms_prices` AS `pr` ON (a.ppID = pr.periodID AND a.roomID = pr.roomID AND a.weekday = pr.day)
                            LEFT JOIN `rooms_min_nights` AS `min1` ON (min1.roomID = a.roomID AND min1.periodID = a.bpID AND min1.weekday = a.weekday)
                            LEFT JOIN `rooms_min_nights` AS `min2` ON (min2.roomID = 0 AND min2.periodID = a.bpID AND min2.weekday = a.weekday)
                        WHERE 1
                    ON DUPLICATE KEY UPDATE `periodID` = VALUES(`periodID`), `basePrice` = VALUES(`basePrice`), `extraAdult` = VALUES(`extraAdult`), `extraKid` = VALUES(`extraKid`)";
            udb::query($que);
        }

        // updating min nights by actual period
        $que = "UPDATE `cache_room_prices` AS `cr` INNER JOIN `year` USING(`date`)
                    LEFT JOIN `rooms_min_nights` AS `min1` ON (min1.roomID = cr.roomID AND min1.periodID = cr.periodID AND min1.weekday = year.weekday)
                    LEFT JOIN `rooms_min_nights` AS `min2` ON (min2.roomID = 0 AND min2.periodID = cr.periodID AND min2.weekday = year.weekday)
                SET 
                    cr.minNights = IF( min1.periodID, IF( DATEDIFF(cr.date, '" . $today . "') > min1.minVoid, min1.minNights, 1), IF(min2.periodID, IF( DATEDIFF(cr.date, '" . $today . "') > min2.minVoid, min2.minNights, 1), 1)),
                    cr.minVoid = IFNULL(min1.minVoid, min2.minVoid)
                WHERE min1.roomID IS NOT NULL OR min2.roomID IS NOT NULL";
        udb::query($que);

        // clearing discounts for single nights
        $que = "UPDATE `cache_room_prices` AS `a` SET a.benefitID = 0, a.benefitValue = 0, a.benefitExtra = 0, a.clubID = 0, a.clubValue = 0, a.clubExtra = 0 WHERE " . $where2 . " AND a.nights = 1";
        udb::query($que);

        // updating room prices + clearing discounts for 2 nights
        $que = "UPDATE `cache_room_prices` AS `a`
                    INNER JOIN `cache_room_prices` AS `b` ON (b.roomID = a.roomID AND b.date = a.date + INTERVAL 1 DAY AND b.nights = a.nights)
                SET a.benefitID = 0, a.benefitValue = 0, a.benefitExtra = 0, a.clubID = 0, a.clubValue = 0, a.clubExtra = 0, a.basePrice = a.basePrice + b.basePrice, a.extraAdult = a.extraAdult + b.extraAdult, a.extraKid = a.extraKid + b.extraKid
                WHERE " . $where2 . " AND a.nights = 2";
        udb::query($que);

        // updating room prices + clearing discounts for 3 nights
        $que = "UPDATE `cache_room_prices` AS `a`
                    INNER JOIN `cache_room_prices` AS `b` ON (b.roomID = a.roomID AND b.date = a.date + INTERVAL 1 DAY AND b.nights = a.nights)
                    INNER JOIN `cache_room_prices` AS `c` ON (c.roomID = a.roomID AND c.date = a.date + INTERVAL 2 DAY AND c.nights = a.nights)
                SET a.benefitID = 0, a.benefitValue = 0, a.benefitExtra = 0, a.clubID = 0, a.clubValue = 0, a.clubExtra = 0, a.basePrice = a.basePrice + b.basePrice + c.basePrice, a.extraAdult = a.extraAdult + b.extraAdult + c.extraAdult, a.extraKid = a.extraKid + b.extraKid + c.extraKid
                WHERE " . $where2 . " AND a.nights = 3";
        udb::query($que);

        // global query
        /*$que = "UPDATE `cache_room_prices` AS `a`
                    INNER JOIN `cache_room_prices` AS `b` ON (b.roomID = a.roomID AND b.date = a.date + INTERVAL 1 DAY AND b.nights = a.nights)
                    INNER JOIN `cache_room_prices` AS `c` ON (c.roomID = a.roomID AND c.date = a.date + INTERVAL 2 DAY AND c.nights = a.nights)
                SET a.benefitID = 0, a.benefitValue = 0
                    , a.basePrice = CASE a.nights
                                        WHEN 2 THEN a.basePrice + b.basePrice
                                        WHEN 3 THEN a.basePrice + b.basePrice + c.basePrice
                                        ELSE a.basePrice
                                    END
                    , a.extraAdult = CASE a.nights
                                        WHEN 2 THEN a.extraAdult + b.extraAdult
                                        WHEN 3 THEN a.extraAdult + b.extraAdult + c.extraAdult
                                        ELSE a.extraAdult
                                    END
                    , a.extraKid = CASE a.nights
                                        WHEN 2 THEN a.extraKid + b.extraKid
                                        WHEN 3 THEN a.extraKid + b.extraKid + c.extraKid
                                        ELSE a.extraKid
                                    END
                WHERE " . $where2;
        udb::query($que);*/

        // clearing extra dates (needed for 2/3 nights calculations)
        udb::query("DELETE FROM `cache_room_prices` WHERE `date` > '" . $till . "'");
        // destroying temp table
        udb::query("DROP TEMPORARY TABLE `" . $tmptab . "`");
        // updating avilable rooms in cache
        self::update_available($from, $till, $extra);

        $que = "CREATE TEMPORARY TABLE `" . $tmptab . "` ENGINE=MEMORY
                    SELECT a.roomID, a.siteID, a.date, a.nights, a.basePrice, a.extraAdult, a.extraKid, b.*
                    FROM `cache_room_prices` AS `a` INNER JOIN `year` ON (a.date = year.date) 
                        INNER JOIN `benefits` as `b` ON (b.active = 1 AND b.siteID = a.siteID AND (b.orderActualFrom IS NULL OR '" . $today . "' BETWEEN b.orderActualFrom AND b.orderActualTill))
                        INNER JOIN `benefits_units` AS `u` ON (u.benefitID = b.benefitID AND (u.roomID = 0 OR u.roomID = a.roomID))
                    WHERE 
                        (b.benefitDates = 0 OR (a.date BETWEEN b.benefitDateStart AND b.benefitDateEnd))      /* active at date */
                        AND " . ($extra['customer'] ? '1' : 'b.benefitTo = 0') . "      /* for customer club or all */ 
        ";
    }

    public static function update_available($from, $till, $extra = []){
        $where = ["c.date BETWEEN '" . $from . "' AND '" . $till . "'"];
        if ($extra['roomID'])               // search only given rooms
            $where[]  = "c.roomID IN (" . (is_array($extra['roomID']) ? implode(',', $extra['roomID']) : intval($extra['roomID'])) . ")";
        elseif ($extra['siteID'])           // search only given sites
            $where[]  = "c.siteID IN (" . (is_array($extra['siteID']) ? implode(',', $extra['siteID']) : intval($extra['siteID'])) . ")";

        $que = "UPDATE (
                    SELECT c.roomID, c.date, c.nights, MIN(IFNULL(u.free, rooms.roomCount)) as `cnt`
                    FROM `cache_room_prices` AS `c` INNER JOIN `rooms` USING(`roomID`)
                        LEFT JOIN `unitsDates` AS `u` ON (c.roomID = u.roomID AND u.date BETWEEN c.date AND c.date + INTERVAL (c.nights - 1) DAY)
                    WHERE " . implode(' AND ', $where) . "
                    GROUP BY c.roomID, c.date, c.nights
                    ORDER BY NULL
                ) AS `tmp` INNER JOIN `cache_room_prices` AS `cr` USING(`roomID`, `date`, `nights`) 
                SET cr.avail = tmp.cnt, cr.minNights = IF(tmp.cnt <= 0 OR cr.nights >= cr.minNights, 1, cr.minNights)
                WHERE 1";
        udb::query($que);
    }

    public static function is_usable($date, $nights){
        if ($nights < self::NIGHTS_MIN || $nights > self::NIGHTS_MAX)
            return false;

        $sql = udb::single_value("SELECT COUNT(*) FROM `cache_room_prices` WHERE `date` = '" . $date . "'");
        return !!intval($sql);
    }

    /*public static function check_dates($from, $till, $complete = true){
        $que = "SELECT MIN(`date`) AS `min`, MAX(`date`) AS `max` FROM `cache_room_prices` WHERE 1";
        list($min, $max) = udb::single_row($que, UDB_NUMERIC);

        return $complete ? ($from >= $min && $till <= $max) : ($from <= $max && $till >= $min);
    }*/

    public static function update_dates($from, $till)
    {
        $que = "SELECT MIN(`date`) AS `min`, MAX(`date`) AS `max` FROM `cache_room_prices` WHERE 1";
        list($min, $max) = udb::single_row($que, UDB_NUMERIC);

        if ($from <= $max && $till >= $min)
            self::rebuild_price_cache($from < $min ? $min : $from, $till > $max ? $max : $till);
    }

    public static function update_sites($sites)
    {
        $que = "SELECT MIN(`date`) AS `min`, MAX(`date`) AS `max` FROM `cache_room_prices` WHERE 1";
        list($min, $max) = udb::single_row($que, UDB_NUMERIC);

        self::rebuild_price_cache($min, $max, ['siteID' => $sites]);
    }

    public static function update_rooms($rooms)
    {
        $que = "SELECT MIN(`date`) AS `min`, MAX(`date`) AS `max` FROM `cache_room_prices` WHERE 1";
        list($min, $max) = udb::single_row($que, UDB_NUMERIC);

        self::rebuild_price_cache($min, $max, ['roomID' => $rooms]);
    }

    /*********************************** select functions ***********************************/
    public static function get_data($date, $nights, $rooms = [], $sites = [], $club = false, $pax = ['adults' => 2, 'kids' => 0], $filter = 'siteID', $rev = false){
        $tables = [];
        $where  = ["c.nights = " . $nights . " AND c.basePrice > 0"];
        if ($rooms)
            $where[] = "c.roomID IN (" . implode(',', is_array($rooms) ? $rooms : [$rooms]) . ")";
        elseif ($sites)
            $where[] = "c.siteID IN (" . implode(',', is_array($sites) ? $sites : [$sites]) . ")";

        if ($date[0] == '`')
            $tables[] = "INNER JOIN " . $date . " AS `t` ON (t.roomID = c.roomID AND t.date = c.date)";
        elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            $where[] = "c.date = '" . $date . "'";
        else
            $tables[] = "INNER JOIN (" . $date . ") AS `t` ON (t.roomID = c.roomID AND t.date = c.date)";

        $dPre = $club ? 'club' : 'benefit';
        /*$ep   = ['price' => [], 'disc' => []];
        if ($pax['adults'] > 2){
            $ep['price'][] = ($pax['adults'] - 2) . ' * c.extraAdult';
            $ep['disc'][]  = 'IF(c.' . $dPre . 'Extra < 1, ' . ($pax['adults'] - 2) . ' * c.extraAdult * c.' . $dPre . 'Extra, ' . ($pax['adults'] - 2) . ' * c.' . $dPre . 'Extra)';
        }
        if ($pax['kids'] > 0){
            $ep['price'][] = $pax['kids'] . ' * c.extraKid';
            $ep['disc'][]  = 'IF(c.' . $dPre . 'Extra < 1, ' . $pax['kids'] . ' * c.extraKid * c.' . $dPre . 'Extra, ' . $pax['kids'] . ' * c.' . $dPre . 'Extra)';
        }

        foreach($ep as &$arr)
            $arr = count($arr) ? '(' . implode(' + ', $arr) . ')' : '0';
        unset($arr);*/

        $que = "SELECT `roomID`, SUM(`available`) AS `available`, `roomCount`, `minNights`, `minVoid`, `date` AS `from`, `date` + INTERVAL " . $nights . " DAY AS `till`, `siteID`, `basePrice`
                    , ROUND(`basePrice` - `discount`) AS `realPrice`, year.`weekday`, `discID` AS `discountType`, b.textLong as `discountText`
                FROM (
                    SELECT c.roomID, c.date, c.siteID, c.avail AS `available`, c." . $dPre . "ID AS `discID`, cr.roomCount, c.minNights, c.minVoid
                        , c.basePrice + GREATEST(0, " . ($pax['adults'] ?? 2) . " - cr.basisGuests) * c.extraAdult + " . ($pax['kids'] ?? 0) . " * c.extraKid AS `basePrice`
                        , c." . $dPre . "Value + IF(c." . $dPre . "Extra < 1, GREATEST(0, " . ($pax['adults'] ?? 2) . " - cr.basisGuests) * c.extraAdult * c." . $dPre . "Extra, GREATEST(0, " . ($pax['adults'] ?? 2) . " - cr.basisGuests) * c." . $dPre . "Extra) 
                            + IF(c." . $dPre . "Extra < 1, " . ($pax['kids'] ?? 0) . " * c.extraKid * c." . $dPre . "Extra, " . ($pax['kids'] ?? 0) . " * c." . $dPre . "Extra) AS `discount`
                    FROM `cache_room_prices` AS `c` INNER JOIN `rooms` AS `cr` USING(`roomID`)
                        " . implode(' ', $tables) . "
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY c.date " . ($rev ? 'DESC' : 'ASC') . ", IF(c.avail, 0, 1), IF(`minNights`, `minNights`, 99), `basePrice` - `discount`
                ) AS `tmp` INNER JOIN `year` USING(`date`)
                    LEFT JOIN `benefits_langs` AS `b` ON (tmp.discID = b.benefitID AND b.langID = " . ActivePage::$langID . ")
                GROUP BY `" . $filter . "`
                ORDER BY NULL"; //echo $que;
        return udb::key_row($que, $filter);
    }


    public static function next_free_rooms($date, $nights = 1, $rooms = [], $club = false, $pax = ['adults' => 2, 'kids' => 0]){
        $wday = (date('w', strtotime($date)) + 6) % 7;

        $base = $nextD = $nextW = [];
        $dateCond = ($nights > 1) ? "u.date BETWEEN c.date AND c.date + INTERVAL " . ($nights - 1) . " DAY" : "u.date = c.date";

        if (strcmp($date, date('Y-m-d')) > 0){
            $que = "SELECT c.roomID, MAX(c.date) AS `date`
                    FROM `cache_room_prices` AS `c` LEFT JOIN `unitsDates` AS `u` ON (u.roomID = c.roomID AND u.free = 0 AND " . $dateCond . ")
                    WHERE c.date < '" . $date . "' AND c.nights = " . $nights . " AND u.roomID IS NULL
                        " . ($rooms ? " AND c.roomID IN (" . implode(',', is_array($rooms) ? $rooms : [$rooms]) . ")" : '') . "
                    GROUP BY c.roomID
                    ORDER BY NULL";
            $base = self::get_data($que, $nights, [], [], $club, $pax, 'siteID', true);
        }

        $que = "SELECT c.roomID, MIN(c.date) AS `date`
                FROM `cache_room_prices` AS `c` LEFT JOIN `unitsDates` AS `u` ON (u.roomID = c.roomID AND u.free = 0 AND " . $dateCond . ")
                WHERE c.date >= '" . $date . "' AND c.nights = " . $nights . " AND u.roomID IS NULL
                    " . ($rooms ? " AND c.roomID IN (" . implode(',', is_array($rooms) ? $rooms : [$rooms]) . ")" : '') . "
                GROUP BY c.roomID
                ORDER BY NULL";
        $nextD = self::get_data($que, $nights, [], [], $club, $pax, 'siteID');
                
        $que = "SELECT c.roomID, MIN(c.date) AS `date`
                FROM `cache_room_prices` AS `c` LEFT JOIN `unitsDates` AS `u` ON (u.roomID = c.roomID AND u.free = 0 AND " . $dateCond . ")
                WHERE c.date > '" . $date . "' AND WEEKDAY(c.date) = " . $wday . " AND c.nights = " . $nights . " AND u.roomID IS NULL
                    " . ($rooms ? " AND c.roomID IN (" . implode(',', is_array($rooms) ? $rooms : [$rooms]) . ")" : '') . "
                GROUP BY c.roomID
                ORDER BY NULL";
        $nextW = self::get_data($que, $nights, [], [], $club, $pax, 'siteID');

        foreach($nextD as $sid => $data){
            if ($base[$sid])
                $base[$sid][] = $data;
            else
                $base[$sid] = [$data];

            if ($nextW[$sid])
                $base[$sid][] = $nextW[$sid];
        }

        return $base;
    }
}



/*include "functions.php";

$time = microtime(true);

SearchCache::rebuild_price_cache('2019-06-18', '2019-07-18');

//SearchCache::get_data('2019-06-21', 3, [], [6,10,16788,15675,15721,15734], false, ['adults' => 2, 'kids' => 0], 'siteID', false);

echo microtime(true) - $time;
*/
