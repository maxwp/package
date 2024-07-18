<?php
class Math_Array {

    public static function Max($a) {
        $max = false;
        foreach ($a as $x) {
            if ($max === false || $x > $max) {
                $max = $x;
            }
        }
        return $max;
    }

    public static function Min($a) {
        $min = false;
        foreach ($a as $x) {
            if ($min === false || $x < $min) {
                $min = $x;
            }
        }
        return $min;
    }

    public static function Count($a) {
        return count($a);
    }

    public static function Avg($a, $countLimit = false) {
        if (!$a) {
            return 0;
        }

        // если в массиве элементов меньше чем нужно - добавляем нулей,
        // чтобы правильно расчитать медиану
        if ($countLimit > 0 && $countLimit > count($a)) {
            $diff = $countLimit - count($a);

            for ($j = 1; $j <= $diff; $j++) {
                $a[] = 0;
            }
        }

        $cnt = 0;
        $sum = 0;
        foreach ($a as $x) {
            $sum += $x;
            $cnt ++;
        }

        return $sum / $cnt;
    }

    public static function Sum($a) {
        if (!$a) {
            return 0;
        }

        $sum = 0;
        foreach ($a as $x) {
            $sum += $x;
        }

        return $sum;
    }

    /**
     * Calculate array median
     *
     * @param $a
     * @return float
     */
    public static function Median($a, $countLimit = false) {
        if (!$a) {
            return 0;
        }

        $a = array_values($a);

        // если в массиве элементов меньше чем нужно - добавляем нулей,
        // чтобы правильно расчитать медиану
        if ($countLimit > 0 && $countLimit > count($a)) {
            $diff = $countLimit - count($a);

            for ($j = 1; $j <= $diff; $j++) {
                $a[] = 0;
            }
        }

        $count = count($a);

        if (!$count) {
            return null;
        }

        sort($a);

        $half = floor($count / 2);
        if ($count % 2) {
            return $a[$half];
        } else {
            return ($a[$half - 1] + $a[$half]) / 2;
        }
    }

}