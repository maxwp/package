<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

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
        // костыль если вдруг передам сюда null
        if (!$a) {
            return 0;
        }
        return count($a);
    }

    public static function Avg($a, $countLimit = false) { // @todo drop count
        $a = new Array_Object($a);
        return $a->avg($countLimit);
    }

    public static function WAvg($a, $w) {
        $sum = 0;
        $weight = 0;
        foreach ($a as $key => $value) {
            $sum += $value * $w[$key];
            $weight += $w[$key];
        }
        if ($weight != 0) {
            return $sum / $weight;
        } else {
            return 0;
        }
    }

    public static function Variance($a) {
        $a = new Array_Object($a);
        return $a->variance();
    }

    public static function StdDeviation($a) {
        $a = new Array_Object($a);
        return $a->stdDeviation();
    }

    public static function Sum($a) {
        $a = new Array_Object($a);
        return $a->sum();
    }

    public static function Quantile($a, $percentile) {
        $a = new Array_Object($a);
        return $a->quantile($percentile);
    }

    public static function TailCount($a, $percentile) {
        $a = new Array_Object($a);
        return $a->tailCount($percentile);
    }

    public static function FilterOutliers($a, $threshold) {
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