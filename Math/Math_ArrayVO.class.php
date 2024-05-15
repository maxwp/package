<?php
class Math_ArrayVO extends ArrayObject {

    public function max() {
        return Math_Array::Max($this);
    }

    public function min() {
        return Math_Array::Min($this);
    }

    public function avg() {
        return Math_Array::Avg($this);
    }

    public function sum() {
        return Math_Array::Sum($this);
    }

    public function median($countLimit = false) {
        return Math_Array::Median($this, $countLimit);
    }

}