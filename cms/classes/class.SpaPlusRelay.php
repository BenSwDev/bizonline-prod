<?php
class SpaPlusRelay {
    protected $bizID;
    protected $spID;

    protected $spURL = "https://www.spaplus.co.il/bizapi/";
    protected $spKey = "Kew0Rd!Kew0Rd!Kew0Rd!";

    public function __construct($siteID = 0){
        $this->bizID = 0;
        $this->spID  = 0;

        if ($siteID)
            $this->setSite($siteID);
    }

    protected function send($data, $format = 'form', $target = '')
    {
        switch($format){
            case 'raw':  $msg = $data; break;
            case 'form': case 'query': $msg = http_build_query($data); break;
            case 'json': $msg = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); break;
            default:
                throw new Exception("Unknown trasfer format: " . $format);
        }

        $url = $this->spURL . $target;

        $link = curl_init($url . (strpos($url, '?') ? '&' : '?') . 'key=' . $this->spKey);

        curl_setopt($link, CURLOPT_POST, true);
        curl_setopt($link, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($link, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($link, CURLOPT_MAXREDIRS, 5);
        curl_setopt($link, CURLOPT_FAILONERROR, true);
        curl_setopt($link, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($link, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($link, CURLOPT_POSTFIELDS, $msg);
        //curl_setopt($link, CURLINFO_HEADER_OUT, true);

        $result = curl_exec($link);
        $info   = curl_getinfo($link);

        curl_close($link);

        if ($info['http_code'] != 200)
            throw new Exception('Failed to send data. Info log: ' . print_r($info, true));

        return $result;
    }



    public function setSite($siteID){
        $outer = self::getSpaplusID($siteID);
        if (!$outer)
            throw new Exception("No spaplus ID for site " . $siteID);

        $this->bizID = intval($siteID);
        $this->spID  = intval($outer);

        return $this;
    }

    public function bizID(){
        return $this->bizID;
    }

    public function spaplusID(){
        return $this->spID;
    }



    public function sendPrices($data_only = false){
        if (!$this->bizID || !$this->spID)
            throw new Exception("Site ID is not set: " . $this->bizID . '-' . $this->spID);

        $prices = udb::single_list("SELECT t.spaplusID AS `treatmentID`, p.duratuion AS `duration`, p.price1, p.price2, p.price3 FROM `treatmentsPricesSites` AS `p` INNER JOIN `treatments` AS `t` USING(`treatmentID`) WHERE (p.price1 > 0 OR p.price2 > 0 OR p.price3 > 0) AND p.siteID = " . $this->bizID);
        if (!$prices && $data_only)
            return 'No prices found';

        foreach($prices as &$row)
            $row = array_filter($row);
        unset($row);

        return $this->send(['siteID' => $this->spID, 'prices' => $prices], 'json', 'pricesupdate.php?act=prices');
    }


    public static function getSpaplusID($siteID){
        return udb::single_value("SELECT `spaplusID` FROM `sites` WHERE `siteID` = " . intval($siteID)) ?: null;
    }
}
