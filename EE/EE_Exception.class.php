<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Eventic Exception
 */
class EE_Exception extends Exception {

    public function __construct($message, $code = 0) {
        parent::__construct('EE: '.$message, $code);
    }

}
