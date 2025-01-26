<?php
class SearchManager {
	static protected $initialized  = false;
	static protected $engine_list  = array('_base' => array('className' => 'baseEngine', 'classFile' => 'class.baseEngine.php'));

	static public function load_class($class){
		foreach(self::$engine_list as $e)
			if (!strcmp($class, $e['className'])){
				include rtrim(__DIR__,'/').'/searchEngines/'.$e['classFile'];
				return;
			}
	}

	
	static public function init(){
	    if (!self::$initialized){
            $que  = "SELECT * FROM `searchManager_engines` WHERE `active` = 1";
            self::$engine_list = array_merge(self::$engine_list, udb::key_row($que, 'index'));

            spl_autoload_register('SearchManager::load_class');

            self::$initialized = true;
        }
	}
	
	static public function get_site_list($engine){
		self::$initialized or self::init();

		if (!self::$engine_list[$engine])
			throw new Exception('Unknown engine ID');
		
		$client = new self::$engine_list[$engine]['className'];

		return method_exists($client, 'site_list_new') ? $client->site_list_new() : $client->site_list();
	}
	
	static public function get_room_list($siteID, $withExtra = false){
		self::$initialized or self::init();
		
		$que = "SELECT `externalEngine`, `externalID` FROM `sites` WHERE `siteID` = ".intval($siteID)." AND `externalID` <> ''";
		list($engine, $exID) = udb::single_row($que, UDB_NUMERIC);
		
		if (!self::$engine_list[$engine])
			throw new Exception('Unknown engine ID');
		
		$client = new self::$engine_list[$engine]['className'];

		return method_exists($client, 'room_list_new') ? $client->room_list_new($exID, $withExtra) : $client->room_list($exID, $withExtra);
	}
	
	/**
	 * @param $engine string engine
	 * @param $exSite mixed site ID
	 * @desc function returns array (map) of room IDs in internal /external systems
	 */
	static public function get_rooms_map($engine, $exSite){
		$siteID = udb::single_value("SELECT `siteID` FROM `sites` WHERE `externalEngine` = '" . udb::escape_string($engine) . "' AND `externalID` = '" . udb::escape_string($exSite) . "'");
		return $siteID ? udb::key_value("SELECT `externalRoomID`, `roomID` FROM `rooms` WHERE `siteID` = " . $siteID . " AND `externalRoomID` > ''", 0, 1) : array();
	}

	static public function get_free_sites($ids, $date, $nights, $people = array(2,0,0), $pansion = ''){
		self::$initialized or self::init();
		
		if (!is_array($ids))
			$ids = array($ids);

        if (count($people) < 3)
            $people = array_slice(array_merge($people, [0,0]), 0, 3);

		$que  = "SELECT `siteID`, `externalEngine`, `externalID` FROM `sites` WHERE `siteID` IN (".implode(',',array_map('intval',$ids)).") AND `externalID` <> ''";
        $list = udb::key_value($que, array('externalEngine', 'siteID'), 'externalID');

        $result = array();

		foreach($list as $engine => $eids){
			if (!self::$engine_list[$engine])
				//throw new Exception('Unknown engine ID');
                continue;

			$client = new self::$engine_list[$engine]['className'];
			$tmp    = $pansion ? $client->get_sites(array_values(array_unique($eids)), $date, $nights, $people, $pansion) : $client->get_sites(array_values(array_unique($eids)), $date, $nights, $people);

			foreach($eids as $siteID => $exID){
				if ($pos = strpos($exID,'#'))
					$exID = substr($exID,0,$pos);
				
				if (isset($tmp[$exID]) && $tmp[$exID]['real_price'])
					$result[$siteID] = $tmp[$exID];
			}

            $rids = array_diff_key($eids, $result);

            if ($nights == 1 && count($rids)){
                $tmp  = $pansion ? $client->get_sites(array_unique($rids), $date, 2, $people, $pansion) : $client->get_sites(array_unique($rids), $date, 2, $people);

                foreach($eids as $siteID => $exID){
                    if ($pos = strpos($exID,'#'))
                        $exID = substr($exID,0,$pos);

                    if (isset($tmp[$exID]) && $tmp[$exID]['real_price']){
                        $result[$siteID] = $tmp[$exID];
                        $result[$siteID]['minNights'] = 2;
                    }
                }
            }
		}

		return $result;
	}
	
	
	static public function get_free_rooms($siteID, $date, $nights, $rid = 0, $people = array(2,0,0), $pansion = ''){
		self::$initialized or self::init();

		$que = "SELECT `externalEngine`, `externalID` FROM `sites` WHERE `siteID` = ".intval($siteID)." AND `externalID` <> ''";
        list($engine, $exid) = udb::single_row($que, UDB_NUMERIC);

		if (!$engine || !self::$engine_list[$engine])
			throw new Exception('Unknown engine ID');
		
		$result = array();

		$que   = "SELECT `roomID`, `externalRoomID` FROM `rooms` WHERE `siteID` = ".intval($siteID)." AND `externalRoomID` <> ''".($rid ? " AND `roomID` = ".$rid : '');
        $rooms = udb::key_value($que, 'roomID', 'externalRoomID');

		$client  = new self::$engine_list[$engine]['className'];
		$exRooms = $pansion ? $client->get_rooms($exid, $date, $nights, $people, $pansion) : $client->get_rooms($exid, $date, $nights, $people);

		foreach($rooms as $roomID => $exID)
			if (isset($exRooms[$exID])){
				$tmp = array();
				foreach($exRooms[$exID]['pansion'] as $k => $v)
					if (is_numeric($k))
						$tmp[$k] = $v;

				$result[$roomID] = $exRooms[$exID];
				$result[$roomID]['pansion'] = $tmp;
			}

        if ($nights == 1 && count($rooms) != count($result)){
            $rids = array_diff_key($rooms, $result);
            $exRooms = $pansion ? $client->get_rooms($exid, $date, 2, $people, $pansion) : $client->get_rooms($exid, $date, 2, $people);

            foreach($rids as $roomID => $exID)
                if (isset($exRooms[$exID])){
                    $tmp = array();
                    foreach($exRooms[$exID]['pansion'] as $k => $v)
                        if (is_numeric($k))
                            $tmp[$k] = $v;

                    $result[$roomID] = $exRooms[$exID];
                    $result[$roomID]['minNights'] = 2;
                    $result[$roomID]['pansion'] = $tmp;
                }
        }

		return $result;
	}
	
	
	static public function get_room_price($roomID, $date, $nights, $people, $pansion = ''){
		self::$initialized or self::init();
		
		$que = "SELECT sites.`externalEngine`, sites.`externalID`, rooms.externalRoomID FROM `rooms` INNER JOIN `sites` USING(`siteID`) WHERE rooms.roomID = ".intval($roomID)." AND sites.externalID <> ''";
        list($engine, $exid, $exrid) = udb::single_row($que, UDB_NUMERIC);

		if (!$engine || !self::$engine_list[$engine])
			throw new Exception('Unknown engine ID');

		$client = new self::$engine_list[$engine]['className'];
		return $client->get_room_price($exid, $exrid, $date, $nights, $people, $pansion /*$tmp[$pansion]*/);
	}


	static public function create_booking(array $order){
        self::$initialized or self::init();

        $que = "SELECT sites.`externalEngine`, sites.`externalID` FROM `sites` WHERE `externalActive` = 1 AND `siteID` = " . intval($order['siteID']);
        list($engine, $exid) = udb::single_row($que, UDB_NUMERIC);

        if (!$engine || !self::$engine_list[$engine] || !$exid)
            return -1;

        $rooms = udb::key_value("SELECT `roomID`, `externalRoomID` FROM `rooms` WHERE `siteID` = " . intval($order['siteID']));

        $client = new self::$engine_list[$engine]['className'];

        // converting array to object and replacing inner IDs with external IDs
        $book = json_decode(json_encode($order));

        $book->siteID = $exid;
        foreach($book->rooms as &$room){
            if ($rooms[$room->roomID])
                $room->roomID = $rooms[$room->roomID];
            else
                throw new Exception('Room #' . $room->roomID . ' does not have external ID');
        }

        return $client->create_booking($book);
    }


    static public function cancel_booking($orderID){
        self::$initialized or self::init();

        $que = "SELECT sites.`externalEngine`, sites.`externalID`, orders.externalOrderID FROM `sites` INNER JOIN `orders` USING(`siteID`) WHERE sites.externalActive = 1 AND orders.orderID = " . intval($orderID);
        list($engine, $exid, $exOrder) = udb::single_row($que, UDB_NUMERIC);

        if (!$engine || !self::$engine_list[$engine] || !$exid || !$exOrder)
            return -1;

        $client = new self::$engine_list[$engine]['className'];

        return $client->cancel_booking($exid, $exOrder);
    }


	static public function smartUpdate($engine = ''){
        self::$initialized or self::init();

        if ($engine && !self::$engine_list[$engine])
            throw new Exception('Unknown engine ID');

        $report = array();

        $arr = $engine ? array($engine => self::$engine_list[$engine]) : self::$engine_list;
        foreach($arr as $engine => $en){
            if ($en['manual'] || substr($engine, 0, 1) == '_')
                continue;

            $es_en = udb::escape_string($engine);

            if ($newList = self::get_site_list($engine)){
                $oldList = udb::key_value("SELECT `siteID`, `siteName` FROM `searchManager_sites` WHERE `engine` = '" . $es_en . "'", 0, 1);
                if ($diff = array_diff_key($oldList, $newList)){      // siteIDs than existed before, but not anymore
                    $used = udb::single_column("SELECT `externalID` FROM `sites` WHERE `externalEngine` = '" . $es_en . "' AND `externalID` IN ('" . implode("','", array_keys($diff)) . "')");

                    if ($rem = array_diff(array_keys($diff), $used))    // get IDs that are non-existant and not is use
                        udb::query("DELETE FROM `searchManager_sites` WHERE `engine` = '" . $es_en . "' AND `siteID` IN ('" . implode("','", $rem) . "')");
                }
                unset($oldList, $diff, $used, $rem);

                $tmp   = array();
                $count = 0;

                foreach($newList as $id => $name){
                    $tmp[] = "('" . $es_en . "', '" . udb::escape_string($id) . "', '" . udb::escape_string($name) . "')";

                    if (count($tmp) >= 200){
                        $que = "INSERT INTO `searchManager_sites`(`engine`,`siteID`,`siteName`) VALUES" . implode(',', $tmp) . "
							ON DUPLICATE KEY UPDATE `siteName` = VALUES(`siteName`)";
                        udb::query($que);

                        $count += udb::affected_rows();
                        $tmp    = array();
                    }
                }

                if (count($tmp)){
                    $que = "INSERT INTO `searchManager_sites`(`engine`,`siteID`,`siteName`) VALUES" . implode(',', $tmp) . "
						ON DUPLICATE KEY UPDATE `siteName` = VALUES(`siteName`)";
                    udb::query($que);

                    $count += udb::affected_rows();
                }

                $report[] = $engine . ': ' . $count . ' zimers added or updated.';
            }
            else
                $report[] = $engine . ': - empty zimer list received -';
        }

        return implode(PHP_EOL, $report);
    }
}
