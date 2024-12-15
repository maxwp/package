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

    // @todo шо тут делать?
    //public function getURL();

    //public function getHost();

    public function getArgumentArray();

    public function getArgument($key, $argType = false);

    public function getArgumentSecure($key, $argType = false);

    //public function getCOOKIEArray();

    const ARG_TYPE_FILE = 'file';
    const ARG_TYPE_GET = 'get';
    const ARG_TYPE_POST = 'post';
    const ARG_TYPE_PUT = 'put';
    const ARG_TYPE_DELETE = 'delete';

}