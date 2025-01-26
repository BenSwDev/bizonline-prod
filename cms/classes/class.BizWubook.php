<?php
ini_set("pcre.jit", "0");

require_once __DIR__ . "/../../wubook/vendor/autoload.php";
use WubookWrapper\WuBookManager;

class BizWubook {
    const WUBOOK_DIR = __DIR__ . "/../../wubook";

    private $manager;
    private $token;

    protected $key_table = 'wubook_keys';

    public function __construct(){
        $config = [
            'username'     => 'RP161',
            'password'     => '89g9vub4',
            'provider_key' => '6206ca77d37dbdda568615845a987ba45a512a5d33356527',
            'lcode'        => '1604225514',
            'cachedir'     => self::WUBOOK_DIR . '/cache',
            'logfilepath'  => self::WUBOOK_DIR . "/logs/"
        ];

        $this->manager = new WuBookManager($config);
    }

    public function availability(){
        if (!$this->token)
            $this->token = $this->manager->auth()->acquire_token();

        return $this->manager->availability($this->token);
    }

    public function update_rooms($rooms){
        $rids = array_keys($rooms);
        if (!$rids)
            throw new Exception('Empty rooms array');

        $keys = udb::single_list("SELECT `keyID`, `keyStr` FROM `" . $this->key_table . "` WHERE `keyType` = 'room' AND `keyID` IN (" . implode(',', $rids) . ") AND `keyStr` > ''");
        if (!$keys)
            return ['has_error' => false, 'data' => 'No Wubook keys found'];

        $prep = [];
        foreach($keys as $key)
            $prep[$key['keyStr']] = $rooms[$key['keyID']];

        return $this->availability()->update_avail_formatted($prep);
    }

    public function __call($name, $args){
        return $this->manager->$name(...$args);
    }



    public function delete_keys($type, $id, $source = null){
        return udb::query("DELETE FROM `" . $this->key_table . "` WHERE `keyType` = '" . udb::escape_string($type) . "' AND `keyID` = " . intval($id) . ($source ? " AND `keySource` = '" . udb::escape_string($source) . "'" : ""));
    }

    public function save_key($type, $id, $source, $key, $update = true){
        return udb::insert($this->key_table, [
            'keyType'   => $type,
            'keySource' => $source,
            'keyStr'    => $key,
            'keyID'     => $id
        ], $update);
    }

    public function save_room_keys($id, $keys = []){
        $this->delete_keys('room', $id);
        foreach($keys as $source => $key)
            $this->save_key('room', $id, $source, $key);
    }

    public function save_site_keys($id, $keys = []){
        $this->delete_keys('site', $id);
        foreach($keys as $source => $key)
            $this->save_key('site', $id, $source, $key);
    }


    public function get_keys($type, $id){
        return udb::key_value("SELECT `keySource`, `keyStr` FROM `" . $this->key_table . "` WHERE `keyType` = '" . udb::escape_string($type) . "' AND `keyID` = " . intval($id));
    }

    public function get_room_keys($id){
        return $this->get_keys('room', $id);
    }

    public function get_site_keys($id){
        return $this->get_keys('site', $id);
    }

    public function get_room_id($wuID){
        return is_array($wuID) ?
            udb::key_value("SELECT `keyStr`, `keyID` FROM `" . $this->key_table . "` WHERE `keyStr` IN ('" . implode("','", $wuID) . "')") :
            udb::single_value("SELECT `keyID` FROM `keyStr` = '" . $wuID . "'");
    }
}
