<?php
/**
 * Class easyGo
 * @version 2.0.0
 */
class easyGo extends baseEngine {
	protected $wsdl     = 'http://www.onlineres.ezgo.co.il/service.asmx?wsdl';
	protected $client   = null;
	
	protected $channel  = 23;        // channel ID
	protected $password = 'mkesdjiv743jsaxs';       // channel password
    protected $username = 'SpaPlusVillas';

    protected $board_map = array(1 => 'RO', 2 => 'BB', 3 => 'HB', 4 => 'FB');
	
	protected function parse($xml){
		if (preg_match_all('/<([a-z0-9_]+)([^>]+)?>(.*)<\/\1>/ismU',$xml,$match) && count($match[1])){
			$tmp = new stdClass();
			foreach($match[1] as $i => $key) {
				$obj = $this->parse($match[3][$i]);
				if ($prm = trim($match[2][$i])){
					preg_match_all('/([a-z0-9_]+)="([^"]*)"/iU',$prm,$patch);
					foreach($patch[1] as $j => $p)
						$obj->$p = $patch[2][$j];
				}
				
				if (isset($tmp->$key))
					is_array($tmp->$key) ? $tmp->{$key}[] = $obj : $tmp->$key = array($tmp->$key, $obj);
				else
					$tmp->$key = $obj;
			}
			return $tmp;
		}
		return $xml;
	}

	protected static function zeropad($num, $len = 2){
	    return str_pad($num, $len, '0', STR_PAD_LEFT);
    }

	protected function log($text){
		static $first = true;
		file_put_contents(__DIR__.'/../../../logs/easy.log',($first ? "\n------------------------\n".date('Y-m-d H:i:s')."\n" : '').print_r($text,true), FILE_APPEND | LOCK_EX);
		$first = false;
	}
	
	protected function prepare_request($date, $nights, $siteID = 0, $rid = 0, $people = array(2,0,0), $pansion = 0){
	    $roomID = ($rid > 0) ? $rid : 0;
        $code   = ($rid < 0) ? 'AllCombination' : ($rid ? 'Specific' : 'CheapestResult');

	    return [
	        'Id_AgencyChannel' => $this->channel,
            'Authentication'   => [
                'sUsrName'     => $this->username,
                'sPwd'         => $this->password
            ],
            'Date_Start'       => array_combine(['Year', 'Month', 'Day'], array_map('intval', explode('-', $date))),
            'iNights'          => $nights,
            'Id_Agency'        => 0,
            'ID_Region'        => 0,
            'Id_Hotel'         => $siteID,
            'iRoomTypeCode'    => $roomID,
            'eBoardBase'       => $this->board_map[$pansion] ?? 'NotSet',
            'eBoardBaseOption' => $pansion ? 'Specific' : 'CheapestResult',
            'eDomesticIncoming'   => 'Domestic',
            'eRoomTypeCodeOption' => $code,
            'iAdults'          => $people[0] ?? 2,
            'iChilds'          => $people[1] ?? 0,
            'iInfants'         => $people[2] ?? 0,
            'eCurrency'        => 'ILS',
            'bDailyPrice'      => false,
            'bVerbal'          => false
        ];
	}

	protected function wsKeyValuePair($pairs, $key = null){
	    if (!is_array($pairs))
	        return $pairs->Value;
        if (is_null($key))
            return $pairs[0]->Value;

        foreach($pairs as $p)
            if ($p->Key == $key)
                return $p->Value;

        return reset($pairs)->Value;
    }

	public function __construct(){
		$this->client = new SoapClient($this->wsdl, array('encoding'=>'UTF-8', 'cache_wsdl' => WSDL_CACHE_BOTH, 'keep_alive' => false, 'trace' => true));
	}
		
	public function get_sites($ids, $date, $nights, $people = array(2,0,0), $pansion = 0){
		$req    = $this->prepare_request($date, $nights, 0, 0, $people, $pansion);
		$result = array();

        $res  = $this->client->AgencyChannels_SearchHotels(['wsRequest' => $req]);
        $data = $res->AgencyChannels_SearchHotelsResult;

        if (isset($data->Error) && $data->Error->iErrorId)
            throw new Exception($data->Error->sErrorDescription);

        is_array($data->aHotels->wsSearchHotel) or $data->aHotels->wsSearchHotel = array($data->aHotels->wsSearchHotel);
        foreach($data->aHotels->wsSearchHotel as $hotel)
            if (in_array($hotel->iHotelCode, $ids)){
                $room = $hotel->Rooms->wsSearchHotelRoom;       // should be the only one

                $result[$hotel->iHotelCode] = array(
                      'roomTypeID' => $room->iRoomTypeCode
                    , 'real_price' => round($room->cPrice)
                    , 'base_price' => round($room->cFullPrice)
                    , 'available'  => $room->iAvailable
                    , 'pansion'    => [array_search($room->eBoardBase, $this->board_map) => round($room->cPrice)]
                );
            }

		return $result;
	}
	
	public function get_rooms($id, $date, $nights, $people = array(2,0,0), $pansion = 0){
		$req    = $this->prepare_request($date, $nights, $id, -1, $people, $pansion);
		$result = array();

        $res  = $this->client->AgencyChannels_SearchHotels(['wsRequest' => $req]);
        $data = $res->AgencyChannels_SearchHotelsResult;

        if (isset($data->Error) && $data->Error->iErrorId)
            throw new Exception($data->Error->sErrorDescription);

        $rooms = is_array($data->aHotels->wsSearchHotel->Rooms->wsSearchHotelRoom) ? $data->aHotels->wsSearchHotel->Rooms->wsSearchHotelRoom : [$data->aHotels->wsSearchHotel->Rooms->wsSearchHotelRoom];
        foreach($rooms as $room)
            $result[$room->iRoomTypeCode] = array(
                  'real_price' => round($room->cPrice)
                , 'base_price' => round($room->cFullPrice)
                , 'available'  => $room->iAvailable
                , 'pansion'    => [array_search($room->eBoardBase, $this->board_map) => round($room->cPrice)]
            );

		return $result;
	}
	
	public function get_room_price($siteID, $roomID, $date, $nights, $people, $pansion){
		$req = $this->prepare_request($date, $nights, $siteID, $roomID, $people, $pansion);

        $res  = $this->client->AgencyChannels_SearchHotels(['wsRequest' => $req]);
        $data = $res->AgencyChannels_SearchHotelsResult;

        if (isset($data->Error) && $data->Error->iErrorId)
            throw new Exception($data->Error->sErrorDescription);

        if (isset($data->aHotels->wsSearchHotel->Rooms))
            return round($data->aHotels->wsSearchHotel->Rooms->wsSearchHotelRoom->cPrice);
		return 0;
	}


	public function get_site_data(){
        ini_set('memory_limit', '320M');
        ini_set('pcre.backtrack_limit', '6000000');

        $req = [
            'Id_AgencyChannel' => $this->channel,
            'Token'   => [
                'sUsrName'     => $this->username,
                'sPwd'         => $this->password
            ]
        ];

        $res =  $this->client->AgencyChannels_HotelsInventory($req);
        $data = $res->AgencyChannels_HotelsInventoryResult;

        if (isset($data->Error) && $data->Error->iErrorId)
            throw new Exception($data->Error->sErrorDescription);

        $hotels = array();

        is_array($data->aHotels->wsHotelInventory) or $data->aHotels->wsHotelInventory = array($data->aHotels->wsHotelInventory);
        foreach($data->aHotels->wsHotelInventory as $ih => $hotel){
            $tmpH = array();

            is_array($hotel->RoomTypes->wsRoomTypeInventory) or $hotel->RoomTypes->wsRoomTypeInventory = array($hotel->RoomTypes->wsRoomTypeInventory);
            foreach($hotel->RoomTypes->wsRoomTypeInventory as $room){
                $dates = array();

                is_array($room->Lines->InventoryLine) or $room->Lines->InventoryLine = array($room->Lines->InventoryLine);
                foreach($room->Lines->InventoryLine as $date){
                    $min = 0;

                    if (is_array($date->Rates->wsInventoryLineRate))
                        foreach($date->Rates->wsInventoryLineRate as $rate)
                            $min = max($min, $rate->iMinNights, $rate->iMinNightsArrival);
                    else
                        $min = max($min, $date->Rates->wsInventoryLineRate->iMinNights, $date->Rates->wsInventoryLineRate->iMinNightsArrival);

                    if ($min > 1)
                        $dates[$date->dtDate->Year . '-' . self::zeropad($date->dtDate->Month) . '-' . self::zeropad($date->dtDate->Day)] = $min;
                }

                if (count($dates))
                    $tmpH[$room->iRoomTypeCode] = $dates;
            }

            if (count($tmpH))
                $hotels[$hotel->iHotelCode] = $tmpH;

            unset($data->aHotels->wsHotelInventory[$ih]);
        }

        return $hotels;
    }


	public function site_list($raw = false){
        $req = [
            'Token' => ['sUsrName' => $this->username, 'sPwd' => $this->password],
            'Id_AgencyChannel' => $this->channel
        ];
        $result = array();

        $res  = $this->client->AgencyChannels_HotelsList($req);
        $data = $res->AgencyChannels_HotelsListResult;

        if (isset($data->Error) && $data->Error->iErrorId)
            throw new Exception($data->Error->sErrorDescription);

        if ($raw)
            return $data->aHotels->wsHotelInfo;

        is_array($data->aHotels->wsHotelInfo) or $data->aHotels->wsHotelInfo = array($data->aHotels->wsHotelInfo);
        foreach($data->aHotels->wsHotelInfo as $hotel)
            $result[$hotel->iHotelCode] = $this->wsKeyValuePair($hotel->Name->wsKeyValuePair);

        return $result;
    }


    public function room_list($siteID, $withExtra = false){
        $list = $this->site_list(true);

        is_array($list) or $list = array($list);
        foreach($list as $hotel)
            if ($hotel->iHotelCode == $siteID){
                $result = [];

                $tmp = is_array($hotel->RoomTypes->wsHotelRoomInfo) ? $hotel->RoomTypes->wsHotelRoomInfo : array($hotel->RoomTypes->wsHotelRoomInfo);
                foreach($tmp as $room)
                    $result[$room->iRoomTypeCode] = $withExtra ? [
                        'name'       => $this->wsKeyValuePair($room->Name->wsKeyValuePair),
                        'maxTotal'   => $room->iMaxPersons,
                        'maxAdults'  => $room->iMaxAdults,
                        'maxKids'    => $room->iMaxChilds,
                        'maxInfants' => $room->iMaxInfants
                    ] : $this->wsKeyValuePair($room->Name->wsKeyValuePair);

                return $result;
            }

        return [];
    }


	public function create_booking($booking){
	    $pan_list = [0 => 'RO', 1 => 'BB', 2 => 'BB'];

	    $egOrderAuth = new eg_authToken;
        $egOrderAuth->sUsrName = $this->username;
        $egOrderAuth->sPwd     = $this->password;

        $egOrder = new eg_orderRequest;
        $egOrder->iChannelManager  = $this->channel;
        $egOrder->sPwd             = $this->password;
        $egOrder->bClient_IsTurist = 0;
        $egOrder->bPriceWithNoVat  = 0;
        $egOrder->BoardBase        = 'RO';
        $egOrder->sClient_Name     = $booking->clientInfo->name;
        $egOrder->sClient_Tel1     = $booking->clientInfo->phone;
        $egOrder->sClient_EMail    = $booking->clientInfo->email;
        $egOrder->sClient_Address  = '';
        $egOrder->sClient_Address2 = '';
        $egOrder->eClientLang      = 'He';
        $egOrder->Currency         = 'ILS';
        $egOrder->cPrice           = $booking->total;
        $egOrder->sRemark          = '';
        $egOrder->sPrivateRemark   = '';
        $egOrder->dtStart          = array_combine(['Year', 'Month', 'Day'], array_map('intval', explode('-', $booking->dateFrom)));
        $egOrder->iNights          = $booking->nights;
        $egOrder->iAgentId         = 13;     // ???
        $egOrder->sAgentName       = 'V for Vacation';
        $egOrder->Id_Hotel         = $booking->siteID;
        $egOrder->iRooms           = count($booking->rooms);
        $egOrder->aRooms           = [];

        foreach($booking->rooms as $room){
            $temp = new eg_orderRequestRoom;
            $temp->iSubItemId = $room->roomID;
            $temp->iAdults    = $room->pax->adults;
            $temp->iChilds    = $room->pax->kids;
            $temp->iInfants   = 0;
            $temp->BoardBase  = $pan_list[$room->pansion ?? 0];
            $temp->cUnitPrice = $room->basePrice;
            $temp->cPrice     = $room->roomTotal;

            $egOrder->aRooms[] = $temp;
        }

        $req = ['Token' => $egOrderAuth, 'wsRes' => $egOrder];
$this->log('Booking request: ');
$this->log($req);
        $res = $this->client->BookReservation($req);
$this->log('Booking response: ');
$this->log($res);
        $data = isset($res->BookReservationResult) ? $res->BookReservationResult : $this->parse($res->wsBookResult);

        if($data->iOrderId)
            return $data->iOrderId; // no error all good

        throw new Exception($data->sErrorDescription);
    }

    public function cancel_booking($siteID, $bookID){
        $egOrderAuth = new eg_authToken;
        $egOrderAuth->sUsrName = $this->username;
        $egOrderAuth->sPwd     = $this->password;

        $req = [
            'Token'           => $egOrderAuth,
            'iChannelManager' => $this->channel,
            'iHotelId'        => $siteID,
            'iChnlMgrOrderId' => $bookID
        ];
$this->log('Booking cancel request: ');
$this->log($req);

        $res = $this->client->CancelReservation($req);
$this->log('Booking cancel response: ');
$this->log($res);
        $data = isset($res->CancelReservationResult) ? $res->CancelReservationResult : $this->parse($res->wsBookResult);

        if($data->iOrderId)
            return $data->iOrderId; // no error all good

        throw new Exception($data->sErrorDescription);
    }
}
