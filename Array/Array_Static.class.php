<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Статическая обретка над Array_Object;
 * для производительности лучше использовать сам Array_Object
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

    public static function Avg($a) {
        $a = new Array_Object($a);
        return $a->average();
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

    public static function FilterOutliers($a, $threshold) {
        $a = new Array_Object($a);
        return $a->filterOutliers($threshold);
    }

    /**
     * Coefficient of Variation (CV = stddev / mean)
     *
     * CV < 0.5 - почти ровный поток
     * 0.5 – 1 - норм
     * >1 - уже нестабильный
     * >2 - трэш / burst
     *
     * @return float
     */
    public static function CV($a) {
        $a = new Array_Object($a);
        return $a->cv();
    }

    /**
     * Calculate array median
     *
     * @param $a
     * @return float
     */
    public static function Med($a) {
        $a = new Array_Object($a);
        return $a->median();
    }

}