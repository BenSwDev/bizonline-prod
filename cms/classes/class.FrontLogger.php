<?php
class FrontLogger {
    public $status;
    public $ip;
    public $agent;

    protected static $temps = [];

    protected function _init(){
        if (!$this->status){
            if ($_SESSION['user_id'] || $_SESSION['permission'] || ($_SESSION['siteManager'] && count($_SESSION['siteManager']['sites'] ?? [])))
                $this->status = -10;
            elseif (!$this->agent || preg_match('/bot|crawl|spider|mediapartners|slurp|patrol|google|bing|yandex/i', $this->agent))
                $this->status = -5;
            else
                $this->status = udb::single_value("SELECT `ip` FROM `log_black_list` WHERE `ip` = '" . $this->ip . "'") ? -1 : 1;
        }
        return ($this->status > 0);
    }

    protected static function _makeTable($que){
        do {
            $table = 'fo_tmp_' . mt_rand();
        } while(in_array($table, self::$temps));

        self::$temps[] = $table;

        udb::query("CREATE TEMPORARY TABLE `" . $table . "` ENGINE = MEMORY " . $que);

        return $table;
    }

    protected static function _dropTable($table){
        $ind = array_search($table, self::$temps);

        if ($ind !== false){
            udb::query("DROP TEMPORARY TABLE `" . $table . "`");
            array_splice(self::$temps, $ind, 1);
        }
    }

    public function __construct($ip = '', $agent = ''){
        $this->status = 0;
        $this->ip     = preg_replace('/[^0-9\.]/', '', $ip ?: $_SERVER['REMOTE_ADDR']);
        $this->agent  = trim(filter_var($agent ?: $_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW));
    }

    public function log_page_view($type, $id = 0, $ref = ''){
        return $this->_init() ? udb::insert('log_view_page', [
            'pageType' => $type,
            'pageID'   => intval($id),
            'ip'       => $this->ip,
            'agent'    => $this->agent,
            'referer'  => $ref
        ]) : 0;
    }

    public function log_search_data($data, $type, $id = 0){
        return $this->_init() ? udb::insert('log_search', [
            'pageType' => $type,
            'pageID'   => intval($id),
            'ip'       => $this->ip,
            'data'     => json_encode($data, JSON_NUMERIC_CHECK)
        ]) : 0;
    }

    public function log_ad_view($logID, $adID, $pos = 0){
        if (!$this->_init())
            return;

        $log = [];
        if (intval($pos) > 0 && !is_array($adID))
            $log[] = "(" . intval($adID) . ", " . intval($logID) . ", " . intval($pos) .  ", '" . $this->ip . "')";
        elseif (!$pos && is_array($adID))
            foreach($adID as $ad)
                if (intval($ad['pos']) > 0)
                    $log[] = "(" . intval($ad['ad']) . ", " . intval($logID) . ", " . intval($ad['pos']) .  ", '" . $this->ip . "')";

        if (count($log))
            udb::query("INSERT INTO `log_view_ad`(`adID`, `logID`, `position`, `ip`) VALUES" . implode(',', $log));
    }

    public function log_site_view($siteID, $data = []){
        if (!$this->_init())
            return 0;

        $now = date('Y-m-d H:i:s');

        // checking if this click should be charged
        $duplicate = udb::single_value("SELECT COUNT(*) FROM `log_site_ip` WHERE `last_click` >= '" . $now . "' - INTERVAL 1 HOUR AND `ip` = '" . $this->ip . "' AND `siteID` = " . $siteID);

        udb::query("INSERT INTO `log_site_ip`(`ip`, `siteID`) VALUES('" . $this->ip . "', " . $siteID . ") ON DUPLICATE KEY UPDATE `last_click` = '" . $now . "'");

        $insert = [
            'clickTime' => $now,
            'siteID'    => $siteID,
            'logID'     => $data['logID'] ?? 0,
            'adID'      => $data['adID'] ?? 0,
            'g'         => $data['g'] ?? 0,
            'ip'        => $this->ip,
            'pay'       => $duplicate ? 0 : 1
        ];

        if ($data['ref'])
            $insert['referer'] = $data['ref'];
        if ($data['que'])
            $insert['que'] = $data['que'];

        return udb::insert('log_clicks', $insert);
    }

    public function get_search_data($id, $byIP = true){
        return udb::single_value("SELECT `data` FROM `log_search` WHERE `logID` = " . intval($id) . ($byIP ? " AND `ip` = '" . $this->ip . "'" : ""));
    }


    public static function update_daily_clicks($min, $max){
        // cancelling charge for black list ips, if exists
        udb::query("UPDATE `log_view_site` AS `log` INNER JOIN `log_black_list` AS `black` USING(`ip`) SET log.charge = 0 WHERE log.clickID >= " . $min . " AND log.clickID <= " . $max);

        // calculating amount of clicks during period per position
        $que = "INSERT INTO `log_vc_daily`(`adID`, `date`, `position`, `clicks`)
                    SELECT `adID`, DATE(`clickTime`) as `date`, `position`, COUNT(*) FROM `log_view_site` WHERE `adID` > 0 AND `clickID` >= " . $min . " AND `clickID` <= " . $max . " GROUP BY `adID`, `date`, `position` ORDER BY NULL
                ON DUPLICATE KEY UPDATE `clicks` = `clicks` + VALUES(`clicks`)";
        udb::query($que);

        return udb::single_column("SELECT DISTINCT `adID` FROM `log_view_site` WHERE `adID` > 0 AND `clickID` >= " . $min . " AND `clickID` <= " . $max);
    }

    public static function counter_html(){
?>
<script>
(function(){
    var url = '/ajax_statistics.php', sub = window.location.href.split('#').slice(1).join('#'), que = 'type=<?=ActivePage::$page['table']?>&id=<?=ActivePage::$page['ref']?>&pref=<?=urlencode($_SERVER['HTTP_REFERER'])?>&que=' + encodeURIComponent(window.location.search.substring(1)) + (sub.length ? '&' + sub : '');
    (typeof $ !== 'function') ? document.write('<img src="' + url + '?back=img&' + que + '" style="width:1px;height:1px" title="" />') : $(function(){
        $.post(url, que).then(function(res){
            console.log('stat res: ' + res.status);
        });

        window.setTracker && setTracker();
    });
})();
</script>
<?php
    }
}
