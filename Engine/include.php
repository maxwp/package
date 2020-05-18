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
ClassLoader::Get()->registerClass($path.'Engine_ContentDataSource.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Request.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Content.class.php');
ClassLoader::Get()->registerClass($path.'Engine_ContentDriver.class.php');
ClassLoader::Get()->registerClass($path.'Engine_IURLParser.class.php');
ClassLoader::Get()->registerClass($path.'Engine_URLParser.class.php');
ClassLoader::Get()->registerClass($path.'Engine_ILinkMaker.class.php');
ClassLoader::Get()->registerClass($path.'Engine_ALinkMaker.class.php');
ClassLoader::Get()->registerClass($path.'Engine_LinkMaker.class.php');
ClassLoader::Get()->registerClass($path.'Engine_HTMLHead.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Response.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Auth.class.php');

// Engine events
ClassLoader::Get()->registerClass($path.'Engine_Event_ContentProcess.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Event_ContentRender.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Event_Exception.class.php');

// cache
ClassLoader::Get()->registerClass($path.'Engine_ACacheModifier.class.php');
ClassLoader::Get()->registerClass($path.'Engine_CacheModifierURL.class.php');
ClassLoader::Get()->registerClass($path.'Engine_CacheModifierUser.class.php');
ClassLoader::Get()->registerClass($path.'Engine_CacheModifierHost.class.php');
ClassLoader::Get()->registerClass($path.'Engine_CacheModifierLanguage.class.php');
ClassLoader::Get()->registerClass($path.'Engine_CacheModifierNoAuth.class.php');
ClassLoader::Get()->registerClass($path.'Engine_CacheModifierAuthLogin.class.php');
ClassLoader::Get()->registerClass($path.'Engine_Cache.class.php');