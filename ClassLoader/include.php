<?php
// fix for Mac OS X PHP 5.3 default
@date_default_timezone_set(date_default_timezone_get());

include_once(__DIR__.'/ClassLoader.class.php');
include_once(__DIR__.'/ClassLoader_Exception.class.php');