<?php
class BizOnlineMailer extends SSDphpMailer {
    public function __construct(){
        parent::__construct();

        //$this->setFrom("info@bizonline.co.il", 'Bizonline');
        $this->useSMTP('bizonline2.spd.co.il', 'info@bizonline.co.il', 'zjm9uau1xbmwuk')->setFrom("info@bizonline.co.il", 'Bizonline');
    }
}
