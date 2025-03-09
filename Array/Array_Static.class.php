<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class Array_Static {

    // @todo внутрь одного метода можно сразу запихнуть подсчет min/max/avg/med/var
    // и обнулять статистику только при изменении массива
    // это особенно актуально для Array_Static, потому что он сука static

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

    public static function Variance($a) {
        $a = new Array_Object($a);
        return $a->variance();
    }

    public static function Sum($a) {
        $a = new Array_Object($a);
        return $a->sum();
    }

    public static function Quantile(array $a, float $percentile) {
        $a = new Array_Object($a);
        return $a->quantile($percentile);
    }

    public static function FilterOutliers(array $a, float $threshold) {
        // @todo возможно переносить внутрь не имело смысла
        $a = new Array_Object($a);
        return $a->filterOutliers($threshold);
    }

    /**
     * Calculate array median
     *
     * @param $a
     * @return float
     */
    public static function Med($a, $countLimit = false) {
        $a = new Array_Object($a);
        return $a->median($countLimit);
    }

}