<?php
include_once 'class.CardCom.php';

class CardComGeneral extends CardCom {
    public function __construct($siteID, $target)
    {
        $data = udb::single_row("SELECT * FROM `sites_terminals` WHERE `siteID` = " . $siteID . " AND `target` = '" . udb::escape_string($target) . "' AND `masof_type` = 'cardcom' AND `active` = 1");
        if (!$data || !$data['masof_number'] || !$data['masof_key'])
            throw new Exception("Cannot find terminal for site " . $siteID . " extension " . $target);

        parent::__construct($data['masof_number'], $data['masof_key'], $data['masof_pwd']);

        $urlBase = 'https://bizonline.co.il/api/cardcom/';

        $this->ownerID      = $siteID;

        $this->has_cc_charge = !!intval($data['flag_cc_charge']);
        $this->has_cc_check  = !!intval($data['flag_cc_check']);
        $this->has_tokens    = !!intval($data['flag_tokens']);
        $this->has_invoice   = !!intval($data['invoice']);
        $this->has_swipe     = !!intval($data['flag_swipe']);
        $this->has_VAT       =  !intval($data['flag_noVAT']);
        $this->has_freeze    = ($data['check_type'] == 10);

        $this->department    = $data['department'];
        $this->invoiceType   = $data['doc_type'];
        $this->cardCheckType = ($data['check_type'] >= 5) ? 5 : 2;

        $this->successURL    = $urlBase . 'pay_result.php';
        $this->failURL       = $urlBase . 'pay_result.php';
        $this->notifyURL     = $urlBase . 'notify_BHfc2tjhWV.php';
    }
}
