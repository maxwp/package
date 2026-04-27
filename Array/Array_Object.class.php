<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class Array_Object extends ArrayObject {

    public function __construct($a = array()) {
        if (!$a) {
            $a = [];
        }
        parent::__construct($a);
    }

    public function max() {
        $max = false;
        foreach ($this as $x) {
            if ($x > $max || $max === false) {
                $max = $x;
            }
        }
        return $max;
    }

    public function min() {
        $min = false;
        foreach ($this as $x) {
            if ($x < $min || $min === false) {
                $min = $x;
            }
        }
        return $min;
    }

    public function avg() {
        $cnt = $this->count();
        if (!$cnt) {
            return 0;
        }

        $cnt = 0;
        $sum = 0;
        foreach ($this as $x) {
            $sum += $x;
            $cnt ++;
        }

        return $sum / $cnt;
    }

    public function sum() {
        if (!$this->count()) {
            return 0;
        }

        // быстрее в 5 раз, но есть косяки с типизацией строк
        //return array_sum($this->getArrayCopy());

        $sum = 0;
        foreach ($this as $x) {
            $sum += $x;
        }

        return $sum;
    }

    public function median() {
        $cnt = $this->count();

        if (!$cnt) {
            return 0;
        }

        $a = array_values($this->getArrayCopy()); // @todo wtf?
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

    public function variance() {
        $n = $this->count();
        if ($n === 0) {
            return 0;
        }

        $mean = $this->avg(); // Среднее значение
        $sumOfSquares = 0;

        $a = $this->getArrayCopy();
        foreach ($a as $num) {
            $sumOfSquares += pow($num - $mean, 2); // Квадраты разностей
        }

        return $sumOfSquares / $n; // Дисперсия
    }

    public function stdDeviation() {
        return sqrt($this->variance());
    }

    public function quantile($percentile) {
        if (!$this->count()) {
            return 0;
        }
        $array = $this->getArrayCopy();
        sort($array);
        $index = ($percentile / 100) * (count($array) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        if ($lower == $upper) {
            return $array[$lower];
        } else {
            return $array[$lower] + ($array[$upper] - $array[$lower]) * ($index - $lower);
        }
    }

    /**
     * сумма элементов в хвосте хвост (>= квантиля)
     * сюда надо передавать сразу значение квантиля
     *
     * @param $quantileValue
     * @return int
     */
    public function tailSum($quantileValue) {
        $sum = 0;
        foreach ($this as $value) {
            if ($value >= $quantileValue) {
                $sum += $value;
            }
        }
        return $sum;
    }

    /**
     * количество элементов в хвосте хвост (>= квантиля)
     * сюда надо передавать сразу значение квантиля
     *
     * @param $quantileValue
     * @return int
     */
    public function tailCount($quantileValue) {
        $count = 0;
        foreach ($this as $value) {
            if ($value >= $quantileValue) {
                $count ++;
            }
        }
        return $count;
    }

    /**
     * Coefficient of Variation (CV = stddev / mean)
     *
     *  CV < 0.5 - почти ровный поток
     *  0.5 – 1 - норм
     *  >1 - уже нестабильный
     *  >2 - трэш / burst
     *
     * @return float
     */
    public function cv() {
        $avg = $this->avg();
        if (!$avg) {
            return false;
        }
        return $this->stdDeviation() / $avg;
    }

    /**
     * Фильтрация выбросов (удаляет экстремальные значения)
     *
     * @param float $threshold
     * @return array
     */
    public function filterOutliers($threshold) {
        $array = $this->getArrayCopy();
        $cnt = count($array);
        if (!$cnt) {
            return [];
        }

        $mean = array_sum($array) / $cnt;
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $array)) / $cnt;
        $std_dev = sqrt($variance);

        return array_filter($array, fn($x) => abs($x - $mean) < $threshold * $std_dev);
    }

}