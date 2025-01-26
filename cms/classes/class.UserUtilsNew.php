<?php
class UserUtilsNew {
    // possible order types. values don't matter for now
    public static $payTypesShort = array() ;
    public static $allpayTypesShort = array() ;
    public static $payTypesFull = array() ;
    public static $allpayTypesFull = array() ;
    public static $typeCoupon = array() ;
    public static $cuponsList = array() ;
    public static $fullList = array() ;
    public static $dbCuponTypes = array() ;
    public static $CouponsfullList = array() ;
    public static $allCuponTypes = array() ;
    public static $customPayTypes = array();
    private static $site;




    public static function init($siteID = 0){
        self::$site = $siteID = intval($siteID);

        $types = udb::single_value("SELECT `siteType` FROM `sites` WHERE `siteID` = " . $siteID);
        $partial = ($types & 2) ? 'spa' : 'host';

        if($siteID) {
            $sql = "select payTypes.* from sitePayTypes left join payTypes on (sitePayTypes.paytypekey = payTypes.`key`) where payTypes.parent=0 and sitePayTypes.siteID=".$siteID . " order by payTypes.showOrder";
            $paytypes = udb::full_list("SELECT * FROM `payTypes` where parent=0 and defaultvalue=1 order by showOrder");
            if(!$paytypes) {
                $paytypes = udb::full_list("SELECT * FROM `payTypes` where parent=0 and defaultvalue=1  and active=1 order by showOrder");
            }
        }
        else {
            $paytypes = udb::full_list("SELECT * FROM `payTypes` where parent=0 and active=1 order by showOrder");
        }

        foreach ($paytypes as $paytype) {
            self::$payTypesShort[$paytype['key']] = $paytype['shortname'];
            self::$fullList[$paytype['key']] = $paytype['fullname'];
            if($paytype['fullname'] && !in_array($paytype['fullname'], ['google', 'פייסבוק', 'B144', 'אינסטגרם', 'פה לאוזן', 'שילוט', 'אושיית אינסטגרם']))
                self::$payTypesFull[$paytype['key']] = $paytype['fullname'];
        }
        if($siteID) {
            $sql = "select payTypes.* from sitePayTypes left join payTypes on (sitePayTypes.paytypekey = payTypes.`key`) where payTypes.parent=11 and payTypes.active=1 and payTypes.".$partial."=1 and sitePayTypes.siteID=".$siteID . " order by payTypes.showOrder";
            $cuponTypes = udb::full_list($sql);
//            if(!$cuponTypes) {
//                $cuponTypes = udb::full_list("SELECT * FROM `payTypes` where parent=11  order by showOrder");
//            }
        }
        else {
            $cuponTypes = udb::full_list("SELECT * FROM `payTypes` where parent=11 and active=1 order by cuponPrice ASC");
        }
        self::$dbCuponTypes = udb::key_row("SELECT * FROM `payTypes` where parent=11 and active=1 and payTypes.".$partial."=1 order by cuponPrice ASC","id");

        foreach ($cuponTypes as $cuponType) {
            self::$typeCoupon[$cuponType['key']] = $cuponType['shortname'];
            self::$CouponsfullList[$cuponType['key']] = $cuponType['fullname'];
            self::$cuponsList[$cuponType['key']] = udb::full_list("SELECT * FROM `payTypes` where parent=".$cuponType['id']."  order by cuponPrice ASC");
        }
        foreach (self::$dbCuponTypes as $cuponType) {
            self::$allCuponTypes[$cuponType['key']] =  $cuponType['fullname'];
        }
        $paytypes = udb::full_list("SELECT * FROM `payTypes` where parent=0 and defaultvalue=1 and active=1 order by showOrder");
        foreach ($paytypes as $paytype) {
            self::$allpayTypesShort[$paytype['key']] = $paytype['shortname'];
            self::$allpayTypesFull[$paytype['key']] = $paytype['fullname'];
        }




    }

    public static function guestMember(){
        return udb::key_value("SELECT `key`, `shortname` FROM `payTypes` WHERE `key` IN ('guest', 'member')", 'key');
    }
    public static function otherSources(){
        return udb::key_value("SELECT `key`, `shortname` FROM `payTypes` WHERE `key` IN ('facebook', 'google','insagram','b144','hearsay','signs','instagram-influencers','returnclient','fliers','owner')", 'key');
    }

//    public static function fullSourcesList(){
//        return udb::key_row("SELECT * FROM `payTypes` WHERE `key` IN ('guest', 'member','facebook', 'google','insagram','b144','hearsay','signs','instagram-influencers')", 'key');
//    }

    public static function fullSourcesList(){
        $types = array_merge(self::$CouponsfullList,self::guestMember(),self::otherSources());
        $types = array_keys($types);
        foreach ($types as $k=>$t) {
            $types[$k] = "'" .$t. "'";
        }
        $retVal = udb::key_row("SELECT * FROM `payTypes` WHERE `key` IN (".implode("," , $types).")", 'key');
        $retVal['online'] = array("letterSign"=>"on","hexColor"=>"yellow");
        return $retVal;
    }

    public static function getCustomPayTypes($sitesIDs){
        $retValue = [];
        if(is_array($sitesIDs)) {
            foreach($sitesIDs as $siteID) {
                if(!self::$customPayTypes[$siteID]) {
                    self::$customPayTypes[$siteID] = udb::key_list("select * from customPayTypes where siteID=".$siteID , "parent");
                }
                $retValue[$siteID] = self::$customPayTypes[$siteID];
            }
        }
        else {
            if(!self::$customPayTypes[$sitesIDs]) {
                self::$customPayTypes[$sitesIDs] = udb::key_list("select * from customPayTypes where siteID=" . $sitesIDs, "parent");
                $retValue = self::$customPayTypes[$sitesIDs];
            }
        }

        return $retValue;
    }


    public static $orderTypes = [
        'order' => 'asdasda',
        'preorder' => 'asdasdas'
    ];



//    public static $payTypesShort = [
//        'ccard'  => 'אשראי',
//        'pseudocc' => 'אשראי',
//        'cash'   => 'מזומן',
//        'check'  => "צ'ק",
//        'refund' => 'זיכוי כ.אשראי',
//        'transfer' => 'העברה בנקאית',
//        'bit' => 'העברה ב-bit',
//        'paybox' => 'העברה ב-PayBox',
//        'guest' => 'אורחי מלון',
//        'member' => 'מנוי',
//        'coupon' => 'ספק/קופון/שובר'
//    ];

//    public static $payTypesFull = [
//        'ccard'  => 'כרטיס אשראי',
//        'pseudocc' => 'אשראי',
//        'cash'   => 'מזומן',
//        'check'  => "צ'ק",
////        'refund' => 'זיכוי כ.אשראי',
//        'transfer' => 'העברה בנקאית',
//        'bit' => 'העברה ב-bit',
//        'paybox' => 'העברה ב-PayBox',
//        'guest' => 'אורחי מלון',
//        'member' => 'מנוי',
//        'coupon' => 'ספק / קופון / שובר'
//    ];

//    public static $typeCoupon = [
//        'spaplus' => 'ספא פלוס - SpaPlus',
//        'vouchers' => 'וואוצרס - Vouchers.co.il',
//        'hatsdaa'  => 'בהצדעה - Behatsdaa',
//        'hist' => 'ביחד בשבילך - Hist',
//        'buyme' => 'ביי מי - BuyMe',
//        'groupon' => 'גרו - Groo',
//        'htzone' => 'הייטק זון - Htzone',
//        'histadrut' => 'הסתדרות - Histadrut',
//        'iec' => 'חברת חשמל - Iec',
//        'max' => 'מקס - Max',
//        'souly' => 'נופשונית - Nofshonit',
//        'nofesh' => 'קופונופש - Cuponofesh',
//        'idea' => 'רעיונית - Raayonit'
//    ];

    public static function method($type, $prov = '', $short = false){
        return ($type == 'coupon') ? self::$allCuponTypes[$prov] : ($short ? self::$allpayTypesShort[$type] : self::$allpayTypesFull[$type]);
    }
}
