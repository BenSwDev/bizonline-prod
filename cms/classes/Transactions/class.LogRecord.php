<?php
namespace Transactions;

use \udb;

class LogRecord {
    protected static $db_table = 'pm_trans_log';

    private $recordID;
    public $data;

    public function __construct($transID = 0, $step = 0, $request = null){
        if ($transID && $step && $request){
            $this->recordID = udb::insert(static::$db_table, [
                'transID'   => intval($transID),
                'step'      => intval($step),
                'request'   => is_scalar($request) ? $request : json_encode($request, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'startTime' => date('Y-m-d H:i:s')
            ]);

            $this->data = udb::single_row("SELECT * FROM `" . self::$db_table . "` WHERE `recordID` = " . $this->recordID);
        }
        else {
            $this->recordID = 0;
            $this->data = [];
        }
    }

    public function update($response){
        if (!$this->recordID)
            throw new \Exception("Cannot update non-initialized record");

        $this->data['response'] = (is_scalar($response) && json_decode($response, true)) ?: $response;
        $this->data['endTime']  = date('Y-m-d H:i:s');

        udb::update(self::$db_table, ['response' => is_scalar($response) ? $response : json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'endTime' => date('Y-m-d H:i:s')], "`recordID` = " . $this->recordID);

        return $this;
    }

    public function __get($name){
        if ($name == 'id')
            return $this->recordID;
        return $this->data[$name] ?? null;
    }

    public static function trans_log($transID){
        $recs = udb::single_list("SELECT * FROM `" . self::$db_table . "` WHERE `transID` = " . intval($transID) . " ORDER BY `recordID`");
        $list = [];

        foreach($recs as $row){
            $tmp = new static;
            $tmp->recordID = intval($row['recordID']);
            $tmp->data = $row;

            $list[] = $tmp;
        }

        return $list;
    }
}
