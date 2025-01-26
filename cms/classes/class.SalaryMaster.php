<?php
include_once __DIR__ . "/Salary/class.Salary.php";

class SalaryMaster extends Salary\Salary {
    protected static $_defaultType = 'default';

    private $_cache = [];

    protected $siteSalary;

    public function __construct($id){
        parent::__construct('master', $id);

        $siteID = udb::single_value("SELECT `siteID` FROM `therapists` WHERE `therapistID` = " . $this->targetID);
        $this->siteSalary = new SalarySite($siteID);
    }

    public function active_type($date = null){
        $this->load();
        return static::search_by_date($this->changes, $date ?: date('Y-m-d')) ?: static::$_defaultType;
    }

    public function get_day_salary($date = null, $salaryType = null){
        $this->load();

        if (!$date)
            $date = date('Y-m-d');

        if ($salaryType == static::$_defaultType)
            return $this->siteSalary->get_day_salary($date);
        elseif (!$salaryType){
            $salaryType = self::search_by_date($this->changes, $date);
            if (!$salaryType || $salaryType == static::$_defaultType)
                return $this->siteSalary->get_day_salary($date);
        }

        if (!isset($this->details[$salaryType]))
            $this->details[$salaryType] = new Salary\SalaryDetails($this->targetType, $this->targetID, $salaryType);

        $salary = $this->details[$salaryType]->day_salary($date);
        $salary->isHoliday = $this->siteSalary->holiday_or_weekend($date);

        return $salary;
    }

    public function change_salary($newType, $fromDate = false){
        $today = date('Y-m-d');

        // if changing from "default" to anything else and site's type matches new type and no data for new type yet - copy from site's active data
        if ($newType != static::$_defaultType && $this->siteSalary->active_type($today) == $newType){
            $this->load();

            $lastType = end($this->changes);
            if (!$lastType || $lastType == static::$_defaultType){
                $sd = new Salary\SalaryDetails($this->targetType, $this->targetID, $newType);
                if (!$sd->has_data()){
                    $salary = $this->siteSalary->get_day_salary($today);
                    $sd->change_rate('wday', $today, $salary->rateRegular);
                    $sd->change_rate('wend', $today, $salary->rateWeekend);
                }
            }
        }

        return parent::change_salary($newType, $fromDate);
    }

    public function get_order_salary($date, $length, $price){
        $this->_cache[$date] = $salary = $this->_cache[$date] ?? $this->get_day_salary($date);

        if ($salary->type == 'percent')
            return round($price * ($salary->isHoliday ? $salary->rateWeekend : $salary->rateRegular) / 100, 2);
        return round($length * ($salary->isHoliday ? $salary->rateWeekend : $salary->rateRegular), 2);
    }

    public function change_rate($type, $day, $fromDate = false, $rate = 0){
        if (!$type || !in_array($type, static::$_salaryTypes))
            throw new \Exception("Illegal salary type: " . $type);

        $sd = new Salary\SalaryDetails($this->targetType, $this->targetID, $type);
        $sd->change_rate($day, $fromDate, $rate);

        return $this;
    }
}
