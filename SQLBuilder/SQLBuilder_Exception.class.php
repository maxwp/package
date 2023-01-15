<?php
class SQLBuilder_Exception extends Exception {

    public function __construct($message, $code = 0) {
        parent::__construct('SQLBuilder: '.$message, $code);
    }

}
