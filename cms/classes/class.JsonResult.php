<?php

class JsonResult implements ArrayAccess {
    const STATE_READY    = 1;
    const STATE_HAS_DATA = 2;
    const STATE_COMPLETE = 4;

    protected static $data    = [];
    protected static $iCount  = 0;
    protected static $state   = 0;
    protected static $context = [];

    protected static function _is($state){
        return (self::$state & $state) == $state;
    }

    protected static function _set($state){
        self::$state = self::$state | $state;
    }

    protected static function _clear($state){
        self::$state = self::$state & ~$state;
    }

    protected static function _formValue($final = false){
        $tmp = self::$data;
        if ($text = ob_get_contents())
            $tmp['_txt'] = $text;

        $final and ob_end_clean();

        return json_encode($tmp, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_BIGINT_AS_STRING);
    }

    public function offsetExists($offset){
        return isset(self::$data[$offset]);
    }

    public function offsetGet($offset){
        return isset(self::$data[$offset]) ? self::$data[$offset] : null;
    }

    public function offsetSet($offset, $value){
        self::$data[$offset] = $value;
        self::_set(self::STATE_HAS_DATA);
    }

    public function offsetUnset($offset){
        unset(self::$data[$offset]);
    }

    public function __construct($type = null, $foo = ''){
        if (!self::_is(self::STATE_READY)){
            ob_start();
            register_shutdown_function(__CLASS__ . '::finish');
            $this->returnType('json', '');
            self::_set(self::STATE_READY);
        }

        ++self::$iCount;

        if (is_array($type))
            $this->add($type);
        elseif (is_string($type) && $type)
            $this->returnType($type, $foo);
    }

    public function __destruct(){
        --self::$iCount || $this->flush();
    }

    public function bind($offset, &$value){
        self::$data[$offset] =& $value;
    }

    public function add($key = null, $val = null){
        if (is_array($key))
            self::$data = array_merge(self::$data, $key);
        elseif (is_null($key) && $val)
            self::$data[] = $val;
        elseif (is_scalar($key) && $key)
            self::$data[$key] = $val;
        else
            throw new Exception('Unknown key type in add() method');
    }

    public function flush(){
        self::$data = [];
        self::_clear(self::STATE_HAS_DATA);
    }

    public function returnType($type, $foo = ''){
        self::$context['type'] = (string) $type;
        self::$context['func'] = (string) $foo;
    }

    public function value($raw = false){
        return $raw ? self::$data : static::_formValue();
    }

    public function __toString(){
        return static::_formValue();
    }

    public function length(){
        return count(self::$data);
    }

    public static function finish(){
        if (!self::_is(self::STATE_COMPLETE)){
            if (self::_is(self::STATE_HAS_DATA) || self::$iCount){
                switch(self::$context['type']){
                    case 'javascript':
                    case 'jsonp':
                    case 'js':
                        header('Content-Type: application/javascript; charset=utf-8');

                        echo self::$context['func'] ? self::$context['func'] . '(' . static::_formValue(true) . ');' : static::_formValue(true);
                        break;

                    default:
                        header('Content-Type: application/json; charset=utf-8');

                        echo static::_formValue(true);
                        break;
                }
            } else
                ob_end_flush();

            self::$data = [];
            self::_set(self::STATE_COMPLETE | ~self::STATE_HAS_DATA);
        }
    }
}
