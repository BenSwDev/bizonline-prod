<?php
class VAT {
    protected static $vat = 18;

    /**
     * @return int
     * @desc returns VAT as natural number from 0 to 100
     */
    public static function get_percent(){
        return self::$vat;
    }

    /**
     * @return float
     * @desc returns VAT as decimal number from 0 to 1
     */
    public static function get_mult(){
        return round(self::$vat / 100, 2);
    }

    /**
     * @param number $beforeVAT "before VAT" sum
     * @return float
     * @desc returns "after VAT" sum
     */
    public static function after_vat($beforeVAT){
        return round($beforeVAT / 100 * (100 + self::$vat), 2);
    }

    /**
     * @param number $afterVAT "after VAT" sum
     * @return float
     * @desc returns "before VAT" sum
     */
    public static function before_vat($afterVAT){
        return round($afterVAT / (100 + self::$vat) * 100, 2);
    }

    /**
     * @param number $beforeVAT "before VAT" sum
     * @return float
     * @desc returns VAT
     */
    public static function calc_vat($beforeVAT){
        return round($beforeVAT / 100 * self::$vat, 2);
    }

    /**
     * @param number $afterVAT "after VAT" sum
     * @return float
     * @desc returns VAT
     */
    public static function extract_vat($afterVAT){
        return round($afterVAT / (100 + self::$vat) * self::$vat, 2);
    }
}
