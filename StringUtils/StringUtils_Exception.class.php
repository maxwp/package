<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * @author FreeFox
 * @copyright WebProduction
 * @package StringUtils
 */
class StringUtils_Exception extends Exception {

    public function __construct($message = '', $code = 0) {
        parent::__construct($message, $code);
    }

}