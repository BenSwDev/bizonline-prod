<?php
namespace WuBook;

use \PhpXmlRpc as rpc;

rpc\Autoloader::register();

class WuBookManager {
    const API_URL   = 'https://wired.wubook.net/xrws/';
    const API_TOKEN = 'wr_807d6039-6eb6-4387-a0d7-3c56d6b083fb';

    private $client;

    public function __construct(){
        $this->client = new rpc\Client(static::API_URL);
    }

    /**
     * @param string  $foo    method to execute at server
     * @param array   $params list of parameters to send
     * @return mixed          data in server response
     * @throws Exception
     */
    protected function _send(string $foo, array $params = [])
    {
        try {
file_put_contents(__DIR__ . '/wubook.log', date('Y-m-d H:i:s') . PHP_EOL . print_r($params, true) . PHP_EOL, FILE_APPEND);
            $enc = new rpc\Encoder;
            $prm = array_merge([static::API_TOKEN], $params);

            $req = new rpc\Request($foo, $enc->encode($prm));
            $result = $this->client->send($req);

            $data = $enc->decode($result->value());
            if (!isset($data[0]) || intval($data[0]))
                throw new Exception($data[1] ?? 'XMLRPC error: ' . ($data[0] ?? ''));
file_put_contents(__DIR__ . '/wubook.log', print_r($data[1], true) . PHP_EOL . PHP_EOL, FILE_APPEND);
            return $data[1];
        }
        catch (rpc\Exception $e){
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }


    protected function _parse_order(array $order)
    {
        $result = [
            'wbOrderID'   => $order['reservation_code'],
            'orderStatus' => '',       // should change later
            'modified'    => false,    // may change later
            'orderPrice'  => $order['amount'],
            'discount'    => $order['discount'],
            'currency'    => $order['currency'],
            'created'     => $order['date_received_time'],
            'dateFrom'    => $order['date_arrival'],
            'dateTill'    => $order['date_departure'],
            'rooms'       => [],
            'extras'      => $order['addons_list'] ?? [],
            'pc_adults'   => $order['men'],
            'pc_kids'     => $order['children'],
            'customerCountry' => $order['customer_country'],
            'customerCity'    => $order['customer_city'],
            'customerAddress' => $order['customer_address'],
            'customerZip'     => $order['customer_zip'],
            'customerEmail'   => $order['customer_mail'],
            'customerPhone'   => $order['customer_phone'],
            'customerName'    => $order['customer_name'],
            'customerSurname' => $order['customer_surname'],
            'customerRemark'  => $order['customer_notes'],
            'customerLang'    => $order['customer_language_iso']
        ];

        switch($order['status']){
            case 1: $result['orderStatus'] = 'confirmed'; break;
            case 2: $result['orderStatus'] = 'pending'; break;
            case 3: $result['orderStatus'] = 'refused'; break;
            case 4: $result['orderStatus'] = 'accepted'; break;
            case 5: $result['orderStatus'] = 'cancelled'; break;
        }

        $occ = [];
        if (is_array($order['rooms_occupancies']))
            foreach($order['rooms_occupancies'] as $room)
                $occ[$room['id']] = $room['occupancy'];

        $order['rooms'] = array_map('intval', explode(',', $order['rooms']));

        if ($rc = count($order['rooms']))
            foreach($order['rooms'] as $rid){
                $adults = min($occ[$rid] ?? 1, floor(($order['men'] / $rc--) ?: 1));
                $kids   = max($occ[$rid] - $adults, 0);

                $result['rooms'][] = ['roomID' => $rid, 'adults' => $adults, 'kids' => $kids, 'board' => $order['boards'][$rid]];
            }

        if ($order['channel_reservation_code']){
            $result['otaOrderID'] = $order['channel_reservation_code'];
            $result['otaChannel'] = $order['id_channel'];
        }

        if ($order['was_modified'] && $order['modified_reservations'] != $order['reservation_code']){
            $result['modified']    = true;
            $result['prevOrderID'] = $order['modified_reservations'];
        }

        if ($order['deleted_at_time']){
            $result['cancelled'] = $order['deleted_at_time'];
            switch($order['deleted_from']){
                case 1: $result['cancelBy'] = 'customer'; break;
                case 2: $result['cancelBy'] = 'backOffice'; break;
                case 3: $result['cancelBy'] = 'distributor'; break;
                case 4: $result['cancelBy'] = 'wubook'; break;
                case 5: $result['cancelBy'] = 'alien'; break;
                case 6: $result['cancelBy'] = 'wired'; break;
            }
        }

        return $result;
    }

    /**
     * @param  int    $siteID  property ID
     * @return array           array of room assigned to property
     * @throws Exception
     */
    public function fetch_rooms(int $siteID)
    {
        $sid = intval($siteID);
        if (!$sid)
            throw new Exception("Illegal/missing property ID in 'fetch_rooms': " . $siteID);

        return $this->_send('fetch_rooms', [$sid]);
    }

    /**
     * @param int    $siteID
     * @param string $name
     * @param int    $pax
     * @param float  $defprice
     * @param string $short
     * @param int    $roomCount
     * @param array  $names
     * @return int   newly created wubook room ID
     * @throws Exception
     */
    public function create_regular_room(int $siteID, string $name, int $pax, float $defprice, string $short, $roomCount = 1, $names = [])
    {
        $sid = intval($siteID);
        if (!$sid)
            throw new Exception("Illegal/missing property ID in 'create_regular_room': " . $siteID);

        $params = [
            $sid,       // property ID
            0,          // room is NOT WooDoo only (may be need to change)
            $name,      // room name
            intval($pax),       // max pax
            round($defprice),   // default price
            intval($roomCount) ?: 1,    // room count
            $short,     // short room name (up to 4 letters)
            'nb'        // default pansion - no board
        ];

        if ($names)
            $params[] = $names;     // names in languages

        return $this->_send('new_room', $params);
    }


    public function get_avail(int $siteID, string $from, string $till, array $rooms = []){
        $sid = intval($siteID);
        if (!$sid)
            throw new Exception("Illegal/missing property ID in 'get_avail': " . $siteID);

        $params = [
            $sid,       // property ID
            (new \DateTime($from))->format('d/m/Y'),    // date from
            (new \DateTime($till))->format('d/m/Y')     // date till
        ];

        if ($rooms)
            $params[] = array_values(array_map('intval', $rooms));

        $data = $this->_send('fetch_rooms_values', $params);

        $result = [];
        $range  = \UtilsDate::getRange($from, $till, true);    // array will date from MIN to MAX
        foreach($data as $roomID => $dates){
            $temp = [];
            foreach($range as $i => $date)
                $temp[$date] = $data[$i]['avail'] ?? -1;

            $result[$roomID] = $temp;
        }

        return $result;
    }


    /**
     * @param int   $siteID  property ID
     * @param array $avails  multidimentional array in format [roomID => [YYYY-MM-DD => available]]
     * @return $this
     * @throws Exception
     */
    public function update_avail(int $siteID, array $avails)
    {
        $sid = intval($siteID);
        if (!$sid)
            throw new Exception("Illegal/missing property ID in 'update_avail': " . $siteID);
        if (empty($avails) || !is_array($avails))
            throw new Exception("Illegal/missing dates list in 'update_avail': " . $avails);

        // selection MIN and MAX dates from data to build request structure
        $minDate = '9999-99-99';
        $maxDate = '0000-00-00';
        foreach($avails as $roomID => $list){
            if (!$list)
                continue;
            elseif (!is_array($list))
                throw new Exception("Wrong format for room " . $roomID . " in 'update_avail': " . $list);

            $dates = array_keys($list);

            $minDate = min($minDate, min($dates));
            $maxDate = max($maxDate, max($dates));
        }

        $empty = new \stdClass;         // empty value for dates that don't need updates
        $range = \UtilsDate::getRange($minDate, $maxDate, true);    // array will date from MIN to MAX

        $final = [];
        foreach($avails as $roomID => $list){
            if (!$list)
                continue;

            $local = [];
            foreach($range as $date)
                $local[] = isset($list[$date]) ? ['avail' => intval($list[$date])] : $empty;

            $final[] = ['id' => intval($roomID), 'days' => $local];
        }

        $dcon = implode('/', array_reverse(explode('-', $minDate)));

        $this->_send('update_avail', [$sid, $dcon, $final]);

        return $this;
    }

    /**
     * @param int    $siteID  property ID
     * @param string $url     callback API URL
     * @return $this
     * @throws Exception
     */
    public function set_api_url(int $siteID, string $url)
    {
        $sid = intval($siteID);
        if (!$sid)
            throw new Exception("Illegal/missing property ID in 'set_api_url': " . $siteID);
        if (!$url)
            throw new Exception("Illegal/missing url in 'set_api_url': " . $url);

        $this->_send('push_activation', [$sid, $url]);

        return $this;
    }

    /**
     * @param int $siteID  property ID
     * @return $this
     * @throws Exception
     */
    public function remove_api_url(int $siteID){
        $sid = intval($siteID);
        if (!$sid)
            throw new Exception("Illegal/missing property ID in 'remove_api_url': " . $siteID);

        $this->_send('push_activation', [$sid, '']);

        return $this;
    }

    /**
     * @param int $siteID  property ID
     * @param int $orderID booking code
     * @return array
     * @throws Exception
     */
    public function get_single_order(int $siteID, int $orderID){
        $sid = intval($siteID);
        if (!$sid)
            throw new Exception("Illegal/missing property ID in 'get_single_order': " . $siteID);

        $result = $this->_send('fetch_booking', [$sid, $orderID, true]);
//print_r($result[0]);
        return $this->_parse_order(isset($result[0]) ? $result[0] : $result);
    }

    /**
     * @param int   $siteID   property ID
     * @param array $orderIDs list of order IDs
     * @return mixed
     * @throws Exception
     */
    public function mark_bookings(int $siteID, array $orderIDs){
        $sid = intval($siteID);
        if (!$sid)
            throw new Exception("Illegal/missing property ID in 'mark_bookings': " . $siteID);

        $oids = array_values(array_filter(array_unique(array_map('intval', $orderIDs))));

        $result = $this->_send('mark_bookings', [$sid, $oids]);

        return $result;
    }
}


class Exception extends \Exception {}
