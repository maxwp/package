<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Storage exception
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package Storage
 */
class WebSocket_Exception extends Exception {

    public function __construct($message = '', $code = 0) {
        parent::__construct($message, $code);
    }

}