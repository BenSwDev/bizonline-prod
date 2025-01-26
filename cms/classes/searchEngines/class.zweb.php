<?php
require_once "xmlrpc/lib/xmlrpc.inc";
require_once "xmlrpc/lib/xmlrpc_wrappers.inc";

$GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';

define('DEFAULT_ZWEB_LINK','https://checkin.olr.co.il/RPC2');
define('DEFAULT_ZWEB_BASE','https://checkin.olr.co.il/');

class zw_basic {
	function __construct($item = null, $_map = array())
	{
		$cn = get_class($this);
		if ($item && is_array($item))
			foreach($item as $key => $val)
				if (property_exists($cn,$key)){
					if ($val && isset($_map[$key])){
						if (is_string($_map[$key]))
							$this->$key = new $_map[$key]($val);
						elseif (is_array($_map[$key]) && is_array($val)){
							$_tmp = array();
							foreach($val as $_i => $_t)
								$_tmp[] = new $_map[$key][0]($_t);
							$this->$key = $_tmp;
						}
					} else
						$this->$key = $val;
				}
	}
}

class zw_Client extends zw_basic {
	public $mobile;
	public $phone;
	public $email;
	public $personalID;
	public $firstName;
	public $lastName;
}

class zw_ccDetails extends zw_basic {
	public $ccName;
	public $ccNumber;
	public $expirationMonth;
	public $expirationYear;
	public $cvv;
	public $paid;
	public $transactionRef;
	public $promoCode;
	public $paymentComments;
	public $clerkCode;
}

class zw_reserveResult extends zw_basic {
	public $success;
	public $errorCode;
	public $errorMessage;
}

class zw_locationMessage extends zw_basic {
	public $field;
	public $he;
	public $en;
	public $ru;
}

class zw_locationInfo extends zw_basic {
	public $locationID;
	public $name;
	public $shortName;
	public $areaID;
	public $regionID;
	public $settlementID;
	public $description;
	public $promoText;
	public $messages;		// zw_locationMessage;
	
	function __construct($item = null){
		parent::__construct($item,array('messages' => array('zw_locationMessage')));
	}
}

class zw_roomReservation extends zw_basic {
	public $roomTypeID;
	public $dateStart;
	public $dateEnd;
	public $nAdults;
	public $nChildren;
	public $nInfants;
	public $roomTypePriceListID;
	public $roomPrice;
	public $accomodation;
	public $origin;
	public $domain;
	public $comments;
	public $addons;
	public $voucherTypeID;
	public $voucherNumber;
	
	public function __construct($item = null){
		parent::__construct($item);

		$this->origin = '';         // here goes origin from checkIn
	}
}

class zw_roomTypeInfo extends zw_basic {
	public $roomTypeID;
	public $roomTypeName;
	public $roomTypeLocationID;
	public $roomTypeDescription;
	public $roomTypeImageURL;
	public $roomTypePriceListID;
	public $roomTypePrice;
	public $roomTypePriceBeforeDiscount;
	public $roomTypeRoomsCount;
	public $roomTypeByRequestRoomsCount;
	public $roomTypeAccomodations;
	
	function __construct($item = null){
		parent::__construct($item,array('roomTypeAccomodations' => array('zw_accomodationPriceInfo')));
	}
}

class zw_accomodationPriceInfo extends zw_basic {
	public $accomodation;							// string
	public $accomodationPrice;						// double
	public $accomodationPriceBeforeDiscount;		// double
}

class zw_roomTypeProperties extends zw_basic {
	public $roomTypeID;
	public $roomTypeName;
	public $roomTypeDescription;
	public $roomTypeImageURL;
	public $roomTypeCapacityAdults;
	public $roomTypeCapacityChildren;
	public $roomTypeCapacityInfants;
	public $roomTypeMaxCapacity;
	public $roomTypeMinPeople;
}

class zw_voucherTypeInfo extends zw_basic {
	public $voucherTypeID;
	public $voucherTypeName;
}

class zw_addonInfo extends zw_basic {
	public $addonID;
	public $description;
	public $basePrice;
	public $pricingType;
}

class zw_roomTypeShortInfo extends zw_basic {
	public $roomTypeID;
	public $roomTypeName;
	public $roomTypeEnglishName;
}

class zw_areaInfo extends zw_basic {
	public $areaID;
	public $areaName;
}

class zw_regionInfo extends zw_basic {
	public $regionID;
	public $areaID;
	public $regionName;
}

class zw_cityInfo extends zw_basic {
	public $cityID;
	public $regionID;
	public $cityName;
}

class zw_specialInfo extends zw_basic {
	public $specialID;
	public $locationID;
	public $dateStart;
	public $dateEnd;
	public $pricelistID;
	public $name;
	public $description;
	public $specialType;
}

class zw_locationPropertyInfo extends zw_basic {
	public $propertyID;
	public $he;
	public $en;
	public $ru;
}

class zw_checkReviewData extends zw_basic {
	public $locationID;
	public $reservationID;
	public $dateStart;
	public $zCode;
}

class zw_clerkInfo extends zw_basic {
	public $clerkID;
	public $clerkName;
	public $clerkPassword;
}

class zw_attractionInfo extends zw_basic {
	public $id;
	public $settlementID;
	public $name;
	public $address;
	public $phone;
	public $fax;
	public $email;
	public $comments;
}

class zw_restaurantInfo extends zw_basic {
	public $id;
	public $settlementID;
	public $name;
	public $address;
	public $phone;
	public $fax;
	public $email;
	public $comments;
}



class zWeb extends xmlrpc_client {
	private $data_map;
	
	function __construct($url = DEFAULT_ZWEB_LINK)
	{
		parent::__construct($url);
		
		//$this->request_charset_encoding = 'UTF-8';
		$this->data_map = array(
			  'getAvailableRoomTypes'      => array('int','zw_roomReservation')
			, 'getAvailableRoomTypes_many' => array(array('int'),'zw_roomReservation')
			, 'getAvailableRoomTypes_all'  => array('zw_roomReservation')
			, 'reserve'                    => array('int',array('zw_roomReservation'),'zw_Client','zw_ccDetails')
			, 'getRoomTypeProperties'      => array('int','int')
			, 'getVoucherTypes'            => array('int')
			, 'getLocationAddons'          => array('int','zw_roomReservation')
			, 'getLocationRoomTypes'       => array('int')
			, 'getSpecialsForAllLocations' => array('string')
			, 'checkReviewData'            => array('int', 'int', 'string', 'int')
		);
	}
	
	private function checkMap($method, $args)
	{
		if (isset($this->data_map[$method])){
			if (count($args) != count($this->data_map[$method]))
				return 1;
			
			foreach($this->data_map[$method] as $ind => $type){
				switch($type){
					case 'string': break;
					case 'int'   : if (!is_int($args[$ind])) return 2; break;
					case 'float' : if (!is_float($args[$ind])) return 2; break;
					case 'bool'  : if (!is_bool($args[$ind])) return 2; break;
					case 'date'  : if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/i',$args[$ind])) return 2; break;
					default      :
						if (is_array($type)){
							if (is_array($args[$ind])){
								foreach($args[$ind] as $v)
									switch($type[0]){
										case 'string': break;
										case 'int'   : if (!is_int($v)) return 2; break;
										case 'float' : if (!is_float($v)) return 2; break;
										case 'bool'  : if (!is_bool($v)) return 2; break;
										case 'date'  : if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/i',$v)) return 2; break;
										default      : if (!is_object($v) || strcmp(get_class($v),$type[0])) return 2; break;
									}
							} else
								return 2;
						}
						elseif (!is_object($args[$ind]) || strcmp(get_class($args[$ind]),$type))
							return 2;
				}
			}
		}
		
		return 0;
	}
	
	private function prepare_message($method,$args,$call)
	{
		if ($err = $this->checkMap($method,$args))
			throw new Exception('Wrong parameter types for calling "'.$method.'":'.$err);

		$prm = array();
		foreach($args as $a)
			$prm[] = php_xmlrpc_encode($a);
		
		return new xmlrpcmsg($call,$prm);
	}
	
	private function get_obj($data, $className)
	{
		$tmp = php_xmlrpc_decode($data);
		if (is_array($tmp)){
			if (is_numeric(key($tmp))) {
				$result = array();
				foreach($tmp as $i)
					$result[] = new $className($i);
				return $result;
			} elseif (count($tmp))
				return new $className($tmp);
			return array();
		}
		return $tmp;
	}
	
	public function getLocationsInfo()
	{
		$msg = new xmlrpcmsg('zdirect2.getLocationsInfo',array());
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_locationInfo');
	}
	
	public function getAvailableRoomTypes($locationID, $reservation)
	{
		$msg = $this->prepare_message(__FUNCTION__, func_get_args(), 'zdirect2.getAvailableRoomTypes');
		$res = $this->send($msg);
//file_put_contents(__DIR__.'/../../../logs/zraw2.log','+-r----------'.PHP_EOL."\nReq: ".print_r($msg,true)."\n\nResponse: ".print_r($res,true)."\n\n",FILE_APPEND | LOCK_EX);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_roomTypeInfo');
	}
	
	public function getAvailableRoomTypes_many($locations, $reservation)
	{
		$msg = $this->prepare_message(__FUNCTION__, func_get_args(), 'zdirect2.getAvailableRoomTypesForLocations');
		$res = $this->send($msg);

		if ($res->faultCode())
			throw new Exception($res->faultString());

		$tmp = $this->get_obj($res->val,'zw_roomTypeInfo');
		return $tmp;
	}
	
	public function getAvailableRoomTypes_all($reservation)
	{
		$msg = $this->prepare_message(__FUNCTION__, func_get_args(), 'zdirect2.getAvailableRoomTypesForAllLocations');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_roomTypeInfo');
	}
	
	public function reserve($locationID, $reservationDetails, $client, $ccData)
	{
		$msg = $this->prepare_message(__FUNCTION__, func_get_args(), 'zdirect2.reserve');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_reserveResult');
	}
	
	public function getRoomTypeProperties($locationID, $roomTypeID)
	{
		$msg = $this->prepare_message(__FUNCTION__, func_get_args(), 'zdirect2.getRoomTypeProperties');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_roomTypeProperties');
	}
	
	public function getVoucherTypes($locationID)
	{
		$msg = $this->prepare_message(__FUNCTION__, func_get_args(), 'zdirect2.getVoucherTypes');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_voucherTypeInfo');
	}
	
	public function getLocationAddons($locationID, $roomReservation)
	{
		$msg = $this->prepare_message(__FUNCTION__, func_get_args(), 'zdirect2.getLocationAddons');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_addonInfo');
	}
	
	public function getLocationRoomTypes($locationID)
	{
		$msg = $this->prepare_message(__FUNCTION__, func_get_args(), 'zdirect2.getLocationRoomTypes');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_roomTypeShortInfo');
	}
	
	public function getAreas()
	{
		$msg = $this->prepare_message(__FUNCTION__, array(), 'zdirect2.getAreas');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_areaInfo');
	}
	
	public function getRegions()
	{
		$msg = $this->prepare_message(__FUNCTION__, array(), 'zdirect2.getRegions');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_regionInfo');
	}
	
	public function getCities()
	{
		$msg = $this->prepare_message(__FUNCTION__, array(), 'zdirect2.getCities');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_cityInfo');
	}
	
	public function getSpecialsForAllLocations($origin)
	{
		$msg = $this->prepare_message(__FUNCTION__, func_get_args(), 'zdirect2.getSpecialsForAllLocations');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_specialInfo');
	}
	
	public function getLocationPropertiesInfo()
	{
		$msg = $this->prepare_message(__FUNCTION__, func_get_args(), 'zdirect2.getLocationPropertiesInfo');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_locationPropertyInfo');
	}

	public function checkReviewData($locationID, $reservationID, $dateStart, $zCode)
	{
		$msg = $this->prepare_message(__FUNCTION__, func_get_args(), 'zdirect2.checkReviewData');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_checkReviewData');
	}
	
	public function getClerks()
	{
		$msg = $this->prepare_message(__FUNCTION__, array(), 'zdirect2.getClerks');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_clerkInfo');
	}
	
	public function getAttractions()
	{
		$msg = $this->prepare_message(__FUNCTION__, array(), 'zdirect2.getAttractions');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_attractionInfo');
	}

	public function getRestaurants()
	{
		$msg = $this->prepare_message(__FUNCTION__, array(), 'zdirect2.getRestaurants');
		$res = $this->send($msg);
		if ($res->faultCode())
			throw new Exception($res->faultString());

		return $this->get_obj($res->val,'zw_restaurantInfo');
	}
}
