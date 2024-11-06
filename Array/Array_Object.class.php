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
            if ($max === false || $x > $max) {
                $max = $x;
            }
        }
        return $max;
    }

    public function min() {
        $min = false;
        foreach ($this as $x) {
            if ($min === false || $x < $min) {
                $min = $x;
            }
        }
        return $min;
    }

    public function avg($countLimit = false) {
        $cnt = $this->count();
        if (!$cnt) {
            return 0;
        }

        $a = $this;

        // если в массиве элементов меньше чем нужно - добавляем нулей,
        // чтобы правильно расчитать медиану
        if ($countLimit > 0 && $countLimit > $cnt) {
            $diff = $countLimit - $cnt;

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

    public function sum() {
        if (!$this->count()) {
            return 0;
        }

        $sum = 0;
        foreach ($this as $x) {
            $sum += $x;
        }

        return $sum;
    }

    public function median($countLimit = false) {
        $cnt = $this->count();

        if (!$cnt) {
            return 0;
        }

        $a = array_values($this->getArrayCopy());

        // если в массиве элементов меньше чем нужно - добавляем нулей,
        // чтобы правильно расчитать медиану
        if ($countLimit > 0 && $countLimit > $cnt) {
            $diff = $countLimit - $cnt;

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

}