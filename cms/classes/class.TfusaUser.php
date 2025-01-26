<?php
class TfusaUser {
    const SUPER_CUT  = 500;
    const SUPER_NAME = 'מנהל ראשי';

    const FULL_ACCESS = 255;

    const SITE_TYPE_DAYS   = 1;
    const SITE_TYPE_SPA    = 2;
    const SITE_TYPE_HOURS  = 4;
    const SITE_TYPE_EVENTS = 8;

    protected static $cache = [];
    protected static $access_list = [
        'view' => 1,
        'edit' => 2
    ];

    protected $user_id;
    protected $permission;
    protected $site_list;
    protected $site_type = 1;

    public $name;
    public $single_site = false;

    public function __construct($id = '', $name = '', $permission = 50, $sites = [],$userType=0, $showstats=0){
        if ($id) {
            $_SESSION['tfusa'] = [];

            $this->user_id    = $_SESSION['tfusa']['user_id']    = $id;
            $this->name       = $_SESSION['tfusa']['name']       = $name;
            $this->permission = $_SESSION['tfusa']['permission'] = $permission;
			$this->userType   = $_SESSION['tfusa']['userType']   = $userType;
			$this->showstats  = $_SESSION['tfusa']['showstats']  = $showstats;
            $this->activeSite = 0;

            $_SESSION['tfusa']['sites'] = $this->set_sites($sites);
        }
        elseif (isset($_SESSION['tfusa']) && isset($_SESSION['tfusa']['user_id'])){
            $this->user_id    = $_SESSION['tfusa']['user_id'];
            $this->name       = $_SESSION['tfusa']['name'];
            $this->permission = $_SESSION['tfusa']['permission'];
            $this->userType   = $_SESSION['tfusa']['userType'];
            $this->showstats  = $_SESSION['tfusa']['showstats'];
            $this->activeSite = 0;

            $this->set_sites($_SESSION['tfusa']['sites'], $_SESSION['tfusa']['activeSite']);
        }
        else {
            $this->user_id    = 0;
            $this->name       = '';
            $this->permission = 0;
            $this->site_list  = [];
            $this->site_type  = 0;
            $this->activeSite = 0;
        }
    }

    public function id(){
        return $this->user_id;
    }

    public function access(){
        return $this->permission;
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
        }

        $_SESSION['tfusa']['sites'] = $this->site_list;
        $_SESSION['tfusa']['activeSite'] = $this->activeSite = $active ?: reset($this->site_list);

        return $this->sites();
    }

    public function active_site($siteID = 0){
        if (!$siteID)
            return $this->activeSite;
        if (!in_array($siteID, $this->site_list))
            throw new Exception('Active site is outside site list');
        return $_SESSION['tfusa']['activeSite'] = $this->activeSite = $siteID;
    }

    public function add_sites($siteID){
        if ($siteID){
            if (is_array($siteID))
                $this->site_list = array_unique(array_merge($this->site_list, $siteID));
            elseif (intval($siteID) && !in_array(intval($siteID), $this->site_list))
                $this->site_list[] = intval($siteID);

            $_SESSION['tfusa']['sites'] = $this->site_list;

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
        if (isset($_SESSION['tfusa']))
            unset($_SESSION['tfusa']);

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
            default:
                return false;
        }
    }
}
