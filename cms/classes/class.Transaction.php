<?php
include_once "Transactions/class.LogRecord.php";

class Transaction {
    protected static $db_table = 'pm_transactions';

    protected $_changes = [];

    protected $id;
    protected $data;
    protected $log;

    public $input;
    public $result;

    private function _next_step(){
        if (!$this->log)
            $this->log = Transactions\LogRecord::trans_log($this->id);

        $next = 1;
        foreach($this->log as $rec)
            if ($rec->step >= $next)
                $next = $rec->step + 1;

        return $next;
    }

    public function __construct($id = 0){
        $this->load($id);
    }

    public function load($id = 0){
        $this->id  = intval($id);
        $this->log = null;

        if ($this->id){
            $this->data = udb::single_row("SELECT * FROM `" . static::$db_table . "` WHERE `transID` = " . $this->id);
            if (!$this->data)
                throw new Exception('Cannot load transaction #' . $id);

            $this->input  = $this->data['input'] ? json_decode($this->data['input'], true) : [];
            $this->result = $this->data['result'] ? json_decode($this->data['result'], true) : [];
        }
        else {
            $this->input  = [];
            $this->result = [];
        }
    }

    public function update($data){
        if (is_array($data)){
            if ($data['input'] && !is_scalar($data['input']))
                $data['input'] = json_encode($data['input'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($data['result'] && !is_scalar($data['result']))
                $data['result'] = json_encode($data['result'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $this->data     = array_merge($this->data, $data);
            $this->_changes = array_merge($this->_changes, $data);

            $this->input  = $this->data['input'] ? json_decode($this->data['input'], true) : [];
            $this->result = $this->data['result'] ? json_decode($this->data['result'], true) : [];
        }
        return $this;
    }

    public function save(){
        if (!$this->id)
            throw new Exception("Must create transaction before saving");

        if ($this->_changes)
            udb::update(static::$db_table, $this->_changes, "`transID` = " . $this->id);

        $this->_changes = [];

        return $this;
    }

    public function complete($success, $result = [], $error = ''){
        if (!$this->id)
            throw new Exception("Cannot 'complete' new transaction");

        return $this->update([
            'status'       => $success ? 1 : 0,
            'completeTime' => date('Y-m-d H:i:s'),
            'error'        => $error,
            'result'       => $result
        ])->save();
    }

    public function __get($name){
        switch($name){
//            case 'input' :
//            case 'result':
//                return isset($this->data[$name]) ? json_decode($this->data[$name], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

            default:
                return $this->data[$name] ?? null;
        }
    }

    public function add_record($request, $step = 0){
        if (!$this->id)
            throw new Exception("Transaction doesn't exists yet");

        if ($step <= 0)
            $step = $this->_next_step();
        elseif (!$this->log)
            $this->log = Transactions\LogRecord::trans_log($this->id);

        $rec = new Transactions\LogRecord($this->id, $step, $request);

        $this->update(['recordID' => $rec->id])->save();

        return $this->log[] = $rec;
    }

    public function last_record(){
        if (!$this->log)
            $this->log = Transactions\LogRecord::trans_log($this->id);
        return end($this->log) ?: null;
    }

    public function id(){
        return $this->id;
    }

    public static function create($owner, $type, $engine, $sum, $extra = []){
        $transID = udb::insert(static::$db_table, [
            'transType'  => $type,
            'siteID'     => intval($owner),
            'sum'        => $sum,
            'createTime' => date('Y-m-d H:i:s'),
            'engine'     => $engine,
            'input'      => json_encode($extra, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ]);

        return new static($transID);
    }

    public static function getByExId($exID){
        $transID = udb::single_value("SELECT `transID` FROM `" . static::$db_table . "` WHERE `exID` = '" . udb::escape_string($exID) . "'");
        if (!$transID)
            throw new Exception('Cannot find transaction with external ID "' . $exID . '"');
        return new static($transID);
    }
}
