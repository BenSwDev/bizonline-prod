<?php
class Silverbyte extends baseEngine {
    protected $uri     = 'https://secureapi.webbnb.com/G4RouterCentral/api/';
    //protected $uri     = 'https://partner.webbnb.com/G4Router/api/';

    protected $client   = null;

    protected $userName = 'HapisgaSITE';
    protected $password = 'HapisgaSITE';
    //protected $customerID = '41';     // test
    protected $customerID = '1674';     // production

    public function __construct(){
        $this->board_map = array(0 => 'RO', 1 => 'RO', 2 => 'B/B', 3 => 'HB', 4 => 'FB');
    }

    private function send($path, $req = array())
    {
        $params = array_merge(array(
              'userName'   => $this->userName
            , 'password'   => $this->password
            , 'customerID' => $this->customerID
        ), $req);

        $curl = curl_init($this->uri.$path);
$this->log('Request: ' . json_encode($params) . PHP_EOL);
        curl_setopt($curl, CURLOPT_POST, true);
        //curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        //curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        //curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        //curl_setopt($curl, CURLOPT_VERBOSE, 1);
        $res = curl_exec($curl);
$this->log('Response: ' . print_r($res, true) . PHP_EOL);
        //$info = curl_getinfo($curl);

//        if(curl_errno($curl)){
//            echo 'Request Error:' . curl_error($curl);
//        }
        curl_close($curl);


        return $res;
    }

    protected function log($text){
        static $first = true;
        file_put_contents(rtrim(dirname(__FILE__),'/').'/../../../logs/silver.log',($first ? "\n------------------------\n".date('Y-m-d H:i:s')."\n" : '').print_r($text,true), FILE_APPEND | LOCK_EX);
        $first = false;
    }

    protected function get_rooms_data($sites, $rooms, $date, $nights, $people = array(2,0,0), $pansion = 0)
    {
        $roomsList = array();
        if ($rooms){
            $rl = is_array($rooms) ? $rooms : array($rooms);
            foreach($rl as $roomID){
                $roomsList[] = array(
                    'roomCategory' => $roomID,
                    'fromDate' => $date . 'T00:00:00',
                    'nights'   => $nights,
                    'adults'   => $people[0],
                    'children' => $people[1],
                    'infants'  => $people[2],
                    'disableCacheSearch' => false,
                    'isLocal'  => true,
                );
            }
        } else
            $roomsList[] = array(
                'fromDate' => $date . 'T00:00:00',
                'nights'   => $nights,
                'adults'   => $people[0],
                'children' => $people[1],
                'infants'  => $people[2],
                'disableCacheSearch' => false,
                'isLocal'  => true,
            );

        if ($pansion){
            foreach($roomsList as &$c)
                $c['planCode'] = $this->board_map[$pansion];
            unset($c);
        }

        $params = array(
              'reqHotelsList' => array_map(function($h){
                return array('hotelID' => intval($h));
            }, is_array($sites) ? $sites : array($sites))
            , 'roomsList' => $roomsList
        );

        $res = $this->send('availability/rooms', $params);

        return json_decode($res, true);
    }

    public function get_sites($ids, $date, $nights, $people = array(2,0,0), $pansion = 0)
    {
        $cache  = array();
        $result = array();
        $data   = $this->get_rooms_data($ids, 0, $date, $nights, $people, $this->board_in($pansion));

        if ($data){
            if(!isset($data['data']))
                return $data;

            foreach($data['data']['roomAvailabiltyList'] as $hotel)
                foreach($hotel['roomPriceList'] as $room){
                    if (!isset($result[$hotel['hotelID']]) || $room['totalPrice'] < $result[$hotel['hotelID']]['real_price']){
                        $result[$hotel['hotelID']] = array(
                              'roomTypeID' => $room['roomCategory']
                            , 'real_price' => $room['totalPrice']
                            , 'base_price' => $room['totalPrice']
                            , 'available'  => $this->max_with_key($room['pricePerDayList'], 'availableRooms')
                            , 'sessionID'  => $room['sessionID']
                            //, 'pansion'    => $tmp
                        );
                    }

                    $cache[$hotel['hotelID']][$room['roomCategory']][$this->board_out($room['planCode'])] = $room['totalPrice'];
                }

            foreach($result as $sid => &$site)
                $site['pansion'] = $cache[$sid][$site['roomTypeID']];
            unset($site);
        }
        return $result;
    }

    public function max_with_key($array, $key) {
        if (!is_array($array) || count($array) == 0) return 0;
        $max = $array[0][$key];
        foreach($array as $a) {
            if($a[$key] > $max) {
                $max = $a[$key];
            }
        }
        return $max;
    }

    public function min_with_key($array, $key) {
        if (!is_array($array) || count($array) == 0) return 0;
        $min = $array[0][$key];
        foreach($array as $a) {
            if($a[$key] < $min) {
                $min = $a[$key];
            }
        }
        return $min;
    }

     public function get_rooms($id, $date, $nights, $people = array(2,0,0), $pansion = -1)
     {
         $cache  = array();
         $result = array();
         $data   = $this->get_rooms_data(array($id), 0, $date, $nights, $people, $this->board_in($pansion));

         if ($data){
             if(!isset($data['data']))
                 return $data;

             foreach($data['data']['roomAvailabiltyList'] as $hotel)
                 foreach($hotel['roomPriceList'] as $room){
                     if (!isset($result[$room['roomCategory']]) || $room['totalPrice'] < $result[$room['roomCategory']['real_price']]){
                         $result[$room['roomCategory']] = array(
                               'roomTypeID' => $room['roomCategory']
                             , 'real_price' => $room['totalPrice']
                             , 'base_price' => $room['totalPrice']
                             , 'available'  => $this->max_with_key($room['pricePerDayList'], 'availableRooms')
                             , 'sessionID'  => $room['sessionID']
                             //, 'pansion'    => $tmp
                         );
                     }

                     $cache[$room['roomCategory']][$this->board_out($room['planCode'])] = $room['totalPrice'];
                 }

             foreach($result as $rid => &$room)
                 $room['pansion'] = $cache[$room['roomTypeID']];
             unset($site);
        }

        return $result;
    }

    public function get_room_price($siteID, $roomID, $date, $nights, $people = array(2,0,0), $pansion = -1)
    {
        $data   = $this->get_rooms_data($siteID, $roomID, $date, $nights, $people, $this->board_in($pansion));

        if ($data){
            if(!isset($data['data']))
                return $data;

            return $data['data']['roomAvailabiltyList'][0]['roomPriceList'][0]['totalPrice'];
        }
        return -1;
    }

    public function create_booking($book){
        $rooms = [];

        foreach($book->rooms as $room){
            $data = $this->get_rooms_data($book->siteID, $room->roomID, $book->dateFrom, $book->nights, [$room->pax->adults, $room->pax->kids, 0], $this->board_in($room->pansion ?: 0));
            $avail = $this->min_with_key($data['data']['roomAvailabiltyList'][0]['roomPriceList'][0]['pricePerDayList'], 'availableRooms');

            if (!$avail || $avail < $room->roomCount)
                throw new Exception('Not enough free units for room ' . $room->roomID);

            $rooms[] = [
                'sessionID'    => $data['data']['roomAvailabiltyList'][0]['roomPriceList'][0]['sessionID'],
                'primaryGuest' => [
                    'lastName'        => $book->clientInfo->last_name,
                    'firstName'       => $book->clientInfo->name,
                    'nationalityCode' => 'IL',
                    'webPassword'     => mt_rand(1000, 9999)
                ],
                'roomComments' => '',
                'customerPriceRemarks' => '',
                //'companions'   => [],
                //'specialServiceList'   => [],
                'IsToUseGuestPoints'   => 0,
                'IsToSetOrigPrice'     => 1,
                'isToValidateClubMemberActiveStatus' => 0
            ];
        }

        $req = [
            'hotelID'        => $book->siteID,
            'voucherNumber'  => '',
            'fromDate'       => $book->dateFrom . 'T00:00:00.0000000+02:00',
            'toDate'         => $book->dateTill . 'T00:00:00.0000000+02:00',      // check that date is correct
            'guestRemarks'   => 'Happy New Year!',
            'paymentBy'      => 'Guest',
            'roomsList'      => $rooms,
            'invoiceDetails' => [
                'lastName'   => $book->clientInfo->last_name,
                'firstName'  => $book->clientInfo->name,
                'email'      => $book->clientInfo->email,
                'phone'      => $book->clientInfo->phone,
            ],
            'mergGuestFields'  => 32            // "disable" - every entry is new guest
        ];
file_put_contents('../../../logs/silver.log', '---------------------------' . PHP_EOL . date('Y-m-d H:i:s') . PHP_EOL . 'Request: ' . print_r($req, true) . PHP_EOL, FILE_APPEND | LOCK_EX);
        $res = $this->send('reservation/insert', $req);
        $dec = json_decode($res, true);
file_put_contents('../../../logs/silver.log', 'Response: ' . print_r($res, true) . PHP_EOL . 'Decoded: ' . print_r($dec, true) . PHP_EOL, FILE_APPEND | LOCK_EX);
        if (!is_array($dec))
            throw new Exception('Unexpected result: ' . print_r($dec, true));
        elseif ($dec['error'])
            throw new Exception('Booking error: ' . $dec['message']['text']);

        return $dec['data']['masterID'];
    }

    public function cancel_booking($siteID, $orderID){
        $req = [
            'masterID'      => $orderID,
            'hotelID'       => $siteID,
            'reservationID' => null,
            'prePayment'    => null
        ];

        $res = $this->send('reservation/cancel', $req);
        $dec = json_decode($res, true);

        if (!is_array($dec))
            throw new Exception('Unexpected result: ' . print_r($dec, true));
        elseif ($dec['error'])
            throw new Exception('Cancelling error: ' . $dec['message']['text']);

        return $dec['data']['isCancel'];
    }

    public function get_booking($master){
        $req = [
            'masterID' => $master
        ];

        $res = $this->send('reservation/booking', $req);

        return json_decode($res, true);
    }

    public function site_list($raw = false){

        $res = $this->send('hotels');

        $result = array();
        if ($data = json_decode($res, true)){
            if(!isset($data['data']))
                return $data;

            foreach($data['data'] as $hotel)
                $result[$hotel['hotelID']] = $hotel['name'];
        }
        return $result;
    }

    public function room_list($siteID)
    {
        $res = $this->send('rooms', array('hotelID' => $siteID));

        $result = array();
        if ($data = json_decode($res, true)){
            if(!isset($data['data']))
                return $data;

            foreach($data['data'] as $room)
                $result[$room['roomCategory']] = $room['name'];
        }
        return $result;
    }
}
