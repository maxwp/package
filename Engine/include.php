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

// проверяем, задан ли project path
PackageLoader::Get()->getProjectPath();

// подключаем Storage
// (необходим для кеширования)
PackageLoader::Get()->import('Storage');

// подключем Events
PackageLoader::Get()->import('Events');

// подключаем все классы Engine
$path = __DIR__.'/';
PackageLoader::Get()->registerPHPClass($path.'Engine_Exception.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_Smarty.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_ContentDataSource.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_Request.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_Content.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_Class.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_ContentDriver.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_IURLParser.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_URLParser.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_ILinkMaker.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_ALinkMaker.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_LinkMaker.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_HTMLHead.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_Response.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_Auth.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_Generator.class.php');

// Engine events
PackageLoader::Get()->registerPHPClass($path.'Engine_Event_ContentProcess.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_Event_ContentRender.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_Event_Exception.class.php');

// cache
PackageLoader::Get()->registerPHPClass($path.'Engine_ACacheModifier.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_CacheModifierURL.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_CacheModifierUser.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_CacheModifierHost.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_CacheModifierLanguage.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_CacheModifierNoAuth.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_CacheModifierAuthLogin.class.php');
PackageLoader::Get()->registerPHPClass($path.'Engine_Cache.class.php');