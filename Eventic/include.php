<?php
/**
 * Engine
 *
 * @author    Maxim Miroshnichenko <max@miroshnichenko.org>
 * @copyright WebProduction
 * @package   Engine
 */

// задаем локаль по умолчанию
setlocale(LC_ALL, 'en_EN.utf8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding("UTF-8");
}

// fix for Mac OS X PHP 5.3 default
@date_default_timezone_set(date_default_timezone_get());

// подключаем ClassLoader
if (!class_exists('ClassLoader')) {
    include_once(__DIR__.'/../ClassLoader/include.php');
}

// подключем Events
include_once(dirname(__FILE__).'/../Events/include.php');

// подключаем все классы Engine
$path = __DIR__.'/';
ClassLoader::Get()->registerClass($path.'EE_Exception.class.php');
ClassLoader::Get()->registerClass($path.'EE.class.php');
ClassLoader::Get()->registerClass($path.'EE_Smarty.class.php');
ClassLoader::Get()->registerClass($path.'EE_IRequest.class.php');
ClassLoader::Get()->registerClass($path.'EE_Request.class.php');
ClassLoader::Get()->registerClass($path.'EE_Content.class.php');
ClassLoader::Get()->registerClass($path.'EE_Response.class.php');
ClassLoader::Get()->registerClass($path.'EE_IRouting.class.php');
ClassLoader::Get()->registerClass($path.'EE_Routing.class.php');

// Engine events
ClassLoader::Get()->registerClass($path.'EE_Event_ContentProcess.class.php');
ClassLoader::Get()->registerClass($path.'EE_Event_ContentRender.class.php');
ClassLoader::Get()->registerClass($path.'EE_Event_Exception.class.php');
