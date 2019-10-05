<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commetcial software; you can not redistribute it and/or
 * modify it under any terms.
 */

/**
 * Events
 *
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package Events
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package Events
 */
class Events_Exception extends Exception {

    public function __construct($message = '', $code = 0) {
        parent::__construct('Events: '.$message, $code);
    }

}