<?php
class MasterPay {
    protected $masterID;
    protected $siteID;

    private $masterPay;
    private $siteWeek;

    public function __construct($id){
        $this->masterID = intval($id);
        $mRow = udb::single_row("SELECT * FROM `therapists` WHERE /*`active` = 1 AND*/ `therapistID` = " . $this->masterID);

        if (!$mRow)
            throw new Exception('Master non-existant or inactive');

        $this->siteID = $mRow['siteID'];
        $this->masterPay = $mRow['salary'] ? json_decode($mRow['salary']) : null;

        if (!$this->masterPay || $this->masterPay->activeType == 'default'){
            $sitePay = udb::single_value("SELECT `salaryDefault` FROM `sites` WHERE `siteID` = " . $this->siteID);
            $this->masterPay = $sitePay ? json_decode($sitePay) : null;
        }

        if ($this->masterPay && !isset($this->masterPay->activeRate))
            $this->masterPay->activeRate = $this->masterPay->all->{$this->masterPay->activeType};

        $this->siteWeek = udb::key_row("SELECT `weekday`, `isWeekend`, `extraPrice` FROM `sites_weekly_hours` WHERE `holidayID` = 0 AND `siteID` = " . $this->siteID, 'weekday');
    }

    public function get_treat_pay($datetime, $len, $cost){
        if (!$this->masterPay) {
            throw new Exception('No payment data for master ' . $this->masterID); }

        list($wday, $date) = explode(':', date('w:Y-m-d', strtotime($datetime)));
        $weekday = $this->siteWeek[$wday] ?? ['isWeekend' => 0, 'extraPrice' => 0];

        $que1 = "SELECT a.isWeekend, a.extraPrice FROM `sites_weekly_hours` AS `a` INNER JOIN `sites_periods` AS `p` ON (p.siteID = a.siteID AND a.holidayID = -p.periodID AND a.weekday = " . $wday . ") 
                    WHERE p.siteID = " . $this->siteID . " AND p.periodType = 0 AND '" . $date . "' BETWEEN p.dateFrom AND p.dateTo";
        $que2 = "SELECT a.isWeekend, a.extraPrice FROM `sites_weekly_hours` AS `a` INNER JOIN `not_holidays` AS `p` ON (a.holidayID = p.notHolidayID AND a.weekday = " . $wday . ") 
                    WHERE a.siteID = " . $this->siteID . " AND '" . $date . "' BETWEEN p.dateStart AND p.dateEnd AND p.active = 1";
        if ($period = udb::single_row($que1))
            $weekday = $period;
        elseif ($holiday = udb::single_row($que2))
            $weekday = $holiday;

        $result = [
            'weekend' => !!$weekday['isWeekend'],
            'rate'    => $weekday['isWeekend'] ? $this->masterPay->activeRate->wend : $this->masterPay->activeRate->wday
        ];

        switch($this->masterPay->activeType){
            case 'minute' : $result['total'] = round($result['rate'] * $len, 1); break;
            case 'percent': $result['total'] = round($result['rate'] * $cost / 100, 1); break;
            default:
                throw new Exception('Unknown payment plan: ' . $this->masterPay->activeType);
        }

        return $result;
    }

    public function active($key = null){
        switch($key){
            case 'type': return $this->masterPay->activeType ?? null;
            case 'rate': return $this->masterPay->activeRate ?? 0;
        }
        return ['type' => $this->masterPay->activeType, 'rate' => $this->masterPay->activeRate];
    }

    public function site(){
        return $this->siteID;
    }
}
