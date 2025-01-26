<?php
include_once __DIR__ . "/Salary/class.Salary.php";

class SalarySite extends Salary\Salary {
    private static $_sites = [];

    protected static $_salaryTypes = ['minute', 'percent'];

    private $_datesCache = [];
    private $_weekends   = [];

    public function __construct($siteID){
        parent::__construct('site', $siteID);

        $this->_weekends = udb::single_column("SELECT `weekday` FROM `sites_weekly_hours` WHERE `active` = 1 AND `holidayID` = 0 AND `isWeekend` = 1 AND `siteID` = " . $this->targetID);

        self::$_sites[$this->targetID] = $this;
    }

    public function get_day_salary($date = null, $salaryType = null){
        if (!$date)
            $date = date('Y-m-d');

        $salary = parent::get_day_salary($date, $salaryType);
        $salary->isHoliday = $this->holiday_or_weekend($date);

        return $salary;
    }

    public function change_salary($newType, $fromDate = false){
        $this->load();

        // if it's first time setting salary for site
        if (!count($this->changes))
            $fromDate = self::MIN_DATE;

        return parent::change_salary($newType, $fromDate);
    }

    public function change_rate($type, $day, $fromDate = false, $rate = 0){
        if (!$type || !in_array($type, static::$_salaryTypes))
            throw new \Exception("Illegal salary type: " . $type);

        $sd = new Salary\SalaryDetails($this->targetType, $this->targetID, $type);

        if (!$fromDate || strcmp($fromDate, date('Y-m-d')) <= 0){       // if no date or date=today
            $last = $sd->get_last_salary();
            if (!$last[$day] || !$last[$day]->date)         // if no records exists
                $fromDate = Salary\Salary::MIN_DATE;
        }

        $sd->change_rate($day, $fromDate, $rate);

        return $this;
    }

    public function holiday_or_weekend($date){
        if ($this->_datesCache[$date])
            return $this->_datesCache[$date];

        $wday = date('w', strtotime($date));

        $que = "SELECT h.weekday 
                FROM `sites_weekly_hours` AS `h` INNER JOIN `sites_periods` AS `p` ON (p.siteID = h.siteID AND p.periodType = 0 AND p.periodID = -h.holidayID) 
                WHERE h.active = 1 AND h.isWeekend = 1 AND h.siteID = " . $this->targetID . " AND h.weekday = " . $wday . " AND '" . udb::escape_string($date) . "' BETWEEN p.dateFrom AND p.dateTo";
        if (udb::single_value($que))
            return $this->_datesCache[$date] = true;

        if (in_array($wday, $this->_weekends))
            return $this->_datesCache[$date] = true;

        return $this->_datesCache[$date] = false;
    }

    public static function factory($siteID){
        return self::$_sites[$siteID] ?? new self($siteID);
    }
}
