<?php
class error404 extends Engine_Class {

    public function process() {
        header('HTTP/1.0 404 Not Found');
    }

}