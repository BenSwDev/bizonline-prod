<?php
class Translation {
    const CHAR_FIELD_MAX_LEN = 127;
    const DEFAULT_DOMAIN     = 1;
    const DEFAULT_LANG       = 1;

    public static $table     = 'translations';
    public static $lang_id   = 1;
    public static $domain_id = 1;

    public static $strict_domain = false;

    private static function _real_array($arr){
        if (is_array($arr)){
            $c = 0;
            foreach($arr as $i => $v)
                if ($i != $c++)
                    return false;
            return true;
        }
        return false;
    }

    private static function _table_cond($cond, $extra){
        if (!count($extra))
            return array(self::_sql_cond($cond));

        $list = array_shift($extra);
        if (is_scalar($list))
            return self::_table_cond(array_merge($cond, array($list)), $extra);

        $isReal = self::_real_array($list);
        $result = array();
        if($list)
            foreach($list as $key => $val){
                if (is_array($val)){
                    $cc = array_merge($cond, array($key));
                    $result = array_merge($result, self::_table_cond($cc, array($val)));
                } else {
                    $cc = array_merge($cond, ($isReal || is_numeric($val)) ? array($val) : array($key, $val));
                    $result = array_merge($result, self::_table_cond($cc, $extra));
                }
            }

        return $result;
    }

    private static function _sql_cond($arr){
        $cond = array();

        $fields = array('table_name', 'row_id', 'field_name');
        foreach($fields as $i => $field)
            if (isset($arr[$i]) && strcmp($arr[$i], '*'))
                $cond[] = "`" . $field . "` = '" . udb::escape_string($arr[$i]) . "'";

        $lid = isset($arr[3]) ? intval($arr[3]) : self::$lang_id;
        $did = isset($arr[4]) ? intval($arr[4]) : self::$domain_id;

        if (self::$strict_domain || $did == self::DEFAULT_DOMAIN || $lid == self::DEFAULT_LANG)
            $cond[] = "`domain_id` = " . $did;
        else
            $cond[] = "(`domain_id` = " . $did . " OR `domain_id` = " . self::DEFAULT_DOMAIN . ")";

        $cond[] = "`lang_id` = " . $lid;

        return '(' . implode(' AND ', $cond) . ')';
    }

    private static function _run_query($cond, $keys = array()){
        $que = "SELECT " . (count($keys) ? '`' . implode('`, `', $keys) . '`, ' : '') . "IFNULL(`translation_text`, `translation`) AS `result` FROM `" . self::$table . "` WHERE " . implode(' OR ', $cond) . " ORDER BY `domain_id`";
        return new TranslationResult(udb::key_value($que, $keys, 'result'));
    }

    public static function select($table, $id = '*', $field = '*', $langID = 0, $domID = 0){
        $cond = array();

        if (is_array($table))
            foreach($table as $tab => $data)
                $cond = array_merge($cond, self::_table_cond(array($tab), array($data)));
        else
            $cond = self::_table_cond(array($table), array($id, $field, $langID ?: self::$lang_id, $domID ?: self::$domain_id));

        if (count($cond)){
            if (is_array($table))
                return self::_run_query($cond, array('table_name', 'row_id', 'field_name'));

            $index = array();
            if (is_array($id) || $id == '*')
                $index[] = 'row_id';
            if (is_array($field) || $field == '*')
                $index[] = 'field_name';

            if (count($index))
                return self::_run_query($cond, $index);

            return self::_run_query($cond);
        }

        return false;
    }

    public static function __callStatic($name, $arguments){
        return self::select($name, $arguments[0] ?: '*', $arguments[1] ?: '*', $arguments[2] ?: self::$lang_id, $arguments[3] ?: self::$domain_id);
    }


    public static function validate_row($ref, $data, $table, $row, $langID = null, $domID = null){
        $did = is_null($domID) ? self::$domain_id : $domID;
        if ($did == self::DEFAULT_DOMAIN)
            return $data;

        $trans = self::select($table, $row, '*', $langID, self::DEFAULT_DOMAIN)->apply($ref);
        foreach($data as $key => $val)
            if (!strcmp($ref[$key], $val))
                unset($data[$key]);

        return $data;
    }


    public static function save($table, $row, $field, $value, $langID = null, $domID = null){
        if (is_null($domID))
            $domID = self::$domain_id;
        if (is_null($langID))
            $langID = self::$lang_id;

        $len = mb_strlen($value, 'UTF-8');

        udb::insertNull('translations', [
            'table_name'  => $table,
            'row_id'      => $row,
            'field_name'  => $field,
            'lang_id'     => $langID,
            'domain_id'   => $domID,
            'translation'      => ($len > self::CHAR_FIELD_MAX_LEN) ? null : $value,
            'translation_text' => ($len > self::CHAR_FIELD_MAX_LEN) ? $value : null,
        ], true);
    }

    public static function save_row($table, $row, $values, $langID = null, $domID = null){
        foreach($values as $field => $value){
            if ($value)
                self::save($table, $row, $field, $value, $langID, $domID);
            else
                self::clear($table, $row, $field, $langID, $domID);
        }
    }

    public static function clear($table, $id, $field = '*', $langID = null, $domID = null){
        $cond = array();

        if (is_array($table))
            foreach($table as $tab => $data)
                $cond = array_merge($cond, self::_table_cond(array($tab), array($data)));
        else
            $cond = self::_table_cond(array($table), array($id, $field, $langID ?: self::$lang_id, $domID ?: self::$domain_id));

        if (count($cond))
            udb::query("DELETE FROM `" . self::$table . "` WHERE " . implode(' OR ', $cond));
    }

    public static function clear_row($table, $id){
        udb::query("DELETE FROM `" . self::$table . "` WHERE `table_name` = '" . udb::escape_string($table) . "' AND `row_id` = '" . udb::escape_string($id) . "'");
    }
}
