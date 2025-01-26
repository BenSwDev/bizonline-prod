<?php
namespace Salary;

use \udb;

class Salary {
    const MIN_DATE = '2001-01-01';

    protected static $_table       = 'salaryDetails';
    protected static $_salaryTypes = ['default', 'minute', 'percent'];
    protected static $_recordType  = 'salaryType';

    protected $targetType;
    protected $targetID;

    protected $changes = [];
    protected $details = [];

    private $_loaded = false;

    public function __construct($type, $id) {
        $this->targetType = $type ?: '';
        $this->targetID   = intval($id);

        if (!$this->targetType || !$this->targetID)
            throw new \Exception("Illegal values for type/id: '" . $this->targetType . "'/" . $this->targetID);
    }

    public function load($force = false){
        if (!$this->_loaded || $force){
            $this->changes = udb::key_value("SELECT `startFrom`, `salaryType` FROM `" . static::$_table . "` WHERE `_deleted` = 0 AND `recordType` = '" . static::$_recordType . "' AND `targetType` = '" . udb::escape_string($this->targetType) . "' AND `targetID` = " . $this->targetID . " ORDER BY `id`");
            ksort($this->changes);
        }
        $this->_loaded = true;

        return $this;
    }

    public function active_type($date = null){
        $this->_loaded or $this->load();
        return static::search_by_date($this->changes, $date ?: date('Y-m-d'));
    }

    public function get_day_salary($date = null, $salaryType = null){
        $this->_loaded or $this->load();

        if (!$date)
            $date = date('Y-m-d');

        if (!$salaryType){
            $salaryType = self::search_by_date($this->changes, $date);
            if (!$salaryType)
                return new SalaryDay(self::MIN_DATE, static::$_salaryTypes[0]);
        }

        if (!isset($this->details[$salaryType]))
            $this->details[$salaryType] = new SalaryDetails($this->targetType, $this->targetID, $salaryType);

        return $this->details[$salaryType]->day_salary($date);
    }

    public function change_salary($newType, $fromDate = false){
        if (!$newType || !in_array($newType, static::$_salaryTypes))
            throw new \Exception("Illegal salary type: " . $newType);
        if ($fromDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate))
            throw new \Exception('Illegal date: ' . $fromDate);
        elseif (!$fromDate)
            $fromDate = date('Y-m-d');

        $que = "UPDATE `" . self::$_table . "` SET `_deleted` = 1 WHERE `recordType` = '" . udb::escape_string(static::$_recordType) . "' AND `targetID` = " . $this->targetID . " 
                    AND `targetType` = '" . udb::escape_string($this->targetType) . "' AND `startFrom` >= '" . udb::escape_string($fromDate) . "'";
        udb::query($que);

        udb::insert(self::$_table, [
            'recordType' => static::$_recordType,
            'targetType' => $this->targetType,
            'targetID'   => $this->targetID,
            'startFrom'  => $fromDate,
            'salaryType' => $newType
        ]);

        if ($this->_loaded)
            $this->load(true);

        return $this;
    }


    public function get_last_salary(){
        $this->_loaded or $this->load();

        $result = ['type' => new SalaryChangeItem(end($this->changes), end($this->changes))];

        foreach(static::$_salaryTypes as $salaryType)
            $result[$salaryType] = (new SalaryDetails($this->targetType, $this->targetID, $salaryType))->get_last_salary();

        return $result;
    }


    public static function search_by_date($array, $date){
        if (!is_array($array))
            return false;
        elseif (isset($array[$date]))
            return $array[$date];

        $last = false;
        foreach($array as $key => $val){
            if (strcmp($key, $date) > 0)        // if $key > $date
                break;
            $last = $val;
        }

        return $last;
    }
}

class SalaryDetails {
    const DAY_REGULAR = 'wday';
    const DAY_WEEKEND = 'wend';

    protected static $_table      = 'salaryDetails';
    protected static $_dayTypes   = ['wday', 'wend'];
    protected static $_recordType = 'salaryRate';

    protected $targetType;
    protected $targetID;
    protected $salaryType;

    protected $changes = [];

    private $_loaded = false;

    public function __construct($type, $id, $salary) {
        $this->targetType = $type;
        $this->targetID   = intval($id);
        $this->salaryType = $salary;

        if (!$this->targetType || !$this->targetID || !$this->salaryType)
            throw new \Exception("Illegal values for type/id/salary: '" . $this->targetType . "'/" . $this->targetID . "/'" . $this->salaryType . "'");
    }

    public function load(){
        if (!$this->_loaded){
            $this->changes = udb::key_value("SELECT `startFrom`, `salaryDay`, `salaryRate` FROM `" . static::$_table . "` WHERE `_deleted` = 0 AND `recordType` = '" . static::$_recordType . "' AND `targetType` = '" . udb::escape_string($this->targetType) . "' AND `targetID` = " . $this->targetID . " AND `salaryType` = '" . udb::escape_string($this->salaryType) . "' ORDER BY `id`",
                ['salaryDay', 'startFrom'], 'salaryRate');

            foreach($this->changes as $day => &$changes)
                ksort($changes);
            unset($changes);
        }

        foreach(static::$_dayTypes as $type)
            if (!isset($this->changes[$type]))
                $this->changes[$type] = [];

        $this->_loaded = true;
    }

    public function has_data(){
        $this->_loaded or $this->load();
        return (count($this->changes['wday']) || count($this->changes['wend']));
    }

    public function day_salary($date){
        $this->_loaded or $this->load();

        $wday = Salary::search_by_date($this->changes[self::DAY_REGULAR], $date);
        $wend = Salary::search_by_date($this->changes[self::DAY_WEEKEND], $date);

        return new SalaryDay($date, $this->salaryType, $wday ?: 0, $wend ?: 0);
    }

    public function change_rate($day, $fromDate, $rate = 0){
        if (!$day || !in_array($day, static::$_dayTypes))
            throw new \Exception("Illegal day code: " . $day);
        if ($fromDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate))
            throw new \Exception('Illegal date: ' . $fromDate);
        elseif (!$fromDate)
            $fromDate = date('Y-m-d');

        $que = "UPDATE `" . self::$_table . "` SET `_deleted` = 1 WHERE `recordType` = '" . udb::escape_string(static::$_recordType) . "' AND `targetID` = " . $this->targetID . " 
                    AND `targetType` = '" . udb::escape_string($this->targetType) . "' AND `salaryType` = '" . udb::escape_string($this->salaryType) . "' AND `salaryDay` = '" . $day . "' 
                    AND `startFrom` >= '" . udb::escape_string($fromDate) . "'";
        udb::query($que);

        udb::insert(self::$_table, [
            'recordType' => static::$_recordType,
            'targetType' => $this->targetType,
            'targetID'   => $this->targetID,
            'startFrom'  => $fromDate ?: date('Y-m-d'),
            'salaryType' => $this->salaryType,
            'salaryDay'  => $day,
            'salaryRate' => $rate
        ]);

        return $this;
    }

    public function get_last_salary(){
        $this->_loaded or $this->load();

        $result = [];
        foreach(static::$_dayTypes as $salaryDay)
            $result[$salaryDay] = new SalaryChangeItem(end($this->changes[$salaryDay]), key($this->changes[$salaryDay]));

        return $result;
    }
}

class SalaryDay {
    public $date;
    public $type;
    public $rateRegular;
    public $rateWeekend;
    public $isHoliday;

    public function __construct($date, $type = '', $wday = 0, $wend = 0, $holiday = false){
        $this->date        = $date;
        $this->type        = $type;
        $this->rateRegular = round($wday, 2);
        $this->rateWeekend = round($wend, 2);
        $this->isHoliday   = !!$holiday;
    }
}

class SalaryChangeItem {
    public $value;
    public $date;

    public function __construct($value, $date = null){
        $this->value = $value;
        $this->date  = $date;
    }
}
