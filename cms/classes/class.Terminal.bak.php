<?php
class Terminal {
    protected $ownerID    = 0;

    protected static $_term_cache = [];

    public $has_cc_charge = false;
    public $has_cc_check  = false;
    public $has_tokens    = false;
    public $has_invoice   = false;
    public $has_swipe     = false;
    public $has_VAT       = true;
    public $has_freeze    = false;

    public static function bySite($siteID){
        if (!self::hasTerminal($siteID))
            throw new Exception("Client doesn't have terminal or terminal is inactive");

        $data = self::$_term_cache[$siteID];

        switch(strtoupper($data['masof_type'])){
            case 'MAX':
            case 'YAAD':
                $client = YaadPay::getTerminal($siteID);
                break;

            case 'CARDCOM':
                $client = new CardComBiz($siteID, $data['masof_number'], $data['masof_key'], $data['masof_pwd']);
                break;

            default:
                throw new Exception("Unknown terminal type");
        }

        return $client;
    }

    public static function hasTerminal($siteID, $target = ''){
        $key = $siteID . ($target ? '-' . $target : '');

        if (!isset(self::$_term_cache[$key])){
            if ($target)
                $data = udb::single_row("SELECT `masof_type`, `active` AS `masof_active`, `masof_number`, `masof_key`, `masof_pwd`, `flag_tokens` AS `masof_no_cvv` FROM `sites_terminals` WHERE `siteID` = " . $siteID . " AND `target` = '" . udb::escape_string($target) . "'");
            else
                $data = udb::single_row("SELECT `masof_type`, `masof_active`, `masof_number`, `masof_key`, `masof_pwd`, `masof_no_cvv` FROM `sites` WHERE `siteID` = " . $siteID);

            if (!$data || !$data['masof_type'] || !$data['masof_number'] || !$data['masof_key'] || !$data['masof_active'])
                self::$_term_cache[$key] = ['masof_type' => ''];
            else
                self::$_term_cache[$key] = $data;
        }

        return self::$_term_cache[$key]['masof_type'];
    }

    public static function hasCardCheck($siteID){
        return (self::hasTerminal($siteID) && self::$_term_cache[$siteID]['masof_no_cvv'] > 0);
    }

    public static function exception($error){
        throw new Exception($error);
    }
}
