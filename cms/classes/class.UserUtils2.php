<?php
class UserUtils2 {
    // possible order types. values don't matter for now
    public static $payTypesShort = array() ;
    public static $payTypesFull = array() ;
    public static $typeCoupon = array() ;
    public static $cuponsList = array() ;
    public static $fullList = array() ;
    public static $dbCuponTypes = array() ;
    public static $CouponsfullList = array() ;
    private static $site;




    public static function init($siteID = 0){
        self::$site = $siteID;
        if($siteID) {
            $sql = "select payTypes.* from sitePayTypes left join payTypes on (sitePayTypes.paytypekey = payTypes.`key`) where payTypes.parent=0 and sitePayTypes.siteID=".$siteID . " order by payTypes.showOrder";
            $paytypes = udb::full_list($sql);
            if(!$paytypes) {
                $paytypes = udb::full_list("SELECT * FROM `payTypes` where parent=0 and defaultvalue=1 order by showOrder");
            }
        }
        else {
            $paytypes = udb::full_list("SELECT * FROM `payTypes` where parent=0 order by showOrder");
        }

        foreach ($paytypes as $paytype) {
            self::$payTypesShort[$paytype['key']] = $paytype['shortname'];
            self::$fullList[$paytype['key']] = $paytype['fullname'];
            if($paytype['fullname'])
                self::$payTypesFull[$paytype['key']] = $paytype['fullname'];
        }
        if($siteID) {
            $sql = "select payTypes.* from sitePayTypes left join payTypes on (sitePayTypes.paytypekey = payTypes.`key`) where payTypes.parent=11 and sitePayTypes.siteID=".$siteID . " order by payTypes.showOrder";
            $cuponTypes = udb::full_list($sql);
            if(!$cuponTypes) {
                $cuponTypes = udb::full_list("SELECT * FROM `payTypes` where parent=11 and defaultvalue=1 order by showOrder");
            }
        }
        else {
            $cuponTypes = udb::full_list("SELECT * FROM `payTypes` where parent=11 order by showOrder");
        }
        self::$dbCuponTypes = udb::key_row("SELECT * FROM `payTypes` where parent=11 order by showOrder","id");

        foreach ($cuponTypes as $cuponType) {
            self::$typeCoupon[$cuponType['key']] = $cuponType['shortname'];
            self::$CouponsfullList[$cuponType['key']] = $cuponType['shortname'];
            self::$cuponsList[$cuponType['key']] = udb::full_list("SELECT * FROM `payTypes` where parent=".$cuponType['id']." order by showOrder");
        }



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
        return ($type == 'coupon') ? self::$typeCoupon[$prov] : ($short ? self::$payTypesShort[$type] : self::$payTypesFull[$type]);
    }
}
