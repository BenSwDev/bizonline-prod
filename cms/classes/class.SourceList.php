<?php
class SourceList {
    public static function site_list($siteID, $full = false, $include = null){
        $mult  = is_array($siteID);
        $sites = $mult ? implode(',', array_map('intval', $siteID)) : intval($siteID);

        $inc_c  = $include ? " OR a.key IN ('" . (is_array($include) ? implode("','", array_map('udb::escape_string', $include)) : udb::escape_string($include)) . "')" : "";
        $full_c = $full ? "1" : "(s.sourceID IS NULL OR s.active = 1)";

        $que = "SELECT a.id, a.shortname, a.fullname, a.key, a.letterSign, a.hexColor, a.siteType, a.payTypeKey, a.active AS `mainActive`, sites.siteID
                    , IFNULL(s.showPrice, a.showPrice) AS `showPrice`, IFNULL(s.active, a.active) AS `active`
                FROM `arrivalSources` AS `a` INNER JOIN `sites` ON (sites.siteID IN (" . $sites . ") AND a.siteType & sites.siteType)
                    LEFT JOIN `siteArrivalSources` AS `s` ON (s.siteID = sites.siteID AND s.sourceID = a.id)
                    LEFT JOIN `sitePayTypes` AS `p` ON (p.siteID = sites.siteID AND p.active = 1 AND p.paytypekey = a.payTypeKey)
                WHERE (a.active = 1 AND (a.payTypeKey = '' OR p.paytypekey IS NOT NULL) AND " . $full_c . ") " . $inc_c . "
                ORDER BY a.showOrder";
        $list = udb::key_row($que, ['siteID', 'key']);

        $coupons = udb::key_list("SELECT * FROM `customPayTypes` WHERE `siteID` IN (" . $sites . ") AND `active` = 1", ['siteID', 'parent']);
        foreach($list as $sid => &$clist)
            foreach($clist as &$source)
                if (isset($coupons[$sid][$source['id']]))
                    $source['coupons'] = $coupons[$sid][$source['id']];
        unset($source, $clist);

        return $mult ? $list : $list[$sites];
    }


    public static function full_list($veryFull = false){
        return udb::key_row("SELECT * FROM `arrivalSources` WHERE " . ($veryFull ? "1" : "`active` = 1") . " ORDER BY `showOrder`", 'key');
    }

}
