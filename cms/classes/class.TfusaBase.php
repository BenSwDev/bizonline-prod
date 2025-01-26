<?php
abstract class TfusaBase {
    protected static $table;
    protected static $field;
    protected static $defStep = 300;

    protected $step;         // min step in SECONDS

    public function __construct($step = 5){        // step time in MINUTES
        $this->step = max($step * 60, static::$defStep);
    }

    public function can_book($elemID, $from, $till, $orderID = 0){
        list($date1, $time1) = explode(' ', $from);
        list($date2, $time2) = explode(' ', $till);

        $oCond = $orderID ? " AND " . (is_array($orderID) ? "`orderID` NOT IN (" . implode(',', $orderID) . ") " : "`orderID` = " . $orderID) : "";

        if (!strcmp($date1, $date2))
            $que = "SELECT `date` FROM `" . static::$table . "` WHERE `date` = '" . $date1 . "' AND `" . static::$field . "` = " . $elemID . " AND `hour` >= '" . $time1 . "' AND `hour` < '" . $time2 . "'";
        else
            $que = "SELECT `date` FROM `" . static::$table . "` 
                        WHERE `" . static::$field . "` = " . $elemID . " AND (
                            (`date` = '" . $date1 . "' AND `hour` >= '" . $time1 . "') OR (`date` = '" . $date2 . "' AND `hour` < '" . $time2 . "') OR (`date` > '" . $date1 . "' AND `date` < '" . $date2 . "')
                        ) "; echo $que;
        return !udb::single_value($que . $oCond . " LIMIT 1");
    }

    public function book($elemID, $from, $till, $orderID){
        $tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $ts = strtotime($from);
        $timeUnits = ceil((strtotime($till) - $ts) / $this->step);

        $insert = [];
        for ($i = 0; $i < $timeUnits; ++$i){
            list($date, $time) = explode(' ', date("Y-m-d H:i:s", $ts + $this->step * $i));
            $insert[] = "(" . $orderID . ", " . $elemID . ", '" . $date . "', '" . $time . "')";
        }

        if ($insert)
            udb::query("INSERT INTO `" . static::$table . "`(`orderID`, `" . static::$field . "`, `date`, `hour`) VALUES" . implode(',', $insert));

        date_default_timezone_set($tz);
    }

    public static function clean_until($stop = null){
        udb::query("DELETE FROM " . static::$table . " WHERE `date` < '" . ($stop ?: date('Y-m-d', strtotime('-6 months'))) . "'");
    }

    public static function clean_order($orderID = -1){
        udb::query("DELETE FROM " . static::$table . " WHERE " . (is_array($orderID) ? "`orderID` IN (" . implode(',', $orderID) . ")" : "`orderID` = " . $orderID));
    }
}
