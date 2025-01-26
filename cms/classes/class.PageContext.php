<?php
class PageContext {
    static public $langID = 1;
    static public $domainID = 1;

    static public $search = [];
    static public $isDateSearch = false;

    static function map($var, $type){
        return typemap($var, $type);
    }

    static public function variant($from_table, $to_table, $fields){
        $list   = is_array($fields) ? $fields : [$fields];
        $result = [];

        foreach($list as $key => $value){
            $from = $from_table . "." . (is_numeric($key) ? $value : $key);
            $to   = $to_table . "." . $value;

            $result[] = "IF(LENGTH(" . $from . ") > 0, " . $from . ", " . $to . ") as `" . $value . "`";
        }

        return implode(', ', $result);
    }

    static public function checkDateSearch($f, $t){
        $from = self::map($f, 'date');
        $till = self::map($t, 'date');

        if ($from && $till && $from < $till){
            self::$search['from']   = $from;
            self::$search['till']   = $till;
            self::$search['nights'] = round((strtotime($till) - strtotime($from)) / (3600 * 24));

            self::$isDateSearch = true;
        }
    }
}
