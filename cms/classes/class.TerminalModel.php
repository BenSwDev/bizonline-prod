<?php
require_once __DIR__ . "/ActiveRecord/class.ActiveRecord.php";

class TerminalModel extends \ActiveRecord\ActiveRecord
{
    const INVOICE_ACTIVE    = 1;
    const INVOICE_FULL_DESC = 2;
    const INVOICE_AUTO_OPEN = 4;
    const INVOICE_ALL       = 7;

    const MODE_MAIN = 1;
    const MODE_SYNC = 2;

    protected static $dbTable = 'sites_terminals';
    protected static $dbIndex = 'id';

    public function __construct($id = 0){
        $this->id = intval($id);

        $this->target = 'vouchers';
    }

    public function set(...$data){
        if (count($data) > 2 || count($data) <= 0)
            throw new Exception("Wrong parameter count for " . __METHOD__ . ": " . count($data));
        elseif (count($data) == 2 && is_scalar($data[0]))
            $input = [$data[0] => $data[1]];
        elseif (!is_array($data[0]))
            throw new Exception("Single parameter for " . __METHOD__ . " must be an array");
        else
            $input = $data[0];

        // filtering out unchangeable fields;
        if ($this->id)
            unset($input['target'], $input['siteID']);

        if ($input['invoice'] && is_array($input['invoice']))
            $input['invoice'] = array_sum(array_map('intval', $input['invoice'])) & self::INVOICE_ALL;

        foreach($input as $key => $val)
            $this->$key = $val;

        return $this;
    }

    public function hasInvoice($prm = self::INVOICE_ACTIVE){
        return ($this->invoice & self::INVOICE_ACTIVE) && ($this->invoice & $prm);
    }

    public static function find_by_target($siteID, $target){
        $id = udb::single_value("SELECT " . self::$dbIndex . " FROM " . self::$dbTable . " WHERE `siteID` = " . intval($siteID) . " AND `target` = '" . udb::escape_string($target) . "'");
        return $id ? new self($id) : null;
    }

    public static function sync_terminals($siteID, $termID = 0){
        $toSync = udb::single_column("SELECT " . self::$dbIndex . " FROM " . self::$dbTable . " WHERE `siteID` = " . intval($siteID) . " AND `mode` & " . self::MODE_SYNC . ($termID ? " AND `id` = " . $termID : ""));
        if (!$toSync)
            return false;

        $update = udb::single_row("SELECT * FROM " . self::$dbTable . " WHERE `siteID` = " . intval($siteID) . " AND `mode` & " . self::MODE_MAIN);
        if ($update){
            $exclude = [self::$dbIndex, 'active', 'siteID', 'target', 'mode'];
            foreach($exclude as $key)
                unset($update[$key]);
        }
        else {
            $data = udb::single_row("SELECT `masof_type`, `masof_active`, `masof_number`, `masof_key`, `masof_pwd`, `masof_no_cvv`, `masof_invoice`, `masof_swipe`, `masof_noVAT`, `masof_doc_type` FROM `sites` WHERE `siteID` = " . $siteID);
            if (!$data)
                return false;

            $update = [
                'masof_type'   => $data['masof_type'],
                'masof_number' => $data['masof_number'],
                'masof_key'    => $data['masof_key'],
                'masof_pwd'    => $data['masof_pwd'],
                'invoice'      => $data['masof_invoice'],
                'flag_tokens'  => $data['masof_no_cvv'],
                'flag_swipe'   => $data['masof_swipe'],
                'flag_noVAT'   => $data['masof_noVAT'],
                'doc_type'     => $data['masof_doc_type']
            ];
        }

        foreach($toSync as $tid)
            udb::update(self::$dbTable, $update, self::$dbIndex . " = " . $tid);

        return true;
    }
}
