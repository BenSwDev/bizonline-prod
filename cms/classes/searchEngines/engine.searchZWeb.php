<?php
include "class.zweb.php";

class searchZWeb extends zWeb {
	protected $board_map = array(1 => 'RO', 2 => 'BB', 3 => 'HB', 4 => 'FB');

    const DEFAULT_DOMAIN = 'hapisga.co.il';

	public function __construct(){
		parent::__construct();
	}
	
	public function get_sites($ids, $date, $nights, $people = array(2,0,0), $pansion = 0){
		$tmp = explode('-',$date);
		
		$res = new zw_roomReservation;
		
		$res->dateStart = $date;
		$res->dateEnd   = date('Y-m-d',mktime(10,0,0,intval($tmp[1]),intval($tmp[2]) + $nights, intval($tmp[0])));
		$res->nAdults   = $people[0];
		$res->nChildren = $people[1];
		$res->nInfants  = $people[2];
		$res->domain    = self::DEFAULT_DOMAIN;
		
		if (intval($pansion) > 0)
			$res->accomodation = $this->board_in($pansion);

		$data   = $this->getAvailableRoomTypes_many(array_map('intval',$ids), $res);
		$result = array();

		foreach($data as $obj)
			if (!isset($result[$obj->roomTypeLocationID]) || $result[$obj->roomTypeLocationID]['real_price'] > $obj->roomTypePrice){
				$pans = array();
				if (is_array($obj->roomTypeAccomodations))
					foreach($obj->roomTypeAccomodations as $acc)
						$pans[$this->board_out($acc->accomodation)] = round($acc->accomodationPrice / 100);

				$result[$obj->roomTypeLocationID] = array(
					  'roomTypeID' => $obj->roomTypeID
					, 'real_price' => $obj->roomTypePrice
					, 'base_price' => $obj->roomTypePriceBeforeDiscount
					, 'available'  => $obj->roomTypeRoomsCount /*- $obj->roomTypeByRequestRoomsCount*/ + ($result[$obj->roomTypeLocationID] ? $result[$obj->roomTypeLocationID]['available'] : 0)
					, 'pansion'    => $pans
				);
			} else
                $result[$obj->roomTypeLocationID]['available'] += $obj->roomTypeRoomsCount /*- $obj->roomTypeByRequestRoomsCount*/;
//file_put_contents(__DIR__.'/../../../logs/zweb.log','+-----------'.PHP_EOL.'Ids: '.implode(',',array_map('intval',$ids))."\nReq: ".print_r($res,true)."\n\nResponse: ".print_r($data,true)."\n\nResult: ".print_r($result,true)."\n\n",FILE_APPEND | LOCK_EX);
		return $result;
	}
	
	public function get_rooms($id, $date, $nights, $people = array(2,0,0), $pansion = -1){
		$tmp = explode('-',$date);
		
		$res = new zw_roomReservation;
		
		$res->dateStart = $date;
		$res->dateEnd   = date('Y-m-d',mktime(10,0,0,intval($tmp[1]),intval($tmp[2]) + $nights, intval($tmp[0])));
		$res->nAdults   = $people[0];
		$res->nChildren = $people[1];
		$res->nInfants  = $people[2];
		$res->domain    = self::DEFAULT_DOMAIN;
		
		if ($pansion > 0)
			$res->accomodation = $this->board_in($pansion);

		$data   = $this->getAvailableRoomTypes(intval($id), $res);
		$result = array();

		foreach($data as $obj) {
			$pans = array();
			if (is_array($obj->roomTypeAccomodations))
				foreach($obj->roomTypeAccomodations as $acc)
					$pans[$this->board_out($acc->accomodation)] = round($acc->accomodationPrice / 100);
			
			$result[$obj->roomTypeID] = array(
				  'real_price' => $obj->roomTypePrice
				, 'base_price' => $obj->roomTypePriceBeforeDiscount
				, 'available'  => $obj->roomTypeRoomsCount /*- $obj->roomTypeByRequestRoomsCount*/
				, 'pansion'    => $pans
			);
		}
//file_put_contents(__DIR__.'/../../../logs/zweb2.log','+-r----------'.PHP_EOL.'Ids: '.$id."\nReq: ".print_r($res,true)."\n\nResponse: ".print_r($data,true)."\n\nResult: ".print_r($result,true)."\n\n",FILE_APPEND | LOCK_EX);
		return $result;
	}
	
	public function get_room_price($siteID, $roomID, $date, $nights, $people, $pansion){
		$tmp = explode('-',$date);
		
		$res = new zw_roomReservation;
		
		$res->roomTypeID   = intval($roomID);
		$res->accomodation = $this->board_in($pansion);
		$res->dateStart    = $date;
		$res->dateEnd      = date('Y-m-d',mktime(10,0,0,intval($tmp[1]),intval($tmp[2]) + $nights, intval($tmp[0])));
		$res->nAdults      = $people[0];
		$res->nChildren    = $people[1];
		$res->nInfants     = $people[2];
		$res->domain       = self::DEFAULT_DOMAIN;

		$data = $this->getAvailableRoomTypes(intval($siteID), $res);

		foreach($data as $obj)
			if ($obj->roomTypeID == $roomID)
				return $obj->roomTypePrice;
//file_put_contents(__DIR__.'/../../../logs/zweb3.log','+-r----------'.PHP_EOL.'Ids: '.$id."\nReq: ".print_r($res,true)."\n\nResponse: ".print_r($data,true)."\n\nResult: ".print_r($data,true)."\n\n",FILE_APPEND | LOCK_EX);
		return 0;
	}
	
	public function site_list(){
		ini_set('memory_limit','64M');			// the $list will be REALLY big
		
		$list   = $this->getLocationsInfo();
		$result = array();
		
		foreach($list as $site)
			$result[$site->locationID] = $site->name;
			
		return $result;
	}
	
	public function room_list($siteID){
		$list   = $this->getLocationRoomTypes(intval($siteID));
		$result = array();
		
		foreach($list as $room)
			$result[$room->roomTypeID] = $room->roomTypeName;
			
		return $result;
	}
	
// converts board base from outside to inner value
	public function board_in($out){
		return isset($this->board_map[$out]) ? $this->board_map[$out] : $out;
	}
	
// converts board base from inner to outside value
	public function board_out($in){
		$tmp = array_search($in, $this->board_map);
		return ($tmp === false) ? $in : $tmp;
	}
}
