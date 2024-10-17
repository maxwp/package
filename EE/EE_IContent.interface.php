<?php
interface EE_IContent {

    public function process();

    public function render();

    public function getValue($key);

    public function setValue($key, $value);

    public function getValueArray();

    public function reset();

}