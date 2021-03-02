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
class EE_Exception extends Exception {

    public function __construct($message, $code = 0) {
        parent::__construct('EE: '.$message, $code);
    }

}
