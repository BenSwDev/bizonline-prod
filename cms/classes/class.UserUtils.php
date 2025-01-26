<?php
class UserUtils {
    // possible order types. values don't matter for now
    public static $orderTypes = [
        'order' => 'asdasda',
        'preorder' => 'asdasdas'
    ];

    public static $payTypesShort = [
        'ccard'  => 'אשראי',
        'pseudocc' => 'אשראי',
        'cash'   => 'מזומן',
        'check'  => "צ'ק",
        'refund' => 'זיכוי כ.אשראי',
        'transfer' => 'העברה בנקאית',
        'bit' => 'העברה ב-bit',
        'paybox' => 'העברה ב-PayBox',
        'guest' => 'אורחי מלון',
        'member' => 'מנוי',
        'coupon' => 'ספק/קופון/שובר'
    ];

    public static $payTypesFull = [
        'ccard'  => 'כרטיס אשראי',
        'pseudocc' => 'אשראי',
        'cash'   => 'מזומן',
        'check'  => "צ'ק",
//        'refund' => 'זיכוי כ.אשראי',
        'transfer' => 'העברה בנקאית',
        'bit' => 'העברה ב-bit',
        'paybox' => 'העברה ב-PayBox',
        'guest' => 'אורחי מלון',
        'member' => 'מנוי',
        'coupon' => 'ספק / קופון / שובר'
    ];

    public static $typeCoupon = [
        'spaplus' => 'ספא פלוס - SpaPlus',
        'vouchers' => 'וואוצרס - Vouchers.co.il',
        'pumba' => 'אקונה מטטה',
        'egift' => 'אקסטרא גיפטקארד',
        'hatsdaa'  => 'בהצדעה - Behatsdaa',
        'hist' => 'ביחד בשבילך - Hist',
        'newdor' => 'דור חדש בשוברים',
        '2give' => 'טו גיב',
        'buyme' => 'ביי מי - BuyMe',
        'groupon' => 'גרו - Groo',
        'htzone' => 'הייטק זון - Htzone',
        'histadrut' => 'הסתדרות - Histadrut',
        'iec' => 'חברת חשמל - Iec',
        'mega' => 'מגה לאן',
        'max' => 'מקס - Max',
        'souly' => 'נופשונית - Nofshonit',
        'nofesh' => 'קופונופש - Cuponofesh',
        'linkplus' => 'קשרים פלוס',
        'idea' => 'רעיונית - Raayonit',
        'busines' => 'תמריץ לעסקים'
    ];

    public static function method($type, $prov = '', $short = false){
        return ($type == 'coupon') ? self::$typeCoupon[$prov] : ($short ? self::$payTypesShort[$type] : self::$payTypesFull[$type]);
    }
}
