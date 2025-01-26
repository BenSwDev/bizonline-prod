<?php
class eg_searchRequest {
	public $Id_AgencyChannel;     // int
	public $sPwd;                 // string
	public $Date_Start_yyyymmdd;  // string
	public $iNights;              // int
	public $Id_Agency;            // int
	public $ID_Region;            // int
	public $Id_Hotel;             // int
	public $iRoomTypeCode;        // int (0 – the cheapest result, -1 all combination)
	public $iBoardBaseCode;       // int (0 – the cheapest result, -1 all combination, Board Base: 1-R/O, 2-BB, 3-HB, 4-FB)
	public $iDomesticIncoming;    // int
	public $iAdults;              // int
	public $iChilds;              // int
	public $iInfants;             // int
	public $iCurrency_0NIS_1UDS;  // int
	public $bDailyPrice;          // bool (prices per night)
}

class eg_authRequest {
	public $Id_AgencyChannel;     // int
	public $sPwd;                 // string
}

class eg_authToken {
    public $sUsrName;     // string
    public $sPwd;                 // string
}

class eg_orderRequest {
    public $iChannelManager;		// int
    public $sPwd;                	 // string
    public $bClient_IsTurist;		// boolean
    public $bPriceWithNoVat;        // boolean
    public $BoardBase;              // int Board Base: 0-R/O, 1-BB, 2-HB, 3-FB
    public $sClient_Name;		 	 //string
    public $sClient_Tel1;			//string
    public $sClient_EMail;			//string
    public $sClient_Address;		//string
    public $sClient_Address2; 		//string
    public $eClientLang;				//string He En
    public $Currency;				// string ILS or USD
    public $cPrice;					// decimal full order price
    public $sRemark;				//string clients comment  - clients can see comment
    public $sPrivateRemark;			// string priavte comments not seen by client
    public $dtStart;  			  // Day, Month, Year
    public $iNights;              // int
    public $iAgentId;            // int
    public $sAgentName;			//string
    public $Id_Hotel;             // int
    public $iRooms;				  // int rooms count
	public $aRooms; 			  // array of rooms
}

class eg_orderRequestRoom {
    public $iSubItemId;	//int
    public $iAdults;	// int
    public $iChilds;	// int
    public $iInfants;	// int
	public $BoardBase;	// int Board Base: 0-R/O, 1-BB, 2-HB, 3-FB
    public $cUnitPrice; // decimal
	public $cPrice;	//decimal
}


class easyGo extends baseEngine {
	protected $wsdl     = 'http://www.onlineres.ezgo.co.il/service.asmx?wsdl';
	protected $client   = null;
	
	protected $channel  = 23;        // channel ID
	protected $password = 'mkesdjiv743jsaxs';       // channel password
    protected $username = 'SpaPlusVillas';
	
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
	
	protected function log($text){
		static $first = true;
		file_put_contents(__DIR__.'/../../../logs/easy.log',($first ? "\n------------------------\n".date('Y-m-d H:i:s')."\n" : '').print_r($text,true), FILE_APPEND | LOCK_EX);
		$first = false;
	}
	
	protected function prepare_request($date, $nights, $siteID = 0, $roomID = 0, $people = array(2,0,0), $pansion = 0){
		$req = new eg_searchRequest;
		$req->Id_AgencyChannel     = $this->channel;
		$req->sPwd                 = $this->password;
		$req->Date_Start_yyyymmdd  = $date;
		$req->iNights              = intval($nights);
		$req->Id_Agency            = 0;
		$req->ID_Region            = 0;
		$req->Id_Hotel             = $siteID;
		$req->iRoomTypeCode        = $roomID;
		$req->iBoardBaseCode       = intval($pansion);
		$req->iDomesticIncoming    = 0;
		$req->iAdults              = $people[0];
		$req->iChilds              = $people[1];
		$req->iInfants             = $people[2];
		$req->iCurrency_0NIS_1UDS  = 0;
		$req->bDailyPrice          = 0;

		return $req;
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
		$this->board_map = array(1 => 'RO', 2 => 'BB', 3 => 'HB', 4 => 'FB');
		$this->client    = new SoapClient($this->wsdl, array('encoding'=>'UTF-8', 'cache_wsdl' => WSDL_CACHE_BOTH, 'keep_alive' => true, 'trace' => true));
	}
		
	public function get_sites($ids, $date, $nights, $people = array(2,0,0), $pansion = 0){
		$req    = $this->prepare_request($date, $nights, 0, -1, $people, $pansion);     // set roomID = 0 for MIN price
		$result = array();

		try {
			$res  = $this->client->xmlAgencyChannels_SearchHotels($req);
			$data = $this->parse($res->xmlAgencyChannels_SearchHotelsResult);

			if (!$data->Response->Error->ErrorCode && isset($data->Response->Results)){
				is_array($data->Response->Results->Hotel) or $data->Response->Results->Hotel = array($data->Response->Results->Hotel);
				foreach($data->Response->Results->Hotel as $hotel)
					if (in_array($hotel->iHotelCode, $ids)){
						$tmp   = array();
                        $avail = 0;

						is_array($hotel->Room) or $hotel->Room = array($hotel->Room);
						foreach($hotel->Room as $room){
							$tmp[$this->board_out($room->BoardBaseCode)] = floatval($room->cTotalPrice);
                            $avail += $room->Available;
                        }
						
						$result[$hotel->iHotelCode] = array(
							  'roomTypeID' => $hotel->Room[0]->RoomTypeCode
							, 'real_price' => round(min($tmp))
							, 'base_price' => round(min($tmp))
							, 'available'  => $avail
							, 'pansion'    => $tmp
						);
					}
			}
//$this->log($req); $this->log($data);
		}
		catch (Exception $e){
		}
		
		return $result;
	}
	
	public function get_rooms($id, $date, $nights, $people = array(2,0,0), $pansion = -1){
		$req    = $this->prepare_request($date, $nights, $id, -1, $people, $pansion);
		$result = array();
error_reporting(0);
		try {
			$res  = $this->client->xmlAgencyChannels_SearchHotels($req);
			$data = $this->parse($res->xmlAgencyChannels_SearchHotelsResult);

			if (!$data->Response->Error->ErrorCode && isset($data->Response->Results)){
				is_array($data->Response->Results->Hotel->Room) or $data->Response->Results->Hotel->Room = array($data->Response->Results->Hotel->Room);
				foreach($data->Response->Results->Hotel->Room as $room){
					$price = round($room->cTotalPrice);
					
					if (isset($result[$room->RoomTypeCode])){
						$result[$room->RoomTypeCode]['pansion'][$this->board_out($room->BoardBaseCode)] = $price;
						if ($price < $result[$room->RoomTypeCode]['real_price'])
							$result[$room->RoomTypeCode]['real_price'] = $result[$room->RoomTypeCode]['base_price'] = $price;
					} 
					else
						$result[$room->RoomTypeCode] = array(
							  'real_price' => $price
							, 'base_price' => $price
							, 'available'  => $room->Available
							, 'pansion'    => array($this->board_out($room->BoardBaseCode) => $price)
						);
				}
			}

		}
		catch (Exception $e){
		}
//$this->log($result);
		return $result;
	}
	
	public function get_room_price($siteID, $roomID, $date, $nights, $people, $pansion){
		$req = $this->prepare_request($date, $nights, $siteID, $roomID, $people, $pansion);

		try {
			$res  = $this->client->xmlAgencyChannels_SearchHotels($req);
			$data = $this->parse($res->xmlAgencyChannels_SearchHotelsResult);

			if (!$data->Response->Error->ErrorCode && isset($data->Response->Results))
				return round($data->Response->Results->Hotel->Room->cTotalPrice);
		}
		catch (Exception $e){
		}

		return 0;
	}
	
	public function site_list($raw = false){
        $req = new eg_authRequest;
        $req->Id_AgencyChannel = $this->channel;
        $req->sPwd             = $this->password;

        $result = array();

        try {
            $res  = $this->client->xmlAgencyChannels_HotelsList($req);
            $data = $this->parse($res->xmlAgencyChannels_HotelsListResult);

            if (!$data->Response->Error->ErrorCode && isset($data->Response->Results)){
                if ($raw)
                    return $data->Response->Results->Hotel;

                is_array($data->Response->Results->Hotel) or $data->Response->Results->Hotel = array($data->Response->Results->Hotel);
                foreach($data->Response->Results->Hotel as $hotel)
                    $result[$hotel->iHotelCode] = $hotel->sHotelName;
            }
        }
        catch (Exception $e){
        }

        return $result;
	}


	public function site_list_new($raw = false){
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


	public function room_list($siteID){
		$list   = $this->site_list(true);
		$result = array();

		is_array($list) or $list = array($list);
		foreach($list as $hotel)
			if ($hotel->iHotelCode == $siteID){
				$tmp = is_array($hotel->Room) ? $hotel->Room : array($hotel->Room);
				foreach($tmp as $room)
					$result[$room->RoomTypeCode] = $room->RoomTypeName;
				break;
			}
			
		return $result;
	}


    public function room_list_new($siteID){
        $list = $this->site_list_new(true);

        is_array($list) or $list = array($list);
        foreach($list as $hotel)
            if ($hotel->iHotelCode == $siteID){
                $result = [];

                $tmp = is_array($hotel->RoomTypes->wsHotelRoomInfo) ? $hotel->RoomTypes->wsHotelRoomInfo : array($hotel->RoomTypes->wsHotelRoomInfo);
                foreach($tmp as $room)
                    $result[$room->iRoomTypeCode] = $this->wsKeyValuePair($room->Name->wsKeyValuePair);

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
        $egOrder->sAgentName       = 'הפיסגה';
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
