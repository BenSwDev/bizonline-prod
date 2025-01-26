<?php
require_once "auth.php";
require_once __DIR__ . "/../cms/classes/ProtelParser/class.ProperParser.php";

$result = new JsonResult();

$IA = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;

try {
    switch($IA['act']){
        case 'clientInfo':
            $input = typemap($IA, [
                'sid' => 'int',
                'val' => 'string',
                'src' => 'string'
            ]);

            if (!$input['sid'] || !$_CURRENT_USER->has($input['sid']))
                throw new Exception("Access denied to site #" . $input['sid']);

            $protel = udb::single_row("SELECT ps.* FROM `protel_sites` AS `ps` INNER JOIN `sites` ON (sites.protelID = ps.id) WHERE ps.active = 1 AND sites.siteID = " . $input['sid']);
            if (!$protel)
                throw new Exception("Cannot find connected Protel hotel");

            if (mb_strlen($input['val'], 'UTF-8') < 3 && $input['src'] != 'guestAppt'){
                $result['clients'] = [];
                break;
            }

            if ($input['src'] == 'guestAppt'){
                $where = "`room` = '" . udb::escape_string($input['val']) . "' OR `room` LIKE '%~'";
            } else {
                $flist = ['phone' => 'customerPhone', 'name' => 'customerName'];
                $where = "`" . implode("` LIKE '%" . udb::escape_string($input['val']) . "%' OR `", $flist) . "` LIKE '%" . udb::escape_string($input['val']) . "%'";
            }

            $que = "SELECT `innerID`, `customerName` AS `name`, `customerPhone` AS `phone`, `room`, `orderData`
                    FROM `protel_orders` 
                    WHERE `hotelID` = " . $protel['id'] . " AND `status` IN ('Reserved', 'In-house') AND (" . $where . ") ORDER BY FIELD(`status`, 'In-house', 'Reserved'), `vacationStart`";
            $clients = udb::single_list($que);

            if ($input['src'] == 'guestAppt'){
                foreach($clients as $i => $client){
                    if (substr($client['room'], -1) != '~')
                        continue;

                    $data = unserialize($client['orderData']);
                    $drop = true;

                    foreach($data->HotelReservations[0]->RoomStays as $stay){
                        foreach($stay->Rooms as $room)
                            if ($room->_RoomID == $input['val'])
                                $drop = false;
                    }

                    if ($drop)
                        unset($clients[$i]);
                }
            }

            $tmp = [];
            foreach($clients as $client)
                $tmp[] = [
                    'pid'  => $client['innerID'],
                    'name' => $client['name'],
                    'phone' => $client['phone'],
                    'room' => $client['room'],
                    'roomText' => is_numeric($client['room']) ? 'חדר ' . $client['room'] : $client['room'],
                    '_text' => implode(' - ', array_filter([$client['name'], $client['phone'], $client['room'] ? 'חדר ' . $client['room'] : '']))
                ];

            $result['clients'] = $tmp;
            break;

//        case 'delete':
//            $clientID = intval($_POST['cid']);
//            $siteID   = intval($_POST['sid']);
//
//            if (!$siteID || !$_CURRENT_USER->has($siteID))
//                throw new Exception("Access denied to site #" . $siteID);
//
//            udb::query("DELETE FROM `crm_clients` WHERE `siteID` = " . $siteID . " AND `clientID` = " . $clientID);
//            break;

        case 'welcome1':
        case 'welcome2':
            $input = typemap($IA, [
                'sid'    => 'int',
                'val'    => 'int',
                'before' => 'int'
            ]);

            if (!$input['sid'] || !$_CURRENT_USER->has($input['sid']))
                throw new Exception("Access denied to site #" . $input['sid']);

            $protel = udb::single_row("SELECT `protelID`, `protel_config` FROM `sites` WHERE `siteID` = " . $input['sid']);
            if (!$protel['protelID'])
                throw new Exception("Spa not linked to Protel");

            $protel_cfg = $protel['protel_config'] ? (json_decode($protel['protel_config'], true) ?: []) : [];

            if ($IA['act'] == 'welcome2')
                $protel_cfg['sms_welcome2'] = $input['val'] ? 1 : 0;
            elseif ($IA['act'] == 'welcome1')
                $protel_cfg['sms_welcome1'] = ($input['val'] && $input['before'] > 0) ? min(7, $input['before']) : 0;

            udb::update('sites', ['protel_config' => json_encode($protel_cfg, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE)], "`siteID` = " . $input['sid']);
            break;

        default:
            throw new Exception('Unknown action');
    }

    $result['status'] = 0;
}
catch (Exception $e){
    $result['error']  = $e->getMessage();
    $result['status'] = $e->getCode() ?: 99;
}
