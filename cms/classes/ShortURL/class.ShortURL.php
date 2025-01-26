<?php
namespace ShortURL;

use \udb;

class ShortURL {
    protected static $table;
    protected static $word_len  = 11;
    protected static $loop_lim  = 10;
    protected static $not_first = ['-'];        // list of symbols that cannot be first in key

    protected static $range;

    protected static function _init_range(){
        if (count(static::$range))
            return;

        static::$range = array_merge(range('a', 'z'), range('A', 'Z'), range(0, 9), ['_', '-']);
        shuffle(static::$range);
    }

    protected static function _encode($data){
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected static function _decode($data){
        return $data ? json_decode($data, true) : false;
    }

    protected static function create($data, $expires = ''){
        if (!$data)
            throw new Exception(get_class() . " error: empty data");

        static::_init_range();

        $max = count(static::$range) - 1;

        $key = str_repeat('_', static::$word_len);
        for ($i = 0; $i < static::$word_len; ++$i)
            $key[$i] = static::$range[mt_rand(0, $max)];

        if (count(static::$not_first))
            while(in_array($key[0], static::$not_first))
                $key[0] = static::$range[mt_rand(0, $max)];

        udb::query("LOCK TABLES " . static::$table . " WRITE");

        $loops = 0;
        while($loops++ < static::$loop_lim && udb::single_value("SELECT COUNT(*) FROM " . static::$table . " WHERE `short` = '" . udb::escape_string($key) . "'"))
            $key[mt_rand(1, static::$word_len - 1)] = static::$range[mt_rand(0, $max)];

        $insert = ['short' => $key, 'data' => static::_encode($data)];
        if ($expires)
            $insert['expires'] = date('Y-m-d H:i:s', is_numeric($expires) ? $expires : strtotime($expires));

        udb::insert(static::$table, $insert);

        udb::query("UNLOCK TABLES");

        return $key;
    }

    public static function sanitize($key){
        return preg_replace('/[^a-zA-Z0-9_-]+/', '', $key);
    }

    public static function get_data($key){
        return static::_decode(udb::single_value("SELECT `data` FROM " . static::$table . " WHERE `short` = '" . udb::escape_string(static::sanitize($key)) . "' AND (`expires` IS NULL OR `expires` >= '" . date('Y-m-d H:i:s') . "')"));
    }


    public static function cleanup(){
        udb::query("DELETE FROM " . static::$table . " WHERE `expires` IS NOT NULL AND `expires` < '" . date('Y-m-d H:i:s') . "'");
    }

    public static function rebuild(){
        udb::query("OPTIMIZE TABLE " . static::$table);
    }
}
