<?php
class SiteItemList {
    protected static $_table = 'site_item_list';

    protected $type;
    protected $siteID;

    private $baseCond;
    private $activeCond;

    public function __construct($siteID, $type){
        $this->type   = $type;
        $this->siteID = intval($siteID);

        $this->baseCond   = "`listType` = '" . udb::escape_string($this->type) . "' AND `siteID` = " . $this->siteID;
        $this->activeCond = $this->baseCond . " AND `_deleted` = 0";
    }

    public function add_item($name){
        if (!$name)
            throw new Exception("Empty item name");
        return udb::insert(static::$_table, ['listType' => $this->type, 'siteID' => $this->siteID,  'itemName' => $name]);
    }

    public function update_item($id, $name){
        if (!$name)
            throw new Exception("Empty item name");
        return udb::update(static::$_table, ['itemName' => $name], $this->activeCond . " AND `itemID` = " . intval($id));
    }

    public function delete_items($id){
        if ($id == '*')
            $cond = '1';
        elseif (is_array($id))
            $cond = "`itemID` IN (" . implode(',', array_map('intval', $id)) . ")";
        else
            $cond = "`itemID` = " . intval($id);

        udb::query("UPDATE `" . static::$_table . "` SET `_deleted` = 1 WHERE " . $this->baseCond . " AND " . $cond);
        return $this;
    }

    public function restore_items($id){
        if ($id == '*')
            $cond = '1';
        elseif (is_array($id))
            $cond = "`itemID` IN (" . implode(',', array_map('intval', $id)) . ")";
        else
            $cond = "`itemID` = " . intval($id);

        udb::query("UPDATE `" . static::$_table . "` SET `_deleted` = 0 WHERE " . $this->baseCond . " AND " . $cond);
        return $this;
    }

    public function get_items($ids = '*'){
        if ($ids == '*')
            return $this->get_full_list(false);

        if (is_array($ids))
            $cond = "`itemID` IN (" . implode(',', array_map('intval', $ids)) . ")";
        else
            $cond = "`itemID` = " . intval($ids);

        $list = udb::key_row("SELECT * FROM `" . static::$_table . "` WHERE " . $this->baseCond . " AND " . $cond, 'itemID');
        return is_array($ids) ? $list : reset($list);
    }

    public function get_name_list($activeOnly = true){
        return udb::key_value("SELECT `itemID`, `itemName` FROM `" . static::$_table . "` WHERE " . ($activeOnly ? $this->activeCond : $this->baseCond));
    }

    public function get_full_list($activeOnly = true){
        return udb::key_row("SELECT * FROM `" . static::$_table . "` WHERE " . ($activeOnly ? $this->activeCond : $this->baseCond), 'itemID');
    }

    public function clear_list(){
        return $this->delete_items('*');
    }
}
