<?php
class TranslationResult implements ArrayAccess {
    private $_result   = null;
    private $_multiRow = false;

    public function __construct($data){
        $this->_result   = $data;
        $this->_multiRow = (is_array($this->_result) && !is_scalar(reset($this->_result)) && is_numeric(key($this->_result)));
    }

    private function _line_replace(&$line, $replace){
        foreach($replace as $key => $value)
            if (array_key_exists($key, $line))
                $line[$key] = $value;
    }

    public function apply(&$arr, $sub = -1){
        if ($sub >= 0)
            $this->_line_replace($arr, $this->_result[$sub] ?? []);
        elseif (!$this->_multiRow)
            $this->_line_replace($arr, $this->_result ?: []);
        elseif ($this->_result){
            foreach($arr as $ind => &$data)
                if (isset($this->_result[$ind]))
                    $this->_line_replace($data, $this->_result[$ind]);
        }
        return $this;
    }

    public function __toString(){
        return $this->_result ? (string)$this->_result : '';
    }

    public function offsetExists($offset){
        return isset($this->_result[$offset]);
    }

    public function offsetGet($offset){
        return $this->_result[$offset];
    }

    public function offsetSet($offset, $value){
        return $this->_result[$offset] = $value;
    }

    public function offsetUnset($offset){
        unset($this->_result[$offset]);
    }
}
