<?php
class OrderCopy {
    public $siteID;
    public $date;

    public $orderID;
    public $parentOrder;
    public $treatmentID;
    public $genderClient;
    public $genderMaster;
    public $treatmentLen;

    protected $minDelay;
    protected $cleanTime;

    public function __construct($sid, $date){
        $this->siteID = intval($sid);
        $this->date   = $date;

        list($cleanTime, $minDelay) = udb::single_row("SELECT `waitingTime`, `bookBefore` FROM `sites` WHERE `active` = 1 AND `siteID` = " . $this->siteID, UDB_NUMERIC);

        $this->minDelay = $minDelay;
        $this->cleanTime = $cleanTime;
    }


    public static function createFromOrder($orderID){
        $order = udb::single_row("SELECT * FROM `orders` WHERE `orderID` = " . intval($orderID) . " AND `parentOrder` > 0 AND `parentOrder` <> `orderID`");
        if (!$order)
            throw new Exception("Illegal or non-existant order: " . $orderID);

        $oc = new self($order['siteID'], substr($order['timeFrom'], 0, 10));
        $oc->orderID      = $order['orderID'];
        $oc->parentOrder  = $order['parentOrder'];
        $oc->treatmentID  = $order['treatmentID'];
        $oc->genderClient = $order['treatmentClientSex'];
        $oc->genderMaster = $order['treatmentMasterSex'];
        $oc->treatmentLen = $order['treatmentLen'];
        $oc->cleanTime    = $order['cleanTime'];

        return $oc;
    }

    public function _getFreeRanges($minDateTime = null)
    {
//        $firstTime = date('Y-m-d H:i:00', $minDelay ? strtotime('now +' . $minDelay . ' hours') : time());      // first possible booking

        // pulling working hours
        $que = "SELECT IFNULL(b.treatFrom, a.treatFrom) AS `from`, IFNULL(b.treatTill, a.treatTill) AS `till`
                FROM `year` INNER JOIN `sites_weekly_hours` AS `a` ON (a.weekday = year.weekday AND a.holidayID = 0)
                    LEFT JOIN `sites_periods` AS `sp` ON (sp.siteID = a.siteID AND sp.periodType = 0 AND year.date BETWEEN sp.dateFrom AND sp.dateTo)
                    LEFT JOIN `sites_weekly_hours` AS `b` ON (b.siteID = a.siteID AND b.holidayID = -sp.periodID AND b.weekday = a.weekday AND b.active = 1)
                WHERE year.date = '" . $this->date . "' AND a.siteID = " . $this->siteID;
        $limits = udb::single_row($que);
        if (!$limits || !$limits['from'] || !$limits['till'] || $limits['from'] == '00:00:00' || $limits['till'] == '00:00:00')
            throw new Exception("No treatment hours for site " . $this->siteID);

        $limits['from'] = $this->date . ' ' . $limits['from'];
        $limits['till'] = $this->date . ' ' . $limits['till'];

        if ($minDateTime)
            $limits['from'] = max($minDateTime, $limits['from']);

        $que = "SELECT DISTINCT t.therapistID, t.workerType
                FROM `therapists` AS `t` 
                    " . ($this->treatmentID ? "LEFT JOIN `therapists_treats` AS `tt` ON (t.workerType <> 'fictive' AND tt.therapistID = t.therapistID AND tt.treatmentID = " . $this->treatmentID . ")" : "") . " 
                WHERE t.siteID = " . $this->siteID . " AND t.active = 1 AND t.deleted = 0 AND (t.workStart IS NULL OR t.workStart <= '" . $this->date . "') AND (t.workEnd IS NULL OR t.workEnd >= '" . $this->date . "') 
                    AND " . ($this->genderMaster ? "(t.gender_self & " . $this->genderMaster . ")" : "1") . "
                    AND (t.workerType = 'fictive' OR (" . ($this->genderClient ? "(t.gender_client & " . $this->genderClient . ")" : "1") . " AND " . ($this->treatmentID ? "tt.treatmentID IS NOT NULL" : "1") . "))
                ORDER BY IF(t.workerType = 'fictive', 0, 1)";
        $masters = udb::key_row($que, 'therapistID');
        if (!$masters)      // if no masters found - there's nobody who can do those treatments
            return [];

        $mStr = implode(',', array_keys($masters));

        $que = "SELECT `masterID`, `timeFrom`,`timeUntil` FROM `spaShifts` WHERE `status` = 1 AND `siteID` = " . $this->siteID . " AND `masterID` IN (" . $mStr . ") AND `timeFrom` < '" . $this->date . " 23:59:59' AND `timeUntil` > '" . $this->date . " 00:00:00'";
        $shifts = udb::key_list($que, 'masterID');

        $que = "(SELECT `masterID`, `timeFrom`,`timeUntil` FROM `spaShifts` WHERE `status` <= 0 AND `siteID` = " . $this->siteID . " AND `masterID` IN (" . $mStr . ") AND `timeFrom` < '" . $this->date . " 23:59:59' AND `timeUntil` > '" . $this->date . " 00:00:00')
                UNION ALL
                (SELECT o.therapistID, o.timeFrom, o.timeUntil FROM `orders` AS `o` INNER JOIN `orders` AS `p` ON (o.parentOrder = p.orderID) 
                    WHERE o.siteID = " . $this->siteID . " AND o.parentOrder > 0 AND o.parentOrder <> o.orderID AND o.status = 1 AND p.status = 1 AND o.therapistID IN (" . $mStr . ") 
                         AND o.timeFrom < '" . $this->date . " 23:59:59' AND o.timeUntil > '" . $this->date . " 00:00:00')
                ORDER BY `timeFrom`";
        $stops = udb::key_list($que, 'masterID');

        $minLen = $this->treatmentLen + $this->cleanTime;
        $result = [];

        foreach($masters as $masterID => $mRow){
            if ($mRow['workerType'] == 'fictive')
                $shifts[$masterID] = [['masterID' => $masterID, 'timeFrom' => $limits['from'], 'timeUntil' => $limits['till']]];

            $slots = [];
            if ($shifts[$masterID])
                foreach($shifts[$masterID] as $shift){
                    $from = max($limits['from'], $shift['timeFrom']);
                    $till = $shift['timeUntil'];

                    if ($from > $till)
                        continue;

                    if ($stops[$masterID])
                        foreach($stops[$masterID] as $stop){
                            if ($till < $stop['timeFrom'])          // shift already over
                                break;
                            if ($from > $stop['timeUntil'])         // shift didn't start yet
                                continue;
                            if ($from < $stop['timeFrom'])          // we have free time !
                                $slots[] = $this->_createNewSlot($from, $stop['timeFrom'], $minLen);

                            $from = $stop['timeUntil'];
                        }

                    if ($till > $from)
                        $slots[] = $this->_createNewSlot($from, $till, $minLen);
                }

            if ($slots)
                $result[$masterID] = array_filter($slots);
        }

        return $result;
    }

    protected function _createNewSlot($from, $till, $minLen = null){
        if ($till <= $from)
            return null;

        $range = ['from' => $from, 'till' => $till, 'from_ts' => strtotime($from), 'till_ts' => strtotime($till)];
        $range['length'] = round(($range['till_ts'] - $range['from_ts']) / 60);

        return (is_null($minLen) || $range['length'] >= intval($minLen)) ? $range : null;
    }

    public function countEmptySlots()
    {
        $minLen = $this->treatmentLen + $this->cleanTime;
        $slots  = $this->_getFreeRanges();

        $count     = 0;
        $lastTime  = $firstTime = [0, 0];

        foreach($slots as $masterID => $ranges)
            foreach($ranges as $range){
                if ($range['length'] < $minLen)
                    continue;
                elseif ($range['length'] == $minLen){
                    $count += 1;

                    $from1 = $from2 = $range['from_ts'];
                    $till1 = $till2 = $range['till_ts'];
                }
                else {
                    $count += $tmp = floor($range['length'] / $minLen);

                    $from1 = $range['from_ts'];
                    $till1 = $range['from_ts'] + $minLen * 60;

                    $from2 = $range['from_ts'] + ($tmp - 1) * $minLen * 60;
                    $till2 = $range['from_ts'] + $tmp * $minLen * 60;
                }

                if (!$firstTime[0] || $firstTime[0] > $from1)
                    $firstTime = [$from1, $till1];
                if ($lastTime[1] < $till2)
                    $lastTime = [$from2, $till2];
            }

        return [
            'count' => $count,
            'first' => [$firstTime[0] ? date('Y-m-d H:i:00', $firstTime[0]) : null, $firstTime[1] ? date('Y-m-d H:i:00', $firstTime[1]) : null],
            'last'  => [$lastTime[0] ? date('Y-m-d H:i:00', $lastTime[0]) : null, $lastTime[1] ? date('Y-m-d H:i:00', $lastTime[1]) : null]
        ];
    }


    public function prepareEmptySlots($startTS = null){
        $minLen = $this->treatmentLen + $this->cleanTime;
        $slots  = $this->_getFreeRanges($startTS ? date('Y-m-d H:i:00', $startTS) : null);
        $list   = [];

        foreach($slots as $masterID => $ranges)
            foreach($ranges as $range){
                if ($range['length'] < $minLen)
                    continue;
                elseif ($range['length'] == $minLen)
                    $list[] = ['master' => $masterID, 'from' => $range['from_ts'], 'till' => $range['till_ts']];
                else {
                    $run = $range['from_ts'];

                    while($run + $minLen * 60 <= $range['till_ts']){
                        $list[] = [
                            'master' => $masterID,
                            'from'   => $run,
                            'till'   => $run + $minLen * 60,
                        ];

                        $run = $run + $minLen * 60;
                    }
                }
            }

        usort($list, function($a, $b){
            return $a['from'] <=> $b['from'];
        });

        return $list;
    }
}
