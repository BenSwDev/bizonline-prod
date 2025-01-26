<?php
class UtilsDate {
    public static function nightCount($from, $till){
        return round((strtotime($till) - strtotime($from)) / 86400);
    }

    public static function date2db($date, $delim = null){
        if (!is_null($delim))
            $d = array_map('intval', explode($delim, $date));
        elseif (preg_match('/^\d?\d(\D)\d?\d\1\d{2,4}$/', $date, $match))
            $d = array_map('intval', explode($match[1], $match[0]));
        else
            return null;
        return (count($d) == 3 && checkdate($d[1], $d[0], $d[2])) ? implode('-', [$d[2], str_pad($d[1], 2, '0', STR_PAD_LEFT), str_pad($d[0], 2, '0', STR_PAD_LEFT)]) : null;
    }

    public static function db2date($date, $delim = '/'){
        return implode($delim, array_reverse(explode('-', $date)));
    }

    public static function getRange($fromDate, $toDate, $inclusive = true){
        $result = [];

        try {
            $end = $inclusive ? new DateTime($toDate) : (new DateTime($toDate))->add(new DateInterval('P1D'));

            $period = new DatePeriod(new DateTime($fromDate), new DateInterval('P1D'), $end);
            foreach ($period as $key => $value)
                $result[] = $value->format('Y-m-d');

            return $result;
        }
        catch (Exception $e){}

        return $result;
    }
}
