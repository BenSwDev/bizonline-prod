<?php
class BizOnlineMailer extends SSDphpMailer {
    public function __construct(){
        parent::__construct();

        $this->setFrom("info@bizonline.co.il", 'Bizonline');
    }
}
