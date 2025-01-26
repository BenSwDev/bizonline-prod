<?php
abstract class BaseModel {
    static protected $schema = null;
    static protected $table  = null;
    static protected $index  = null;

    protected $id   = 0;
    protected $data = [];

    protected static function _field_list($list){
        if (is_array($list)){
            array_walk($list, function(&$val, $key){
                if (!preg_match('/\W/', $val))
                    $val = '`' . $val . '`';
            });
            return count($list) ? implode(',', $list) : '*';
        }
        return $list ?: '*';
    }

    protected static function _condition_list($list){
        if (is_array($list)){
            $tmp = [];
            foreach($list as $key => $val)
                $tmp[] = "`" . $key . "` = '" . udb::escape_string($val) . "'";
            return count($tmp) ? implode(' AND ', $tmp) : '1';
        }
        return $list ?: '1';
    }

    protected static function _load_schema(){
        if (static::$schema)
            return;

        $list   = [];
        $fields = udb::full_list("DESCRIBE `" . static::$table . "`");
        foreach($fields as $row){
            switch(true){
                case !strcasecmp('date', $row['Type']): $type = 'date'; break;
                case preg_match('/int/i', $row['Type']): $type = 'int'; break;
                case preg_match('/float/i', $row['Type']): $type = 'float'; break;
                case preg_match('/text/i', $row['Type']): $type = 'html'; break;
                default: $type = 'string';
            }

            $list[$row['Field']] = $type;
        }

        static::$schema = $list;
    }

    protected function _safe_save($shift){
        if (!$this->id)
            $this->data = array_merge($this->data, $shift);
        else {
            $tmp = $this->data;
            $this->data = $shift;

            $this->save();
            $this->data = count($tmp) ? array_merge($tmp, $shift) : [];
        }
        return $this;
    }

    public function load(...$fields){
        $this->data = array_merge($this->data, udb::single_row("SELECT " . (count($fields) ? '`' . implode('`,`', $fields) . '`' : '*') . " FROM `" . static::$table . "` WHERE `" . static::$index . "` = " . $this->id));
        return $this;
    }

    public function save($withUpdate = true){
        if (count($this->data)){
            static::_load_schema();

            $filter = array_intersect_key($this->data, static::$schema);

            if ($this->id || !count($filter))
                $filter[static::$index] = $this->id;

            $this->id = udb::insert(static::$table, $filter, $withUpdate);
        }

        return $this;
    }

    public function __get($name){
        if ($name == 'id')
            return $this->id;

        $foo = 'get_' . strtolower($name);
        if (method_exists($this, $foo)){
            if (is_null($tmp = $this->$foo()))
                return null;
            return $this->$name = $tmp;
        }

        if ($this->id && !count($this->data))
            $this->load();

        if (isset($this->data[$name]))
            return $this->data[$name];

        return null;
    }

    public function __set($name, $value){
        if ($name == 'id')
            throw new Exception('Cannot change ID');

        $foo = 'set_' . strtolower($name);
        if (method_exists($this, $foo))
            return $this->$foo();

        if (static::$table){
            static::_load_schema();

            if (isset(static::$schema[$name]))
                return $this->data[$name] = typemap($value, static::$schema[$name]);
        }

        return $this->$name = $value;
    }
}
