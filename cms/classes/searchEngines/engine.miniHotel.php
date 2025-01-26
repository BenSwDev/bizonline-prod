<?php
class attributes {
	public function __construct($list = array()){
		foreach($list as $key => $val)
			$this->$key = $val;
	}
}

class miniHotel extends baseEngine {
	protected $gateway  = 'https://api.minihotelpms.com/GDS';
	protected $username = '';
	protected $password = '';
	
	protected $curl     = null;
	protected $debug    = 0;
	protected $db_fpath = '../../../logs/minih_debug.txt';
	
	public function __construct(){
		$this->db_fpath = rtrim(__DIR__,'/').'/'.$this->db_fpath;
		
		$this->curl = curl_init($this->gateway);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: test/xml'));
		
		$this->board_map = array(-1 => '*ALL*', 0 => '*MIN*', 1 => 'RO', 2 => 'BB', 3 => 'HB', 4 => 'FB');
	}
	
	public function __destruct(){
		curl_close($this->curl);
	}
	
	protected function runDebug($header, $data){
	    if ($this->debug){
            $message = '------------------ '.$header.'------------------'.PHP_EOL.$data.PHP_EOL.'------------------ END OF '.$header.'------------------'.PHP_EOL;

            if ($this->debug & 1)
                echo $message;
            if ($this->debug & 2)
                file_put_contents($this->db_fpath, $message, FILE_APPEND | LOCK_EX);
        }
	}
	
	// hack to check if node is an object or an array
	protected function toArray($node)
	{
		switch(count($node)){
			case 0 : return array();
			case 1 : return array($node);
			default: return $node;
		}
	}
	
	protected function toXML($params, $parent = ''){
		$list = array();
		foreach($params as $key => $val){
			if (is_numeric($key))
				$key = $parent;
			
			if (is_a($val,'attributes')){
				$tmp = array();
				foreach($val as $pn => $pv)
					if (strcmp($pn,'_cdata'))
						$tmp[] = $pn.'="'.str_replace('"','\"',$pv).'"';
						
				$list[] = '<'.$key.' '.implode(' ',$tmp).(isset($val->_cdata) ? '>'.$val->_cdata.'</'.$key.'>' : ' />');
			} 
			elseif (is_array($val))
				$list[] = is_numeric(key($val)) ? $this->toXML($val,$key) : '<'.$key.'>'.$this->toXML($val).'</'.$key.'>';
			else
				$list[] = '<'.$key.'>'.$val.'</'.$key.'>';
		}
		
		return implode(PHP_EOL,$list);
	}
	
	protected function request($data){
		$xml = '
<?xml version="1.0" encoding="UTF-8" ?>
<AvailRaterq xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<Authentication username="'.$this->username.'" password="'.$this->password.'" />
'.$this->toXML($data).'
</AvailRaterq>';
		
		return trim($xml);
	}
	
	protected function send($query, $cookies = '', $raw = false)
	{
		$this->runDebug('REQUEST', $query);
		$this->runDebug('COOKIES',print_r($cookies,true));
		
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $query);
		
		if (is_array($cookies) && count($cookies)){
			$tmp = array();
			foreach($cookies as $k => $c)
				$tmp[] = is_numeric($k) ? $c : $k.'='.$c;
			curl_setopt($this->curl, CURLOPT_COOKIE, implode('; ',$tmp));
		} else
			curl_setopt($this->curl, CURLOPT_COOKIE, $cookies);

		$page = curl_exec($this->curl);

		$this->runDebug('RESPONSE', $page);
		
		if (strpos($page,'<AvailRaters>') === false)
			throw new Exception('Undefined response: '.$page);
		
		return $raw ? $page : simplexml_load_string($page, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR | LIBXML_NOWARNING);
	}
	
	public function std_call($siteID, $roomID, $date, $nights, $people = array(2,0,0), $pansion = '*MIN*'){
		$tmp = explode('-',$date);
		
		$result  = array();
		$request = array(
			  'Hotel'          => new attributes(array('id' => 0, 'Currency' => 'ILS'))
			, 'DateRange'      => new attributes(array('from' => $date, 'to' => date('Y-m-d',mktime(10,0,0,intval($tmp[1]),intval($tmp[2]) + $nights, intval($tmp[0])))))
			, 'Guests'         => new attributes(array_combine(array('adults','child','babies'),$people))
			, 'RoomTypes'      => array(
				'RoomType'     => new attributes(array('id' => $roomID))
			)
			, 'Prices'         => new attributes(array('rateCode' => '*ALL', '_cdata' => '<Price boardCode="'.$pansion.'" />'))
		);

		is_array($siteID) or $siteID = array($siteID);
		foreach($siteID as $id){
			$tmp = explode('#',$id);
			
			try {
				$request['Hotel'] ->id       = $tmp[0];
				$request['Prices']->rateCode = $tmp[1] ? $tmp[1] : '*ALL';
				
				$raw = $this->send($this->request($request));

				if (count($raw->RoomType))
					$result[] = $raw;
			}
			catch (Exception $e) {
			}
		}
		
		return $result;
	}
	
	public function get_sites($ids, $date, $nights, $people = array(2,0,0), $pansion = 0){
		$list   = $this->std_call($ids, '*ALL*', $date, $nights, $people, $this->board_in($pansion)); // *MIN*

		$result = array();

		foreach($list as $hotel){
			$tmp = array();
            $hid = (string) $hotel->Hotel->attributes()->id;

			foreach($this->toArray($hotel->RoomType->price) as $price){
				$p = (int) $price->attributes()->value;
				$tmp[$this->board_out((string) $price->attributes()->board)] = $p;
			}
			$min = min($tmp);

            if ($result[$hid] && $min >= $result[$hid]['real_price']){
                $result[$hid]['available'] += (int) $hotel->RoomType->Inventory->attributes()->maxavail;
            } else {
                $result[$hid] = array(
                      'roomTypeID' => (string) $hotel->RoomType->attributes()->id
                    , 'real_price' => $min
                    , 'base_price' => $min
                    , 'available'  => (int) $hotel->RoomType->Inventory->attributes()->maxavail + ($result[$hid] ? $result[$hid]['available'] : 0)
                    , 'pansion'    => $tmp
                );
            }
		}
		
		return $result;
	}
	
	public function get_rooms($id, $date, $nights, $people = array(2,0,0), $pansion = -1){
		list($hotel) = $this->std_call($id, '*ALL*', $date, $nights, $people, $this->board_in($pansion));
		
		$result = array();
		
		if ($hotel){
			foreach($this->toArray($hotel->RoomType) as $room){
				$tmp = array();
				foreach($this->toArray($room->price) as $price){
					$p = (int) $price->attributes()->value;
					$tmp[$this->board_out((string) $price->attributes()->board)] = $p;
				}
				$min = min($tmp);
				
				$result[(string) $room->attributes()->id] = array(
					  'real_price' => $min
					, 'base_price' => $min
					, 'available'  => (string) $room->Inventory->attributes()->maxavail
					, 'pansion'    => $tmp
				);
			}
		}
		
		return $result;
	}
	
	public function get_room_price($siteID, $roomID, $date, $nights, $people, $pansion){
		list($hotel) = $this->std_call($siteID, $roomID, $date, $nights, $people, $this->board_in($pansion));
		
		$result = array();
		
		return $hotel ? (int) $hotel->RoomType->price->attributes()->value : 0;
	}
	
	public function site_list(){
		return false;
	}
	
	public function room_list($id){
		return false;
	}
}
