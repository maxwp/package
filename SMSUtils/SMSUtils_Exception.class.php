<?php
/**
 * @author Maxim Miroshnichenko
 * @copyright WebProduction
 * @package SMSUtils
 */
class SMSUtils_Exception extends Exception {

    public function __construct($message = '', $code = 0) {
        parent::__construct($message, $code);
    }

    public function __toString() {
        if (class_exists('DebugException')) {
            return DebugException::Display($this, __CLASS__);
        }

        return parent::__toString();
    }

}