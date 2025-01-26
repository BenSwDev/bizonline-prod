<?php
class TfusaBaseUser {
    const SUPER_CUT  = 500;
    const SUPER_NAME = 'מנהל ראשי';

    const ACCESS_SUPER = 65535;
    const ACCESS_BIT_ADMIN = 128;

    const SITE_TYPE_DAYS   = 1;
    const SITE_TYPE_SPA    = 2;
    const SITE_TYPE_HOURS  = 4;
    const SITE_TYPE_EVENTS = 8;

    const CURRENT_VERSION = 2;

	const CLIENT_USER_ID = -1;

    protected static $cache = [];
    protected static $access_list = [
        'view' => 1,
        'edit' => 2
    ];

    protected $user_id;
    protected $permission;
    protected $site_list;
    protected $site_type = 1;
    protected $active_site;
    protected $selected;

    public $name;
    public $single_site  = false;

    public $access_token = '';
    public $class_suffix = '';
    public $mult_login   = true;

    public $calendar_only;

    public $version;

    public function __construct($id = '', $name = '', $permission = 0, $sites = [], $userType = 0, $showstats = 0){
        $this->user_id     = $id;
        $this->name        = $name;
        $this->permission  = $permission;
        $this->userType    = $userType;
        $this->showstats   = $showstats;
        $this->active_site = 0;
        $this->selected    = 0;

        $this->isportal = 0;

        $this->access_token = md5(mt_rand() . microtime());

        $this->version = self::CURRENT_VERSION;

        $this->set_sites($sites);
    }

    public function id(){
        return $this->user_id;
    }

    public function access($compare = null){
        //return is_null($compare) ? $this->permission : ($this->permission >= $compare);
        return is_null($compare) ? $this->permission : ($this->permission & $compare);
    }

    public function sites($asString = false, $sep = ','){
        return $asString ? implode($sep, $this->site_list) : $this->site_list;
    }

    public function set_sites($sites, $active = 0){
        $this->site_list   = $sites ? (is_array($sites) ? $sites : (is_scalar($sites) ? [intval($sites)] : [])) : [];
        $this->single_site = (count($this->site_list) == 1);

        if ($this->site_list){
            $this->site_type = 0;
            $types = udb::single_column("SELECT IF(`spaplusID`, `siteType`, 1) FROM `sites` WHERE `siteID` IN (" . $this->sites(true) . ")");
            foreach($types as $type)
                $this->site_type |= $type;
            $isPortals = udb::single_column("SELECT portalsID FROM `sites` WHERE `siteID` IN (" . $this->sites(true) . ")");
            foreach($isPortals as $isPortal)
                $this->isportal += intval($isPortal);
        }

        $this->active_site = $active ?: reset($this->site_list);
        $this->selected    = $active;

        return $this->sites();
    }

    public function active_site($siteID = -1){
        if ($siteID <= 0)
            return $this->active_site;
        if (!in_array($siteID, $this->site_list))
            throw new Exception('Active site is outside site list');

		$this->calendar_only = ($this->permission == self::ACCESS_SUPER) ? false : !!udb::single_value("SELECT `calendarOnly` FROM `sites` WHERE `siteID` = " . $siteID);
		
        return $this->active_site = $this->selected = $siteID;
    }

    public function select_site($siteID = -1){
        if ($siteID < 0)
            return $this->selected;
        if ($siteID == 0){
            //$this->active_site = reset($this->site_list) ?: 0;
            return $this->selected = 0;
        }
//        if (!in_array($siteID, $this->site_list))
//            throw new Exception('Active site is outside site list');
//
//        return $this->active_site = $this->selected = $siteID;
		return $this->active_site($siteID);
    }

    public function add_sites($siteID){
        if ($siteID){
            if (is_array($siteID))
                $this->site_list = array_unique(array_merge($this->site_list, $siteID));
            elseif (intval($siteID) && !in_array(intval($siteID), $this->site_list))
                $this->site_list[] = intval($siteID);

            $this->single_site = (count($this->site_list) == 1);
        }

        return $this;
    }

    public function has($id){
        return in_array($id, $this->site_list);
    }

    public function can($action){
        return !!((self::$access_list[$action] ?? 0) & $this->permission);
    }

    public function __call($name, $args){
        $split = explode('_', $name);
        if ($split[0] == 'can')
            return $this->can($split[1]);
        elseif ($split[0] == 'is')
            return $this->is($split[1]);

        return false;
    }

    public function logout(){
//        if (isset($_SESSION['tfusa']))
//            unset($_SESSION['tfusa']);

        $this->user_id     = 0;
        $this->name        = '';
        $this->permission  = 0;
        $this->site_list   = [];
        $this->site_type   = 0;
        $this->single_site = false;
    }

    public function user($userID){
        if ($this->user_id == $userID)
            return $this->name;

		elseif ($userID == self::CLIENT_USER_ID) 
			return 'פעולת לקוח';

        return self::$cache[$userID] ?? (self::$cache[$userID] = udb::single_value("SELECT `name` FROM `biz_users` WHERE `buserID` = " . intval($userID)));
    }

    public function is($type){
        switch(strtolower($type)){
            case 'spa':
                return !!($this->site_type & self::SITE_TYPE_SPA);
            case 'zimer':
                return !!($this->site_type & self::SITE_TYPE_DAYS);
            case 'rooms':
                return !!($this->site_type & (self::SITE_TYPE_DAYS | self::SITE_TYPE_HOURS | self::SITE_TYPE_EVENTS));
            case 'portals':
                return $this->isportal > 0;
            default:
                return false;
        }
    }

    public function suffix($pre = ''){
        return $this->class_suffix ? $pre . $this->class_suffix : null;
    }

    public static function passHash($pass){
        return password_hash($pass, PASSWORD_DEFAULT);
    }

    public static function passVerify($pass, $hash){
        return password_verify($pass, $hash);
    }
}
