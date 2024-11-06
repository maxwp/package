<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Request interface
 */
interface EE_IRequest {

    //public function getURL();

    //public function getHost();

    public function getArgumentArray();

    public function getArgument($key, $argType = false);

    public function getArgumentSecure($key, $argType = false);

    //public function getCOOKIEArray();

}