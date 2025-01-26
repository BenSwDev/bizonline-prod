<?php
namespace ProtelParser;

class ParsedClass {}

class WrongTypeException extends \Exception {}

class NamedArray extends \ArrayObject {};

class ProperParser
{
    public static function fromXML($xml){
        if (preg_match_all('~<([a-z0-9_:]+)( [^>]+)?((?<=/)>|(?<!/)>(.*)</\1>)~ismU',$xml,$match) && count($match[1])){
            $tmp = new ParsedClass;

            foreach($match[1] as $i => $key) {
                $content = $match[4][$i] ? self::fromXML($match[4][$i]) : null;
                if ($prm = trim($match[2][$i])){
                    $obj = new ParsedClass;

                    preg_match_all('/([a-z0-9_:]+)="([^"]+)"/iU', $prm, $patch); //if ($key == 'p:Action') {print_r($match[2][$i]); print_r($patch); exit;}
                    foreach($patch[1] as $j => $p){
                        if (substr($p, 0, 5) == 'xmlns')
                            continue;

                        $obj->{'_' . strtr($p, ':', '_')} = $patch[2][$j];
                    }

                    if (!count(get_object_vars($obj)))
                        unset($obj);
                }

                if (isset($obj)) {
                    if (is_object($content))
                        self::moveFields($content, $obj);
                    elseif ($content)
                        $obj->__value = $content;
                } else
                    $obj = $content;

                $key = strtr($key, ':', '_');

                if (isset($tmp->$key))
                    is_array($tmp->$key) ? $tmp->{$key}[] = $obj : $tmp->$key = array($tmp->$key, $obj);
                else
                    $tmp->$key = $obj;

                unset($obj);
            }
            return $tmp;
        }
        return $xml;
    }

    public static function moveFields($source, $target){
        foreach($source as $key => $value)
            $target->$key = $value;
    }

    public function inst($xmlArray){
        $class = get_class($this);

        if (is_object($xmlArray))       // casting object as array if needed
            $xmlArray = (array) $xmlArray;

        foreach($xmlArray as $key => $val){
            $name = ltrim($key, '_');

            if (method_exists($this, $name))
                $this->$name($val);
            elseif (property_exists($class, $key)){
                $className = '\\' . __NAMESPACE__ . '\\' . $key;
                $this->$key = ($val instanceof ParsedClass && class_exists($className) && is_subclass_of($className, __CLASS__)) ? (new $className)->inst($val) : $val;
            }
            elseif (property_exists($class, $name)){
                $className = '\\' . __NAMESPACE__ . '\\' . $name;
                $this->$name = ($val instanceof ParsedClass && class_exists($className) && is_subclass_of($className, __CLASS__)) ? (new $className)->inst($val) : $val;
            }
        }

        return $this;
    }

    public function make_class_array($data, $class){
        $className = '\\' . __NAMESPACE__ . '\\' . $class;

        if (!is_subclass_of($className, __CLASS__))
            return [];

        $d = self::real_array($data) ? $data : [$data];
        $list = [];


        foreach($d as $res){
            try {
                $list[] = (new $className)->inst($res);
            }
            catch (WrongTypeException $e){}
        }

        return $list;
    }

    public static function real_array($arr){
        if (!is_array($arr))
            return false;

        reset($arr);
        return (!$arr || is_int(key($arr)));
    }

    public function __toString(){
        $props = $atts = [];
        $self  = isset($this->_node_) ? $this->_node_ : get_class($this);

        foreach($this as $key => $val){
            if ($key == '_node_' || substr($key, 0, 2) == '__')
                continue;
            elseif ($key[0] == '_'){
                if ($val)
                    $atts[] = str_replace('__', ':', substr($key, 1)) . '="' . $val . '"';
            }
            elseif ($key == 'Success' && is_array($val) && !$val)
                $props[] = '<Success />';
            elseif (is_array($val) || is_a($val, 'NamedArray'))
                $props[] = $this->_collapseArray($val, $key);
            else
                $props[] = '' . $val;
        }

        return '<' . $self . (count($atts) ? ' ' . implode(' ', $atts) : '') . (count($props) ? '>' . implode('', $props) . '</' . $self . '>' : ' />');
    }

    protected function _collapseArray($arr, $name){
        $subs = [];

        foreach($arr as $key => $val)
            $subs[] = is_array($val) ? $this->_collapseArray($val, is_numeric($key) ? $name : $key) : '' . $val;

        return is_a($arr, 'NamedArray') ? '<' . $name . (count($subs) ? '>' . implode('', $subs) . '</' . $name . '>' : ' />') : implode('', $subs);
    }

    public static function parse_bool($val){
        return is_bool($val) ? $val : (is_scalar($val) ? !strcasecmp($val, 'true') : false);
    }
}


class OTA_HotelResNotifRQ extends ProperParser {
    public $_CorrelationID;
    public $_EchoToken;
    public $_ResStatus;
    public $_Version;

    public $POS;
    public $HotelReservations;

//    public function POS($data){
//        $this->POS = (new POS)->inst($data);
//    }

    public function HotelReservations($data){
        $this->HotelReservations = parent::make_class_array($data->HotelReservation, 'HotelReservation');
    }
}

class UniqueID extends ProperParser {
    public $_ID;
    public $_ID_Context;
    public $_Type;

    public function __construct($id = 0, $type = 0, $cont = 'protelIO'){
        $this->_ID   = $id;
        $this->_Type = $type;
        $this->_ID_Context = $cont;
    }
}

class RequestorID extends UniqueID {}

class Account extends UniqueID {}

class POS extends ProperParser {
    public $Source;

//    public function Source($data) {
//        $this->Source = (new Source)->inst($data);
//    }
}

class Source extends ProperParser {
    public $_xmlns;
    public $RequestorID;

//    public function RequestorID($data) {
//        $this->RequestorID = (new RequestorID)->inst($data);
//    }
}


class HotelReservation extends ProperParser {
    public $_CreateDateTime;
    public $_CreatorID;
    public $_LastModifierID;
    public $_LastModifyDateTime;
    public $_ResStatus;

    public $UniqueID;
    public $RoomStays;
    public $ResGuests;
    public $ResGlobalInfo;
    public $Services;

    public function UniqueID($data){
        $temp = parent::make_class_array($data, 'UniqueID');

        foreach($temp as $id)
            if ($id->_ID_Context == 'protelIO')
                $this->UniqueID = $id;
    }

    public function RoomStays($data){
        $this->RoomStays = parent::make_class_array($data->RoomStay, 'RoomStay');
    }

    public function Services($data){
        $this->Services = parent::make_class_array($data->Service, 'Service');
    }

//    public function ResGlobalInfo($data){
//        $this->ResGlobalInfo = (new ResGlobalInfo)->inst($data);
//    }

    public function ResGuests($data){
        $temp = self::real_array($data->ResGuest) ? $data->ResGuest : [$data->ResGuest];

        $main = $booker = null;
        $rest = [];

        foreach($temp as $guest){
            if (self::parse_bool($guest->_PrimaryIndicator) == true)
                $main = (new ResGuest)->inst($guest);
            elseif (($guest->TPA_Extensions->px_GuestStayExtensions->px_StayInfo->_Role ?? '') == 'Booker')
                $booker = (new ResGuest)->inst($guest);
            elseif (($guest->TPA_Extensions->px_GuestStayExtensions->px_StayInfo->_Role ?? '') == 'ArrivingGuest')
                $rest[] = (new ResGuest)->inst($guest);
        }

        //$this->ResGuests = parent::make_class_array($data->ResGuest, 'ResGuest');
        $this->ResGuests = array_merge($main ? [$main] : [], $booker ? [$booker] : [], $rest);
    }
}

class RoomStay extends ProperParser {
    public $TimeSpan;
    public $ResGuestRPHs = [];
    public $Rooms        = [];

//    public function TimeSpan($data){
//        $this->TimeSpan = (new TimeSpan)->inst($data);
//    }

    public function ResGuestRPHs($data){
        $d = self::real_array($data->ResGuestRPH) ? $data->ResGuestRPH : [$data->ResGuestRPH];
        foreach($d as $res)
            $this->ResGuestRPHs[] = intval($res->_RPH);
    }

    public function RoomTypes($data){
        $temp = self::real_array($data->RoomType) ? $data->RoomType : [$data->RoomType];
        foreach($temp as $room)
            $this->Rooms[] = (new RoomType)->inst($room);
    }
}

class RoomType extends ProperParser {
    public $_IsRoom;
    public $_RoomID;
    public $_RoomType;
    public $_RoomTypeCode;
}

class TimeSpan extends ProperParser {
    public $_Start;
    public $_End;
}

class ResGuest extends ProperParser {
    public $__Role;
    public $__isCompany;

    public $_PrimaryIndicator;
    public $_ResGuestRPH;
    public $UniqueID;
    public $Customer;

    public function TPA_Extensions($data){
        $this->__Role = $data->px_GuestStayExtensions->px_StayInfo->_Role ?? '';
    }

    public function PrimaryIndicator($val){
        $this->_PrimaryIndicator = self::parse_bool($val);
    }

    public function Profiles($data){
        $info = $data->ProfileInfo;

        if (is_array($info->UniqueID)){
            foreach($info->UniqueID as $uid){
                if ($uid->_ID_Context == 'protelIO')
                    $this->UniqueID = (new UniqueID)->inst($uid);
            }
        }
        else
            $this->UniqueID = (new UniqueID)->inst($info->UniqueID);

        if (isset($info->Profile->Customer)){
            $this->__isCompany = false;
            $this->Customer = (new Customer)->inst($info->Profile->Customer);
        }
        else {
            $this->__isCompany = true;
            $this->Customer = (new CompanyInfo)->inst($info->Profile->CompanyInfo);
        }
    }
}

class Customer extends ProperParser {
    public $BirthDate;
    public $Gender;
    public $Surname;
    public $MidName;
    public $Name;
    public $Telephone = [];
    public $Email     = [];
    public $Addresses = [];

    public function PersonName($data){
        if (is_array($data))
            $data = $data[0];

        $this->Name    = isset($data->GivenName) ? (is_array($data->GivenName) ? implode(' ', $data->GivenName) : $data->GivenName) : '';
        $this->MidName = isset($data->MiddleName) ? (is_array($data->MiddleName) ? implode(' ', $data->MiddleName) : $data->MiddleName) : '';
        $this->Surname = is_array($data->Surname) ? implode(' ', $data->Surname) : $data->Surname;
    }

    public function Telephone($data){
        if (is_array($data))
            foreach($data as $addr)
                $this->Telephone[] = (new Telephone)->inst($addr);
        else
            $this->Telephone[] = (new Telephone)->inst($data);
    }

    public function Email($data){
        if (is_array($data))
            foreach($data as $addr)
                $this->Email[] = (new Email)->inst($addr);
        else
            $this->Email[] = (new Email)->inst($data);
    }

    public function Address($data) {
        if (is_array($data))
            foreach($data as $addr)
                $this->Addresses[] = (new Address)->inst($addr);
        else
            $this->Addresses[] = (new Address)->inst($data);
    }
}

class CompanyInfo extends ProperParser {
    public $CompanyName;
    public $Telephone = [];
    public $Email     = [];
    public $Addresses = [];

    public function TelephoneInfo($data){
        if (is_array($data))
            foreach($data as $addr)
                $this->Telephone[] = (new Telephone)->inst($addr);
        else
            $this->Telephone[] = (new Telephone)->inst($data);
    }

    public function Email($data){
        if (is_array($data))
            foreach($data as $addr)
                $this->Email[] = (new Email)->inst($addr);
        else
            $this->Email[] = (new Email)->inst($data);
    }

    public function AddressInfo($data) {
        if (is_array($data))
            foreach($data as $addr)
                $this->Addresses[] = (new Address)->inst($addr);
        else
            $this->Addresses[] = (new Address)->inst($data);
    }
}

class Address extends ProperParser {
    public $_DefaultInd = false;
    public $_Type;

    public $Address     = '';
    public $CityName    = '';
    public $PostalCode  = '';
    public $Country     = '';

    public function Type($data){
        $types = [1 => 'home', 2 => 'business', 3 => 'other'];
        $this->_Type = $types[$data] ?? $data;
    }

    public function AddressLine($data) {
        $this->Address = is_array($data) ? implode(', ', $data) : $data;
    }

    public function CountryName($data) {
        $this->Country = ($data instanceof ParsedClass) ? ($data->__value ?? $data) : $data;
    }
}

class Email extends ProperParser {
    public $_DefaultInd = false;
    public $_Type;
    public $_RPH;
}

class Telephone extends ProperParser {
    public $_DefaultInd = false;
    public $_Type;
    public $_PhoneLocationType;     // complement $_Type for companies (???)
    public $_PhoneNumber;
    public $_RPH;
}


class ResGlobalInfo extends ProperParser {
    public $HotelReservationIDs;

//    public function HotelReservationIDs($data){
//        $this->HotelReservationIDs = (new HotelReservationIDs)->inst($data);
//    }

    public function change_status($status){
        $list = ['Commit' => 'Committed', 'Modify' => 'Modified', 'Cancel' => 'Cancelled'];
        $this->HotelReservationIDs->_ResResponseType = $list[$status] ?: 'Committed';
    }
}

class HotelReservationIDs extends ProperParser {
    public $_ResResponseType = 'Committed';

    public $HotelReservationID;

    public function HotelReservationID($data){
        $this->HotelReservationID = parent::make_class_array($data, 'HotelReservationID');
    }
}

class HotelReservationID extends ProperParser {
    public $_ForGuest;
    public $_ResID_Source;
    public $_ResID_Type;
    public $_ResID_Value;
}


class Service extends ProperParser {
    public $_ID;
    public $_ID_Context;
    public $_Type;
    public $_Inclusive;
    public $_Quantity;
    public $_RatePlanCode;
    public $_RequestedIndicator;
    public $_ServiceInventoryCode;
    public $_ServicePricingType;

    public $Prices;      // array
    public $ServiceDetails;

    public function Price($data){
        $this->Prices = parent::make_class_array($data, 'ServicePrice');
    }

    public function ServiceDetails($data){
        $this->ServiceDetails = (new ServiceDetails)->inst($data);
    }
}

class ServicePrice extends ProperParser {
    public $_EffectiveDate;
    public $_ExpireDate;
    public $_NumberOfUnits;
    public $_RateTimeUnit;
    public $_UnitMultiplier;

    public $Base;
    public $Total;
    public $RateDescription;

    public function Base($data){
        $this->Base = (new GenericPrice)->inst($data);
    }

    public function Total($data){
        $this->Total = (new GenericPrice)->inst($data);
    }

    public function RateDescription($data){
        $rateDesc = [];

        $d = self::real_array($data) ? $data : [$data];
        foreach($d as $desc){
            $t = self::real_array($desc->Text) ? $desc->Text : [$desc->Text];
            foreach($t as $text)
                $rateDesc[] = ($text instanceof ParsedClass) ? $text->__value : $text;
        }

        $this->RateDescription = implode(PHP_EOL, $rateDesc);
    }
}

class ServiceDetails extends ProperParser {
    public $TimeSpan;
    public $Comments;
    public $Total;

    public function TimeSpan($data){
        $this->TimeSpan = (new TimeSpan)->inst($data);
    }

    public function Comments($data){
        $comms = [];

        $d = self::real_array($data->Comment) ? $data->Comment : [$data->Comment];
        foreach($d as $comm){
            $t = self::real_array($comm->Text) ? $comm->Text : [$comm->Text];
            foreach($t as $text)
                $comms[] = ($text instanceof ParsedClass) ? $text->__value : $text;
        }

        $this->Comments = implode(PHP_EOL, $comms);
    }

    public function Total($data){
        $this->Total = (new GenericPrice)->inst($data);
    }
}


class GenericPrice extends ProperParser {
    public $_AmountAfterTax;
    public $_AmountBeforeTax;
    public $_CurrencyCode;
}
