<?php
class Math_Array {

    public static function Max($a) {
        return max($a);
    }

    public static function Min($a) {
        return min($a);
    }

    public static function Count($a) {
        return count($a);
    }

    public static function Avg($a) {
        if (!$a) {
            return 0;
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
        $a = array_values($a);
        sort($a);

        // если в массиве элементов меньше чем нужно - добавляем нулей,
        // чтобы правильно расчитать медиану
        if ($countLimit > 0 && $countLimit > count($a)) {
            $diff = $countLimit - count($a);

            for ($j = 1 ; $j <= $diff; $j++) {
                $a[] = 0;
            }
        }

        $count = count($a);

        if (!$count) {
            return null;
        }

        $half = floor($count / 2);
        if ($count % 2) {
            return $a[$half];
        } else {
            return ($a[$half - 1] + $a[$half]) / 2;
        }
    }

}