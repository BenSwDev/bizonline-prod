<?php
class InSearch {
    const COMMON_TYPE_ID = 1;
    const ATTR_FREE_FOR_WEEKEND = 223;      // id of "free for weekend" attribute

    /*private static function extraPeople($table, $type, $adults, $kids){
        return ($adults ? " + " . $table . ".extraPriceAdultWeek" . $type . " * " . $adults : "") . ($kids ? " + " . $table . ".extraPriceKidWeek" . $type . " * " . $kids : "");
    }*/

    private static function extraPeople($table, $type, $pax){
        return ($pax['adults'] ? " + tmp.extraAdults * " . $table . ".extraPriceAdultWeek" . $type : "") . ($pax['kids'] ? " + tmp.extraKids * " . $table . ".extraPriceKidWeek" . $type : "");
    }

    private static function sortPax($pax){
        if (!count($pax) || !is_numeric(key($pax)))
            return $pax;

        usort($pax, function($a, $b){
            return (array_sum($a) <=> array_sum($b)) ?: ($a['adults'] <=> $b['adults'] ?: $a['kids'] <=> $b['kids']);
        });

        return $pax;
    }

    protected static function _sqlBaseFields($nights){
        if ($nights == 1)
            return ['weekday1', 'weekend1', ''];
        if ($nights == 2)
            return ['weekday2', 'weekend2', 'extraHour'];
        return ['weekday3', 'weekend3', 'extraHourEnd'];
    }

    protected static function _sqlPax($pax){
        $res = [];
        foreach($pax as $p)
            if ($p != ['adults' => 2, 'kids' => 0])
                $res[] = "(rooms.maxGuests >= " . array_sum($p) . " AND rooms.maxAdults >= " . intval($p['adults']) . " AND rooms.maxKids >= " . intval($p['kids']) . ")";

        return $res;
    }

    protected static function _filterByPax(array $pax, array $sites = [], array $rooms = [])
    {
        $res = [];
        foreach($pax as $p)
            if ($p != ['adults' => 2, 'kids' => 0])
                $res[] = "(rooms.maxGuests >= " . array_sum($p) . " AND rooms.maxAdults >= " . intval($p['adults']) . " AND rooms.maxKids >= " . intval($p['kids']) . ")";

        $cond = count($rooms) ? 'rooms.roomID IN (' . implode(',', $rooms) . ')' : "rooms.siteID IN (" . implode(',', $sites) . ")";

        $que = "SELECT rooms.roomID, rooms.siteID, rooms.roomCount, COUNT(spaces_type.id) AS `cnt` 
                FROM `rooms` LEFT JOIN `spaces` ON (spaces.roomID = rooms.roomID) LEFT JOIN `spaces_type` ON (spaces_type.id = spaces.spaceType AND spaces_type.isBedroom = 1) 
                WHERE " . $cond . " AND rooms.active = 1 " . (count($res) ? ' AND (' . implode(' OR ', $res) . ')' : '') . "
                GROUP BY `roomID` 
                ORDER BY NULL";

        if (count($sites))
            $que = "SELECT `siteID`, SUM(`roomCount` * GREATEST(`cnt`, 1)) AS `rc` 
                    FROM (" . $que . ") AS `tmp`
                    GROUP BY `siteID`
                    HAVING `rc` >= " . count($pax) . "
                    ORDER BY NULL";
        return udb::single_column($que);
    }

    public static function validateLang($langID, $sites){
        return ($langID == 1 || !$sites) ? $sites : udb::single_column("SELECT `siteID` FROM `sites_langs` WHERE `siteID` IN (" . (is_array($sites) ? implode(',', $sites) : $sites ?: 0) . ") AND `siteName` > '' AND `langID` = " . $langID);
    }

    public static function dates2nights($date1, $date2)
    {
        $dates = (strcmp($date1, $date2) > 0) ? [$date2, $date1] : [$date1, $date2];
        return round((strtotime($dates[1]) - strtotime($dates[0])) / (24 * 3600));
    }

    public static function filterByParams($params, $sites = [], $activeOnly = true)
    {
	
        $tables =  [];
        //$where  = $activeOnly ? ["sites.active = 1"] : ['1'];
        $where  = ["sites.active = 1"];

        $filtered = $sites ? (is_array($sites) ? $sites : [$sites]) : [];

        if ($params['attr']){               // filter by attribute(s)
            $que = "SELECT `siteID`, COUNT(`attrID`) AS `cnt` 
                    FROM `sites_attributes` 
                    WHERE `attrID` IN (" . (is_array($params['attr']) ? implode(',', array_map('intval', $params['attr'])) : intval($params['attr'])) . ")
                        AND " . (count($filtered) ? "`siteID` IN (" . implode(',', $filtered) . ")" : '1') . "
                    GROUP BY `siteID` 
                    HAVING `cnt` >= " . (is_array($params['attr']) ? count($params['attr']) : '1') . "
                    ORDER BY NULL";
            $tmp = udb::single_column($que);

            if (!count($tmp))           // no match by attributes combination
                return [];

            $filtered = $tmp;
        }

        if ($params['city'])               // search in settlement
            $where[] = "sites.settlementID = " . intval($params['city']);
        elseif ($params['area'])             // search in area
            $tables[] = "INNER JOIN `sites_areas` ON (sites.siteID = sites_areas.siteID AND sites_areas.areaID = " . intval($params['area']) . ")";
        elseif ($params['main_area']){      // search in zone
            $tmp = udb::single_column("SELECT `areaID` FROM `areas` WHERE `main_areaID` = " . intval($params['main_area']));
            $tables[] = "INNER JOIN `sites_areas` ON (sites.siteID = sites_areas.siteID AND sites_areas.areaID IN (" . (count($tmp) ? implode(',', $tmp) : '0') . "))";
        }

        if($params['type'] && $params['type'] != 1){              // search per room type
            $tables[] = "INNER JOIN `rooms` ON (rooms.siteID = sites.siteID) INNER JOIN `room_type_search` ON (rooms.roomID = room_type_search.roomID AND room_type_search.roomType IN (" . (is_array($params['type']) ? implode(',', $params['type']) : $params['type']) . "))";
            $where[]  = 'rooms.active = 1';
        }

        if (count($filtered))
            $where[] = "sites.siteID IN (" . implode(',', $filtered) . ")";

        $que = "SELECT DISTINCT sites.siteID
                FROM `sites` 
                    /*INNER JOIN `sites_domains` ON (sites.siteID = sites_domains.siteID AND sites_domains.domainID = " . ActivePage::$domainID . " AND sites_domains.active = 1)*/
                    ".implode(' ', $tables)."
                WHERE " . implode(' AND ', $where) . "
                ORDER BY NULL
                /*LIMIT 0 , 255*/";
        return udb::single_column($que);
    }


    public static function next_free($date, $nights = 1, $sites = [], $club = false, $pax = ['adults' => 2, 'kids' => 0]){
        $que = "SELECT `roomID` FROM `rooms` WHERE `active` = 1 AND `maxGuests` >= " . array_sum($pax) . " AND `maxAdults` >= " . $pax['adults'] . " AND `maxKids` >= " . $pax['kids'] . ($sites ? " AND `siteID` IN (" . implode(',', is_array($sites) ? $sites : [$sites]) . ")" : '');
        $rooms = udb::single_column($que);

        return SearchCache::next_free_rooms($date, $nights, $rooms, $club, $pax);
    }

    public static function categorySearch($params, $siteID = 0){
        return self::getPriceData(date('Y-m-d'), 1, 0, 0, $params, ['adults' => 2, 'kids' => 0]);

    }



    public static function searchFromPage(){
        $search = ActivePage::$search;

        return self::getPriceData($search['from'] ?: $search['default']['from'], $search['nights'] ?: 1, 0, 0, $search, $search['pax'] ?? []);
    }

    public static function filterSearchParams($data = []){
        $good = ['attr', 'type', 'city', 'area', 'main_area'];
        $result = [];

        foreach($data as $key => $val)
            if (in_array($key, $good))
                $result[$key] = $val;

        return $result;
    }
/*
    public static function freeSearch($txt, $langID, $check_only = false){
        $text = udb::escape_string($txt);

        $que = "(SELECT 'area' AS `type`, `areaID` AS `id` FROM `areas_text` WHERE `TITLE` = '" . $text . "' AND `langID` = " . $langID . ")
                UNION ALL
                (SELECT 'sett' AS `type`, `settlementID` AS `id` FROM `settlements_text` WHERE `TITLE` = '" . $text . "' AND `langID` = " . $langID . ")
                UNION ALL
                (SELECT 'main_area' AS `type`, `main_areaID` AS `id` FROM `main_areas_text` WHERE `TITLE` = '" . $text . "' AND `langID` = " . $langID . ")
                UNION ALL
                (SELECT 'attr' AS `type`, `attrID` AS `id` FROM `attributes_langs` WHERE `defaultName` = '" . $text . "' AND `langID` = " . $langID . ")";
        $loc = udb::single_row($que);

        // if found exact location - return search params
        if ($loc)
            return ['type' => self::COMMON_TYPE_ID, $loc['type'] => $loc['id']];

        // if found exact room type - return search params
        $type = udb::single_value("SELECT `id` FROM `roomTypesLangs` WHERE (`roomType` = '" . $text . "' OR `roomTypeMany` = '" . $text . "') AND `langID` = " . $langID);
        if ($type)
            return ['type' => $type];

        // if found exact attribute - return search params
        $attr = udb::single_value("SELECT `attrID` FROM `attributes_langs` WHERE `defaultName` = '" . $text . "' AND `langID` = " . $langID);
        if ($attr)
            return ['type' => self::COMMON_TYPE_ID, 'attr' => [$attr]];

        if ($check_only)
            return false;

        // return sites that match text
        $attr = udb::single_column("SELECT DISTINCT `attrID` FROM `attributes_langs` WHERE `defaultName` LIKE '%" . $text . "%' AND `langID` = " . $langID);

        $que = "SELECT DISTINCT sites.siteID
                FROM `sites` LEFT JOIN `sites_langs` ON (sites.siteID = sites_langs.siteID AND sites_langs.langID = " . $langID . ")
                     " . ($attr ? "LEFT JOIN `sites_attributes` ON (sites.siteID = sites_attributes.siteID AND sites_attributes.attrID IN (" . implode(',', $attr) . "))" : '') . "
                    LEFT JOIN `sites_areas` ON (sites.siteID = sites_areas.siteID) LEFT JOIN `areas_text` ON (areas_text.areaID = sites_areas.areaID AND areas_text.langID = " . $langID . ")
                    LEFT JOIN `settlements_text` AS `s` ON (s.settlementID = sites.settlementID AND s.langID = " . $langID . ")
                WHERE (" . ($attr ? "sites_attributes.attrID IS NOT NULL OR " : '') . " sites_langs.siteName LIKE '%" . $text . "%' OR sites_langs.owners LIKE '%" . $text . "%' OR areas_text.TITLE LIKE '%" . $text . "%' OR s.TITLE LIKE '%" . $text . "%')";
        return ['siteID' => udb::single_column($que) ?: [-1]];
    }
*/





    public static function freeSearch($txt, $langID, $check_only = false){
        $text = udb::escape_string($txt);

        $prm = [];

        $prm['type'] = udb::single_value("SELECT `id` FROM `roomTypesLangs` WHERE ('" . $text . "' LIKE CONCAT('', '%', `roomTypeMany`, '%') OR '" . $text . "' LIKE CONCAT('', '%', `roomType`, '%')) AND `langID` = " . $langID);
        $prm['area'] = udb::single_value("SELECT `areaID` FROM `areas_text` WHERE '" . $text . "' LIKE CONCAT('', '%', `TITLE`, '%') AND `TITLE` > '' AND `langID` = " . $langID);
        $prm['city'] = udb::single_value("SELECT `settlementID` FROM `settlements_text` WHERE '" . $text . "' LIKE CONCAT('', '%', `TITLE`, '%') AND `TITLE` > '' AND `langID` = " . $langID);
        $prm['main_area'] = udb::single_value("SELECT `main_areaID` FROM `main_areas_text` WHERE '" . $text . "' LIKE CONCAT('', '%', `TITLE`, '%') AND `TITLE` > '' AND `langID` = " . $langID);
        $prm['attr'] = udb::single_column("SELECT `attrID` FROM `attributes_langs` WHERE '" . $text . "' LIKE CONCAT('', '%', `defaultName`, '%') AND `defaultName` > '' AND `langID` = " . $langID);
	
        $prm = array_filter(array_map('intval', $prm));
        if (count($prm))
            return $prm;

        if ($check_only)
            return false;

        $que = "SELECT DISTINCT sites.siteID 
                FROM `sites` LEFT JOIN `sites_langs` ON (sites.siteID = sites_langs.siteID AND sites_langs.langID = " . $langID . ")
                    LEFT JOIN `sites_areas` ON (sites.siteID = sites_areas.siteID) LEFT JOIN `areas_text` ON (areas_text.areaID = sites_areas.areaID AND areas_text.langID = " . $langID . ")
                    LEFT JOIN `settlements_text` AS `s` ON (s.settlementID = sites.settlementID AND s.langID = " . $langID . ")
                WHERE (sites_langs.siteName LIKE '%" . $text . "%' OR sites_langs.owners LIKE '%" . $text . "%' OR areas_text.TITLE LIKE '%" . $text . "%' OR s.TITLE LIKE '%" . $text . "%')";
        return ['siteID' => udb::single_column($que) ?: [-1]];
    }
}
