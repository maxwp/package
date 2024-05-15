<?php
class Math_ArrayVO extends ArrayObject {

    public function max($a) {
        return Math_Array::Max($a);
    }

    public function min($a) {
        return Math_Array::Min($a);
    }

    public function avg($a) {
        return Math_Array::Avg($a);
    }

    public function sum($a) {
        return Math_Array::Sum($a);
    }

    /**
     * Calculate array median
     *
     * @param $a
     * @return float
     */
    public function median($a, $countLimit = false) {
        return Math_Array::Median($a, $countLimit);
    }

}