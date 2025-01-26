<?php
class ReferenceMap {
    protected static $table = 'referenceMap';

    private $engineID;
    private $engineData;

    public function __construct($engine){
        $this->engineData = udb::single_row("SELECT * FROM `referenceEngines` WHERE `engineKey` = '" . udb::escape_string($engine) . "'");
        if (!$this->engineData)
            throw new Exception("Cannot find " . $engine . " engine");
        if (!$this->engineData['active'])
            throw new Exception($engine . " engine is inactive");

        $this->engineID = $this->engineData['engineID'];
    }

    public function __call($name, $params = []){
        $var = preg_replace_callback('/_(id$|[a-z])/', function($m){return $m[1] == 'id' ? 'ID' : strtoupper($m[1]);}, $name);
        return property_exists($this, $var) ? $this->$var : null;
    }

//    public function engine_id(){
//        return $this->engineID;
//    }
//
//    public function engine_data(){
//        return $this->engineData;
//    }

    public function get_remote_value($localID, $refType){
        return udb::single_value("SELECT `remoteID` FROM `" . static::$table . "` WHERE `engineID` = " . $this->engineID . " AND `localID` = " . intval($localID) . " AND `refType` = '" . udb::escape_string($refType) . "'");
    }

    public function get_local_value($remoteID, $refType){
        return udb::single_value("SELECT `localID` FROM `" . static::$table . "` WHERE `engineID` = " . $this->engineID . " AND `remoteID` = '" . udb::escape_string($remoteID) . "' AND `refType` = '" . udb::escape_string($refType) . "'");
    }

    public function get_remote_map($localID, $refType, $multiValue = false){
        if ($multiValue)
            return udb::key_column("SELECT `localID`, `remoteID` FROM `" . static::$table . "` WHERE `engineID` = " . $this->engineID . " AND `localID` IN (" . (is_array($localID) ? implode(',', array_map('intval', $localID)) : intval($localID)) . ") AND `refType` = '" . udb::escape_string($refType) . "'");
        return udb::key_value("SELECT `localID`, `remoteID` FROM `" . static::$table . "` WHERE `engineID` = " . $this->engineID . " AND `localID` IN (" . (is_array($localID) ? implode(',', array_map('intval', $localID)) : intval($localID)) . ") AND `refType` = '" . udb::escape_string($refType) . "'");
    }

    public function get_local_map($remoteID, $refType, $multiValue = false){
        if ($multiValue)
            return udb::key_column("SELECT `remoteID`, `localID` FROM `" . static::$table . "` WHERE `engineID` = " . $this->engineID . " AND `remoteID` IN ('" . (is_array($remoteID) ? implode("','", array_map('udb::escape_string', $remoteID)) : udb::escape_string($remoteID)) . "') AND `refType` = '" . udb::escape_string($refType) . "'");
        return udb::key_value("SELECT `remoteID`, `localID` FROM `" . static::$table . "` WHERE `engineID` = " . $this->engineID . " AND `remoteID` IN ('" . (is_array($remoteID) ? implode("','", array_map('udb::escape_string', $remoteID)) : udb::escape_string($remoteID)) . "') AND `refType` = '" . udb::escape_string($refType) . "'");
    }

    public function get_api_manager(){
        return $this->engineData['engineClass'] ? new $this->engineData['engineClass'] : null;
    }

    public static function from_local($localID, $engine){
        try {
            $sites = (new static($engine))->get_remote_map($localID, 'site');
            return $sites[$localID] ? (new RemoteSite($localID, $sites[$localID], $engine)) : null;
        }
        catch (Exception $e){
            return null;
        }
    }

    public static function from_remote($remoteID, $engine){
        try {
            $sites = (new static($engine))->get_local_map($remoteID, 'site', true);
            if ($sites[$remoteID])
                return array_map(function($localID) use ($remoteID, $engine) {return new RemoteSite($localID, $remoteID, $engine);}, $sites[$remoteID]);
        }
        catch (Exception $e){}

        return [];
    }
}


class RemoteSite extends ReferenceMap {
    protected $localID;
    protected $remoteID;
    protected $roomMap;

    private $api;

    public function __construct($local, $remote, $engine){
        parent::__construct($engine);

        $this->localID  = $local ?: 0;
        $this->remoteID = $remote ?: '';

        $rids = udb::single_column("SELECT `roomID` FROM `rooms` WHERE `siteID` = " . $this->localID);
        $this->roomMap = $rids ? $this->get_remote_map($rids, 'room') : [];

        $this->api = $this->get_api_manager();
    }

    public function local_id(){
        return $this->localID;
    }

    public function remote_id(){
        return $this->remoteID;
    }

    public function local_room($remoteID){
        return array_search($remoteID, $this->roomMap);
    }

    public function remote_room($localID){
        return $this->roomMap[$localID];
    }

    public function remote_rooms_list(){
        $rooms = $this->api->fetch_rooms_list($this->remoteID);
    }

    public function local_order($remoteID){
        if ($orderID = $this->get_local_value($remoteID, 'order')){
            if (!udb::single_value("SELECT COUNT(*) FROM `orders` WHERE `orderID` = " . $orderID . " AND `siteID` = " . $this->localID))
                throw new ReferenceException("Booking site ID doesn't match: " . $this->localID);

            return $orderID;
        }
        return null;
    }

    public function remote_order($localID){
        return $this->get_remote_value($localID, 'order');
    }
}


class ReferenceException extends \Exception {}
