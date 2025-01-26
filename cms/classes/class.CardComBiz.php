<?php
class CardComBiz extends CardCom {
    public static $_customPayTypeMap = [
        'transfer' => [31, 'העברה בנקאית'],
        'bit'      => [28, 'אפליקציה BIT'],
        'paybox'   => [27, 'אפליקציה PayBox'],
        'guest'    => ['Cash'],
        'member'   => ['Cash'],
        'coupon'   => ['Cash'],
        'cash'     => ['Cash'],
        'check'    => ['Check'],
        'ccard'    => ['CCard']
    ];

    public function __construct($siteID, $terminal, $username, $pwd = ''){
        parent::__construct($terminal, $username, $pwd);

        $data = udb::single_row("SELECT `masof_no_cvv`, `masof_invoice`, `masof_swipe`, `masof_noVAT`, `masof_department`, `masof_no_charge`, `masof_doc_type`, `masof_check_type` FROM `sites` WHERE `siteID` = " . $siteID);

        $urlBase = 'https://bizonline.co.il/api/cardcom/';

        $this->ownerID      = $siteID;

        $this->has_cc_check  = true;
        $this->has_cc_charge =  !intval($data['masof_no_charge']);
        $this->has_tokens    = !!intval($data['masof_no_cvv']);
        $this->has_invoice   = !!intval($data['masof_invoice']);
        $this->has_swipe     = !!intval($data['masof_swipe']);
        $this->has_VAT       =  !intval($data['masof_noVAT']);
        $this->has_freeze    = ($data['masof_check_type'] == 10);

        $this->department    = $data['masof_department'];
        $this->invoiceType   = $data['masof_doc_type'];
        $this->cardCheckType = ($data['masof_check_type'] >= 5) ? 5 : 2;

        $this->successURL    = $urlBase . 'pay_result.php';
        $this->failURL       = $urlBase . 'pay_result.php';
        $this->notifyURL     = $urlBase . 'notify_BHfc2tjhWV.php';
    }
}
