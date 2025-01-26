<?php
abstract class TabList {
    protected static $loaded     = false;
    protected static $db_table   = '';
    protected static $field_key  = '';
    protected static $field_name = '';
    protected static $short      = '';
    protected static $data       = '';

    protected static $list = [];

    private static function _load($force = false){
        if ($force || !self::$loaded)
            self::$list = udb::key_row(static::getLoadQuery(), static::$field_key);
    }

    public static function getLoadQuery(){
        return 'SELECT NULL';
    }

    public static function active($id = null){
        return $id;
    }

    public static function html_select($selected = null, $name = null, $sid = null, $class = null, $extra = null){
        self::_load();

        $active = ($selected ?? static::active());
        $html   = [];
        foreach(self::$list as $id => $item)
            $html[] = '<option value="' . $id . '" ' . (($id == $active) ? 'selected="selected"' : '') . '>' . $item[static::$field_name] . '</option>';

        return '<select name="' . ($name ?? static::$short) . '" id="' . ($sid ?? static::$short) . '" class="' . ($class ? $class : static::$data . 'Selector') . '" ' . ($extra ?? '') . '>' . implode('', $html) . '</select>';
    }

    public static function html_tabs($selected = null){
        self::_load();

        $active = ($selected ?? static::active());
        $html   = [];
        foreach(self::$list as $id => $item)
            $html[] = '<div class="tab' . (($id == $active) ? ' active' : '') . '" data-show="' . static::$data . '" data-id="' . $id . '"><p>' . $item[static::$field_name] . '</p></div>';

        return '<div class="miniTabs">' . implode('', $html) . '</div>';
    }

    public static function get($id = ''){
        self::$loaded or self::_load();
        return strlen($id) ? [$id => self::$list[$id]] : self::$list;
    }
}



class DomainList extends TabList {
    protected static $db_table   = 'domains';
    protected static $field_key  = 'domainID';
    protected static $field_name = 'domainName';
    protected static $short      = 'domID';
    protected static $data       = 'domain';

    public static function getLoadQuery(){
        return "SELECT * FROM `".static::$db_table."` WHERE `domainMenu` = 1 ";
    }

    public static function active($domID = 0){
        if ($domID)
            return $_SESSION['cms']['domID'] = $domID;
        return ($_SESSION['cms']['domID'] ?? 1);
    }
}


class LangList extends TabList {
    protected static $db_table   = 'language';
    protected static $field_key  = 'LangID';
    protected static $field_name = 'LangName';
    protected static $short      = 'langID';
    protected static $data       = 'language';

    public static function getLoadQuery(){
        return "SELECT * FROM `".static::$db_table."`";
    }

    public static function active($id = 0){
        if ($id)
            return $_SESSION['cms']['langID'] = $id;
        return ($_SESSION['cms']['langID'] ?? 1);
    }
}
