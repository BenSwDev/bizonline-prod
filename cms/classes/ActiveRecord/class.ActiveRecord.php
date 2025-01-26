<?php
namespace ActiveRecord;

use \udb;
use \Exception;

abstract class ActiveRecord {
    public static $version = '1.0';
    protected static $dbTable;
    protected static $dbIndex;

    private $_is_loaded  = false;
    private $_is_changed = false;
    private $_data       = [];

    protected $id;

    protected function _create(){
        if ($this->id)
            throw new Exception("Cannot _create record - " . $this->id . " already exists");
        return $this->_after_create(udb::insert(static::$dbTable, $this->_data));
    }

    protected function _after_create($newID){
        $this->id = $newID;
        return $this;
    }

    protected function _read($fields = '*'){
        if (!$this->id)
            throw new Exception("Cannot _read record without ID");

        $this->_data = udb::single_row("SELECT " . implode(',', udb::wrap_fields($fields)) . " FROM " . static::$dbTable . " WHERE " . static::$dbIndex . " = '" . $this->id . "'") ?: [];
        unset($this->_data[static::$dbIndex]);

        $this->_is_loaded  = true;
        $this->_is_changed = false;

        return $this;
    }

    protected function _save($force = false){
        if (!$this->id)
            throw new Exception("Cannot _save record without ID");

        if ($this->_is_changed || ($force && $this->_is_loaded))
            udb::update(static::$dbTable, $this->_data, static::$dbIndex . " = '" . $this->id . "'");

        $this->_is_changed = false;

        return $this;
    }

    protected function _delete(){
        if (!$this->id)
            throw new Exception("Cannot _delete record without ID");
        udb::query("DELETE FROM " . static::$dbTable . " WHERE " . static::$dbIndex . " = '" . $this->id . "'");

        $this->id = null;
        $this->_is_loaded  = false;
        $this->_is_changed = false;
        $this->_data       = [];

        return $this;
    }

    /*************************** exposed functions *************************************/
    public function __get($key){
        if ($this->id && !$this->_is_loaded)
            $this->_read();

        return $this->_data[$key] ?? null;
    }

    public function __set($key, $value){
        if ($key !== static::$dbIndex){
            $this->_data[$key] = $value;
            $this->_is_changed = true;
        }
        return $value;
    }

    public function id(){
        return $this->id;
    }

    public function create(){
        return $this->_create();
    }

    public function save(){
        return $this->_save();
    }

    public function delete(){
        return $this->_delete();
    }

    public function isSet($key){
        return isset($this->_data[$key]);
    }
}
