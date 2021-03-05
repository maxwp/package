<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2016 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Engine
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
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

// подключаем Storage
// (необходим для кеширования)
include_once(dirname(__FILE__).'/../Storage/include.php');

// подключем Events
include_once(dirname(__FILE__).'/../Events/include.php');

// подключаем все классы Engine
$path = __DIR__.'/';
ClassLoader::Get()->registerClass($path.'Engine_Exception.class.php');
ClassLoader::Get()->registerClass($path.'Engine.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Smarty.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Request.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Request2.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Content.class.php');
ClassLoader::Get()->registerClass($path.'Engine_ContentDriver.class.php');
ClassLoader::Get()->registerClass($path.'Engine_IRequest.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Response.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Routing.class.php');

// Engine events
ClassLoader::Get()->registerClass($path.'Engine_Event_ContentProcess.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Event_ContentRender.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Event_Exception.class.php');

// ClassLoader::Get()->registerClass($path.'content/engine_include.php');

// инициализируем движок, пусть он подгрузит все что ему нужно,
// в том числе файлы engine.mode.php, engine.config.php, структуру contents
Engine::Initialize();