<?php
include_once 'class.TfusaBaseUser.php';

class MemberUser extends TfusaBaseUser {
    public $class_suffix = 'member';
    public $mult_login   = false;

    public function __construct($id = '', $name = '', $permission = 0, $sites = [], $userType = 0, $showstats = 0){
        parent::__construct($id, $name, 127, $sites, $userType, $showstats);
    }

    public function set_sites($sites = [], $active = 0){
        // set/change list only once - cannot redeclare it later
        if (empty($this->site_list))
            parent::set_sites(is_array($sites) ? array_slice($sites, 0, 1) : $sites, 0);
    }

    public function add_sites($sites = []){
        // set/change list only once - cannot redeclare it later
        if (empty($this->site_list))
            $this->set_sites($sites);
    }
}
