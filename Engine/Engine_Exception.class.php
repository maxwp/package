<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * @author DFox
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package Engine
 */
class Engine_Exception extends Exception {

    public function __construct($message, $code = 0) {
        parent::__construct('Engine: '.$message, $code);
    }

    public function __toString() {
        if (class_exists('DebugException')) {
            return DebugException::Display($this, __CLASS__);
        }

        return parent::__toString();
    }

}
