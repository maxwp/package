<?php
class Array_Static {

    public static function Max($a) {
        $a = new Array_Object($a);
        return $a->max();
    }

    public static function Min($a) {
        $a = new Array_Object($a);
        return $a->min();
    }

    public static function Count($a) {
        return count($a);
    }

    public static function Avg($a, $countLimit = false) {
        $a = new Array_Object($a);
        return $a->avg($countLimit);
    }

    public static function Sum($a) {
        $a = new Array_Object($a);
        return $a->sum();
    }

    /**
     * Calculate array median
     *
     * @param $a
     * @return float
     */
    public static function Median($a, $countLimit = false) {
        $a = new Array_Object($a);
        return $a->median($countLimit);
    }

}