<?php
class MultiSearch extends InSearch {
public static function multi_find($date, $endDate, $nights, $where, $paxes){
    $bookDay = date('w', strtotime($date));
    $preBook = round((strtotime($date) - strtotime(date('Y-m-d'))) / 24 / 3600);

    $tmptab = 'tmpT' . mt_rand();        // name for temp table

udb::set_log(1);
    // creating temp table with all possible dates/rooms combinations
    $que = "CREATE TEMPORARY TABLE `" . $tmptab . "` (
                        `cDate` DATE NOT NULL DEFAULT '0000-00-00',
                        `wEnd` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                        `roomID` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
                        `roomCount` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
                        `siteID` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
                        `nightIndex` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
                        `basePeriod` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                        `periodID` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                        `ppID` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                        `frees` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
                        `maxGuests` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
                        `maxAdults` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
                        `maxKids` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
                        `basePax` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0
                    ) ENGINE=MEMORY
                    
                    SELECT year.date AS `cDate`, year.weekday AS `wEnd`, rooms.roomID, rooms.roomCount, rooms.siteID, DATEDIFF(year.date, '" . $date . "') AS `nightIndex`
                        , IF(p3.periodID OR holidays.holidayID, p2.periodID, p1.periodID) AS `basePeriod`
                        , IFNULL(p3.periodID, IF(holidays.holidayID, p2.periodID, p1.periodID)) as `periodID`
                        , IF(rp3.roomID, p3.periodID, IF(p3.periodID OR holidays.holidayID, IF(rp2.roomID, p2.periodID, p1.periodID), p1.periodID)) as `ppID`
                        , IFNULL(ud.free, rooms.roomCount) AS `frees`, rooms.maxGuests, rooms.maxAdults, rooms.maxKids, rooms.basisGuests AS `basePax`
                    FROM `year` INNER JOIN `rooms` ON (year.date BETWEEN '" . $date . "' AND '" . $endDate . "') INNER JOIN `sites` USING(`siteID`)
                        INNER JOIN `sites_periods` as `p1` ON (sites.siteID = p1.siteID AND p1.periodType = 1)
                        INNER JOIN `rooms_prices` as `rp1` ON (rp1.periodID = p1.periodID AND rooms.roomID = rp1.roomID AND rp1.day = year.weekday)
                        INNER JOIN `sites_periods` as `p2` ON (sites.siteID = p2.siteID AND p2.periodType = 2)
                        LEFT JOIN `rooms_prices` as `rp2` ON (rp2.periodID = p2.periodID AND rooms.roomID = rp2.roomID AND rp2.day = year.weekday)
                        LEFT JOIN `sites_periods` as `p3` ON (sites.siteID = p3.siteID AND p3.periodType = 0 AND year.date BETWEEN p3.dateFrom AND p3.dateTo)
                        LEFT JOIN `rooms_prices` as `rp3` ON (rp3.periodID = p3.periodID AND rooms.roomID = rp3.roomID AND rp3.day = year.weekday)
                        LEFT JOIN `holidays` ON (year.date BETWEEN holidays.dateStart AND holidays.dateEnd)
                        LEFT JOIN `unitsDates` AS `ud` ON (ud.roomID = rooms.roomID AND ud.date = year.date)
                    WHERE " . implode(' AND ', $where);
    udb::query($que);

    list($day_field, $end_field, $fixed_field) = self::_sqlBaseFields($nights);
    list($day_alt, $end_alt, $fixed_alt) = self::_sqlBaseFields($nights - 1);

    $void = 'IF(m4.periodID IS NOT NULL AND m4.minVoid < ' . $preBook . ', m4.minNights, 1)';
    for($i = 3; $i >= 1; --$i)
        $void = 'IF(m' . $i . '.periodID, IF(m' . $i . '.minVoid < ' . $preBook . ', m' . $i . '.minNights, 1), ' . $void . ')';

    $que = "SELECT i2.cDate AS `date`, i2.wEnd AS `weekday`, i2.roomID, i2.roomCount, i2.siteID, i2.maxGuests, i2.maxAdults, i2.maxKids, i2.basePax, i2.available
                , IF(i2.hasFixed, i2.fixedPrice, i2.basePrice) AS `basePrice`, i2.adultPrice, i2.kidPrice, IF(i2.hasFixed, i2.fixedAlt, i2.i2.baseAlt) AS `baseAlt`, i2.adultAlt, i2.kidAlt
                , IFNULL(m1.minVoid, IFNULL(m2.minVoid, IFNULL(m3.minVoid, IFNULL(m4.minVoid, 1)))) AS `minVoid`, " . $void . " AS `minNights`
            FROM (
                SELECT i1.*, MIN(i1.frees) AS `available`
                    , SUM(i1.bPrice) AS `basePrice`, SUM(i1.aPrice) AS `adultPrice`, SUM(i1.kPrice) AS `kidPrice`
                    , SUM(IF(i1.nightIndex >= " . ($nights - 1) . ", 0, i1.bPrice)) AS `baseAlt`, SUM(IF(i1.nightIndex >= " . ($nights - 1) . ", 0, i1.aPrice)) AS `adultAlt`, SUM(IF(i1.nightIndex >= " . ($nights - 1) . ", 0, i1.kPrice)) AS `kidAlt`
                    , SUM(i1.fPrice) AS `hasFixed`, SUM( IF(i1.nightIndex < 3, i1.fPrice, i1.bPrice) ) AS `fixedPrice`, SUM( IF(i1.nightIndex < 3, i1.faPrice, IF(i1.nightIndex >= " . ($nights - 1) . ", 0, i1.bPrice)) ) AS `fixedAlt`
                    , SUM(IF(`cDate` = '" . $date . "', `periodID`, 0)) AS `p1`, SUM(IF(`cDate` = '" . $date . "', `basePeriod`, 0)) AS `p2`
                FROM (
                    SELECT tmp.*
                        ,  IF(tmp.nightIndex < 3
                                , IF( FIND_IN_SET(tmp.wEnd, p.weekend), pr." . $end_field . ", pr." . $day_field . ")
                                , IF( FIND_IN_SET(tmp.wEnd, p.weekend), pr.halfDayEnd, pr.halfDay)
                            ) AS `bPrice`
                        , IF( FIND_IN_SET(tmp.wEnd, p.weekend), pr.halfNightEnd, pr.halfNight) AS `aPrice`
                        , IF( FIND_IN_SET(tmp.wEnd, p.weekend), pr.allDayEnd, pr.allDay) AS `kPrice`
                        , " . ($fixed_field ? "IF( tmp.nightIndex = 0, pr." . $fixed_field . ", 0)" : '0') . " AS `fPrice`
                        , " . ($fixed_alt ? "IF( tmp.nightIndex = 0, pr." . $fixed_alt . ", 0)" : '0') . " AS `faPrice`
                    FROM `" . $tmptab . "` AS `tmp`
                        INNER JOIN `sites_periods` as `p` ON (p.periodID = tmp.periodID)
                        INNER JOIN `rooms_prices` AS `pr` ON (pr.roomID = tmp.roomID AND pr.periodID = tmp.ppID AND pr.day = tmp.wEnd)
                    WHERE 1
                ) AS `i1`
                GROUP BY i1.roomID
                ORDER BY NULL
            ) AS `i2` 
                LEFT JOIN `rooms_min_nights` AS `m1` ON (i2.roomID = m1.roomID AND i2.p1 = m1.periodID AND m1.weekday = " . $bookDay . ")
                LEFT JOIN `rooms_min_nights` AS `m2` ON (m2.roomID = 0 AND i2.p1 = m2.periodID AND m2.weekday = " . $bookDay . ")
                LEFT JOIN `rooms_min_nights` AS `m3` ON (i2.roomID = m3.roomID AND i2.p2 = m3.periodID AND m3.weekday = " . $bookDay . ")
                LEFT JOIN `rooms_min_nights` AS `m4` ON (m4.roomID = 0 AND i2.p2 = m4.periodID AND m4.weekday = " . $bookDay . ")
            WHERE 1";
    $sites = udb::key_row($que, ['siteID', 'roomID']);

    // if nothing found - stop
    if (!$sites)
        return [];

    $que = "SELECT rooms.roomID, COUNT(*) AS `bedrooms`
            FROM `rooms` INNER JOIN `spaces` USING(`roomID`) INNER JOIN `spaces_type` ON (spaces.spaceType = spaces_type.id)
            WHERE rooms.active = 1 AND rooms.siteID IN (" . implode(',', array_keys($sites)) . ") AND spaces_type.isBedroom = 1";
    $spaces = udb::key_value($que);
udb::set_log(0);
    $result = [];

    foreach($sites as $sid => &$site){
        foreach($site as $roomID => &$room)
            $room['bedrooms'] = $spaces[$roomID] ?? 1;
        unset($room);

        $combs = self::rec_comb($site, $paxes);
IF ($_GET['aaaa'] == 1) print_r($combs);
        if (count($combs)){
            $full  = isset($result[$sid]) ? [$result[$sid]] : [];

            foreach($combs as $comb){
                $tmp = [
                    'rooms'     => [],
                    'available' => 0,
                    'minNights' => 1,
                    'minVoid'   => 99,
                    'basePrice' => 0,
                    'altPrice'  => 0
                ];

                foreach($comb as $c){
                    $room = $site[$c['roomID']];

                    $pc = array_sum($c['pax']);

                    $exAdult = max(0, $c['pax']['adults'] - $room['basePax']);
                    $exKids  = max(0, min($pc - $room['basePax'], $c['pax']['kids']));

                    $tmp = [
                        'rooms'     => array_merge($tmp['rooms'], [$c['roomID']]),
                        'available' => $tmp['available'] + $room['available'],
                        'minNights' => max($tmp['minNights'], $room['minNights']),
                        'minVoid'   => ($tmp['minNights'] < $room['minNights']) ? $room['minVoid'] : (($tmp['minNights'] > $room['minNights']) ? $tmp['minVoid'] : min($tmp['minVoid'], $room['minVoid'])),
                        'basePrice' => $tmp['basePrice'] + ($room['fixedPrice'] ?: $room['basePrice']) + $exAdult * $room['adultPrice'] + $exKids * $room['kidPrice'],
                        'altPrice'  => $tmp['baseAlt'] + $room['baseAlt'] + $exAdult * $room['adultAlt'] + $exKids * $room['kidAlt'],
                        'from'      => $date,
                        'till'      => $endDate,
                        'nights'    => $nights
                    ];
                }

                if ($tmp['basePrice'])
                    $full[] = $tmp;
            }

            if (count($full)){
                usort($full, 'self::sort_comb');
                $result[$sid] = reset($full);
            }
        }

        unset($full, $tmp, $room, $combs);
    }
    unset($site);

    return $result;
}

public static function sort_comb($a, $b){
    if (($a['available'] && $b['available']) || (!$a['available'] && !$b['available']))
        return ($a['basePrice'] <=> $b['basePrice']) ?: (($a['minNights'] <=> $b['minNights']) ?: ($b['minVoid'] <=> $a['minVoid']));
    elseif ($a['available'])
        return -1;
    return 1;
}

public static function rec_comb($rooms, $paxes, $current = [])
{
    if (!count($paxes)){
        usort($current, 'self::inner_sort');
        return [$current];
    }
    if (!count($rooms))
        return [];

    $list = [];
    $pax  = array_shift($paxes);
    $pc   = array_sum($pax);

    foreach($rooms as $rid => $r){
        // if can enter (minNights > 0) and still have units and can fit pax
        if ($r['minNights'] && ($r['_rc'] ?? $r['roomCount']) && ($r['_pax']['g'] ?? $r['maxGuests']) >= $pc && ($r['_pax']['a'] ?? $r['maxAdults']) >= $pax['adults'] && ($r['_pax']['k'] ?? $r['maxKids']) >= $pax['kids']){
            $rc   =  array_slice($rooms, 0, count($rooms), true);
            $curr =  array_slice($current, 0);

            $room =& $rc[$rid];

            // if no actual bedroom count - add new unit
            if (!isset($room['_bc']))
                $curr[] = ['roomID' => $rid, 'pax' => $pax];
            else {
                $tmp = array_pop($curr);
                $tmp['pax']['adults'] += $pax['adults'];
                $tmp['pax']['kids']   += $pax['kids'];
                $curr[] = $tmp;
            }

            // setting actual bedroom count
            $room['_bc']  = ($room['_bc'] ?? $room['bedrooms']) - 1;

            // if dropped to zero
            if ($room['_bc'] <= 0){
                unset($room['_bc'], $room['_pax']);

                // actual unit count
                $room['_rc'] = ($room['_rc'] ?? $room['roomCount']) - 1;
                if ($room['_rc'] <= 0)
                    unset($rc[$rid]);
            }
            else {
                $room['_pax'] = [
                    'g' => ($room['_pax']['g'] ?? $r['maxGuests']) - $pc,
                    'a' => ($room['_pax']['a'] ?? $r['maxAdults']) - $pax['adults'],
                    'k' => ($room['_pax']['k'] ?? $r['maxKids']) - $pax['kids']
                ];
            }

            $list = array_merge($list, self::rec_comb($rc, $paxes, $curr));

            unset($room);
        }
    }

    return array_values(array_unique($list, SORT_REGULAR));

        /*foreach($rc as $rid => &$room){
            // if can enter (minNights > 0) and still have bedrooms and can fit pax
            if ($room['minNights'] && $room['bedrooms'] && $room['maxGuests'] >= $pc && $room['maxAdults'] >= $pax['adults'] && $room['maxKids'] >= $pax['kids']){
                $exAdult = max(0, $pax['adults'] - $room['basePax']);
                $exKids  = max(0, min($pc - $room['basePax'], $pax['kids']));

                $current = [
                    'rooms' => array_merge($current['rooms'] ?? [], [$rid]),
                    'available' => min($current['available'] ?? 99, $room['available']),
                    'minNights' => max($current['minNights'] ?? 1, $room['minNights']),
                    'minVoid'   => (($current['minNights'] ?? 1) < $room['minNights']) ? $room['minVoid'] : ((($current['minNights'] ?? 1) > $room['minNights']) ? $current['minVoid'] : min($current['minVoid'] ?? 99, $room['minVoid'])),
                    'basePrice' => ($current['basePrice'] ?? 0) + $room['basePrice'] + $exAdult * $room['adultPrice'] + $exKids * $room['kidPrice'],
                    'altPrice'  => ($current['baseAlt'] ?? 0) + $room['baseAlt'] + $exAdult * $room['adultAlt'] + $exKids * $room['kidAlt'],
                ];
            }
        }*/
}

public static function inner_sort($a, $b){
    return ($a['roomID'] <=> $b['roomID']) ?: (array_sum($b['pax']) <=> array_sum($a['pax']));
}
}
