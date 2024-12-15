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

    const ARG_SOURCE_FILE = 'file';
    const ARG_SOURCE_GET = 'get';
    const ARG_SOURCE_POST = 'post';
    const ARG_SOURCE_PUT = 'put';
    const ARG_SOURCE_DELETE = 'delete';
    const ARG_SOURCE_CLI = 'cli';

}