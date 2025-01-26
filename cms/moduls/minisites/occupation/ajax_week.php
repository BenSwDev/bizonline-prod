<?php
include_once "../../../bin/system.php";
include_once "../../../_globalFunction.php";

include_once "../../../classes/class.JsonResult.php";

$result = new JsonResult(['status' => 99]);

$siteID = intval($_POST['siteID'] ?? $_GET['siteID']);

switch($_GET['act'] ?? $_POST['act']){
    case 'week':
        $start = typemap($_GET['start'], 'date');

        $tmp = strtotime($start . ' 10:00:00');
        $start = date('Y-m-d', $tmp - date('w', $tmp) * 24 * 3600);     // getting sunday in case of error
        $end   = date('Y-m-d', $tmp + 6 * 24 * 3600);

        $rooms  = udb::key_row("SELECT rooms.roomID AS `id`, rooms.roomName AS `name`, rooms.roomCount AS `units` FROM `rooms` WHERE `siteID` = " . $siteID . " ORDER BY `showOrder`", 'id');
        $free   = udb::key_list("SELECT * FROM `unitsDates` WHERE `roomID` IN (" . implode(',', array_keys($rooms)) . ") AND `date` BETWEEN '" . $start . "' AND '" . $end . "' ORDER BY `date`", 'roomID');

        $que = "SELECT orders.orderID, orders.vacationStart, orders.vacationNights, orders.clientName, orderUnits.roomID 
                FROM `orders` INNER JOIN `orderUnits` USING(`orderID`) 
                WHERE orders.siteID = " . $siteID . " AND orders.vacationStart <= '" . $end . "' AND orders.vacationEnd > '" . $start . "' AND orders.status = 'confirmed'
                ORDER BY orders.vacationStart";
        $orders = udb::key_list($que, 'roomID');

        $total = [];
        foreach($rooms as $rid => $room){
            $room['orders'] = [];
            if ($orders[$rid])
                foreach($orders[$rid] as $order)
                    $room['orders'][] = [
                        'orderID' => $order['orderID'],
                        'start'   => $order['vacationStart'],
                        'nights'  => $order['vacationNights'],
                        'title'   => $order['clientName']
                    ];

            $room['empty'] = [];
            if ($free[$rid])
                foreach($free[$rid] as $empty)
                    $room['empty'][$empty['date']] = $empty['free'];

            $total[] = $room;
        }

        $result['rooms'] = $total;
        $result['status'] = 0;
        break;

    case 'free':
        if (!is_array($_POST['data']))
            die('Data error. Please reload page');

        foreach($_POST['data'] as $date => $rooms){
            $pre  = array_map('intval', array_keys($rooms));
            $rids = udb::single_column("SELECT `roomID` FROM `rooms` WHERE `roomID` IN (" . implode(',', $pre) . ") AND `siteID` = " . $siteID);

            if (count(array_diff($pre, $rids)))
                die('Cannot find rooms: ' . implode(', ', array_diff($pre, $rids)));

            $insert = [];
            foreach($rooms as $rid => $empty)
                $insert[] = "(" . intval($rid) . ", '" . typemap($date, "date") . "', " . intval($empty) . ")";

            if (count($insert))
                udb::query("INSERT INTO `unitsDates`(`roomID`, `date`, `free`) VALUES" . implode(',', $insert) . " ON DUPLICATE KEY UPDATE `free` = VALUES(`free`)");
        }

        $result['status'] = 0;
        break;

    case 'range':
        $input = typemap($_GET, [
            'from'   => 'date',
            'nights' => 'int'
        ]);

        if (!$input['from'])
            die('Illegal start date');
        if ($input['nights'] <= 0)
            die('Minimum nights: 1');

        $que = "SELECT rooms.roomID, MIN(IFNULL(unitsDates.free, rooms.roomCount)) AS `count` 
                FROM `rooms` LEFT JOIN `unitsDates` ON (unitsDates.roomID = rooms.roomID AND unitsDates.date " . ($input['nights'] > 1 ? "BETWEEN '" . $input['from'] . "' AND '" . $input['from'] . "' + INTERVAL " . ($input['nights'] - 1) . " DAY" : " = '" . $input['from'] . "'") . ") 
                WHERE rooms.active = 1 AND rooms.siteID = " . $siteID . "
                GROUP BY rooms.roomID
                ORDER BY NULL";
        $result['rooms']  = udb::key_value($que);
        $result['status'] = 0;
        break;

    case 'filler':
        $input = typemap($_POST, [
            'from'   => 'date',
            'nights' => 'int',
            'rooms'  => ['int' => 'int']
        ]);

        if (!$input['from'])
            die('Illegal start date');
        if ($input['nights'] <= 0)
            die('Minimum nights: 1');
        if (!$input['rooms'])
            die('No rooms selected');

        $dateCond = ($input['nights'] > 1) ? "BETWEEN '" . $input['from'] . "' AND '" . $input['from'] . "' + INTERVAL " . ($input['nights'] - 1) . " DAY" : " = '" . $input['from'] . "'";

        $time = strtotime($input['from'] . ' 10:00:00');
        $dates = [$input['from']];
        for($i = 1; $i < $input['nights']; ++$i)
            $dates[] = date('Y-m-d', $time + 3600 * 24 * $i);

        udb::query("LOCK TABLES `unitsDates` WRITE, `rooms` READ");

        $que = "SELECT rooms.roomID, MIN(IFNULL(unitsDates.free, rooms.roomCount)) AS `count` 
                FROM `rooms` LEFT JOIN `unitsDates` ON (unitsDates.roomID = rooms.roomID AND unitsDates.date " . $dateCond . ") 
                WHERE rooms.active = 1 AND rooms.siteID = " . $siteID . " AND rooms.roomID IN (" . implode(',', array_keys($input['rooms'])) . ")
                GROUP BY rooms.roomID
                ORDER BY NULL";
        $rooms = udb::key_value($que);

        $or = [];
        foreach($input['rooms'] as $roomID => $rc){
            if ($rc && (!$rooms[$roomID] || $rooms[$roomID] < $rc))
                die("Room #" . $roomID . " only has " . intval($rooms[$roomID]) . " free. (" . $rc . " required)");

            $or[] = "(#, " . $roomID . ", " . $rc . ")";
        }

        foreach($input['rooms'] as $roomID => $rc){
            $ud = [];
            foreach($dates as $date)
                $ud[] = "(" . $roomID . ", '" . $date . "', " . ($rooms[$roomID] - $rc) . ")";

            udb::query("INSERT INTO `unitsDates`(`roomID`, `date`, `free`) VALUES" . implode(',', $ud) . " ON DUPLICATE KEY UPDATE `free` = `free` - " . $rc);
        }

        /*if (count($ud))
            udb::query("INSERT INTO `unitsDates`(`roomID`, `date`, `free`) VALUES" . implode(',', $ud) . " ON DUPLICATE KEY UPDATE `free` = VALUES(`free`)");*/

        udb::query("UNLOCK TABLES");

        /*$que = "INSERT INTO `orders`(`siteID`, `status`, `vacationStart`, `vacationEnd`, `vacationNights`)
                    VALUES(" . $siteID . ", 'filler', '" . $input['from'] . "', '" . $input['from'] . "' + INTERVAL " . $input['nights'] . " DAY, " . $input['nights'] . ")";
        $orderID = udb::query($que);

        $que = "INSERT INTO `orderUnits`(`orderID`, `roomID`, `amount`) VALUES" . str_replace('#', $orderID, implode(',', $or));
        udb::query($que);*/

        $result['status'] = 0;
        break;
}
