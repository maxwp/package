<?php

// default locale
setlocale(LC_ALL, 'en_EN.utf8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding("UTF-8");
}

// fix for Mac OS X PHP 5.3 default
@date_default_timezone_set(date_default_timezone_get());

include(__DIR__.'/ClassLoader/include.php');
include(__DIR__.'/Checker/include.php');
include(__DIR__.'/Connection/include.php');
include(__DIR__.'/DateTime/include.php');
include(__DIR__.'/Events/include.php');
include(__DIR__.'/EE/include.php');
include(__DIR__.'/ImageProcessor/include.php');
//include(__DIR__.'/MailQue/include.php');
include(__DIR__.'/Smarty/include.php');
include(__DIR__.'/SQLBuilder/include.php');
include(__DIR__.'/Storage/include.php');
include(__DIR__.'/StringUtils/include.php');
include(__DIR__.'/TextProcessor/include.php');
include(__DIR__.'/Array/include.php');
include(__DIR__.'/Cron/include.php');
include(__DIR__.'/IPC/include.php');