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

    // @todo отказаться от typing внутри EE_IRequest
    public function getArgument($key, $source = false, $type = false);

    //public function getCOOKIEArray();

    const ARG_SOURCE_FILE = 'file';
    const ARG_SOURCE_GET = 'get';
    const ARG_SOURCE_POST = 'post';
    const ARG_SOURCE_PUT = 'put'; // @todo
    const ARG_SOURCE_DELETE = 'delete'; // @todo
    const ARG_SOURCE_CLI = 'cli';
    const ARG_SOURCE_INTERNAL = 'internal';

}