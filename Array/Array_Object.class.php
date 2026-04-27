<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class Array_Object extends ArrayObject {

    public function __construct($a = []) {
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

    /**
     * Average (mean)
     *
     * @return float|int
     */
    public function average() {
        $sum = $this->sum();
        if (!$sum) {
            return 0;
        }

        $cnt = $this->count();
        if (!$cnt) {
            return 0;
        }

        return $sum / $cnt;
    }

    /**
     * Sum
     *
     * @return int|float
     */
    public function sum() {
        // если элементов нет - то перебирать ничего не нужно
        if (!$this->count()) {
            return 0;
        }

        $sum = 0;
        foreach ($this as $x) {
            $sum += $x;
        }

        return $sum;
    }

    /**
     * Медиана (p50)
     *
     * @return float|int|mixed
     */
    public function median() {
        $cnt = $this->count();
        if (!$cnt) {
            return 0;
        }

        $a = $this->_getArrayCopySorted();

        $half = $cnt >> 1; // хитрожопое супер быстрое деление на 2
        if ($cnt % 2) {
            return $a[$half];
        } else {
            return ($a[$half - 1] + $a[$half]) / 2;
        }
    }

    public function variance() {
        $cnt = $this->count();
        if (!$cnt) {
            return 0;
        }

        $mean = $this->average(); // Среднее значение

        $sumOfSquares = 0; // сумма квадратов разностей
        foreach ($this as $num) {
            $sumOfSquares += pow($num - $mean, 2); // Квадраты разностей
        }

        return $sumOfSquares / $cnt; // дисперсия
    }

    /**
     * Std. Deviation
     *
     * @return float
     */
    public function stdDeviation() {
        return sqrt($this->variance());
    }

    /**
     * Quantille
     *
     * @param $percentile
     * @return float|int|mixed
     */
    public function quantile($percentile) {
        $cnt = $this->count();
        if (!$cnt) {
            return 0;
        }
        $a = $this->_getArrayCopySorted();
        $index = ($percentile / 100) * ($cnt - 1);
        $lower = floor($index);
        $upper = ceil($index);
        if ($lower == $upper) {
            return $a[$lower];
        } else {
            return $a[$lower] + ($a[$upper] - $a[$lower]) * ($index - $lower);
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
        $cnt = 0;
        foreach ($this as $value) {
            if ($value >= $quantileValue) {
                $cnt ++;
            }
        }
        return $cnt;
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
    public function cv() {
        $avg = $this->average();
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
        if (!$this->count()) {
            return [];
        }

        $mean = $this->average();
        $stddev = $this->stdDeviation();

        return array_filter($this->getArrayCopy(), fn($x) => abs($x - $mean) < $threshold * $stddev);
    }

    private function _getArrayCopySorted() {
        $a = $this->getArrayCopy();
        sort($a);
        return $a;
    }

}