<?php
/**
 * Eventic Engine
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 * @copyright WebProduction
 * @package EE
 */

// задаем локаль по умолчанию
setlocale(LC_ALL, 'en_EN.utf8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding("UTF-8");
}

// fix for Mac OS X PHP 5.3 default
@date_default_timezone_set(date_default_timezone_get());

// подключаем все классы Engine
$path = __DIR__.'/';
ClassLoader::Get()->registerClass($path.'EE_Exception.class.php');
ClassLoader::Get()->registerClass($path.'EE.class.php');
ClassLoader::Get()->registerClass($path.'EE_Smarty.class.php');
ClassLoader::Get()->registerClass($path.'EE_IRequest.class.php');
ClassLoader::Get()->registerClass($path.'EE_Request.class.php');
ClassLoader::Get()->registerClass($path.'EE_IProcessable.interface.php');
ClassLoader::Get()->registerClass($path.'EE_Content.class.php');
ClassLoader::Get()->registerClass($path.'EE_Response.class.php');
ClassLoader::Get()->registerClass($path.'EE_IRouting.class.php');
ClassLoader::Get()->registerClass($path.'EE_Routing.class.php');

// Engine events
ClassLoader::Get()->registerClass($path.'EE_Event_ContentProcess.class.php');
ClassLoader::Get()->registerClass($path.'EE_Event_ContentRender.class.php');
ClassLoader::Get()->registerClass($path.'EE_Event_Exception.class.php');

// регистрация событий которые понимает Eventic Engine
// @todo если я не юзаю eventic - то такие вызовы не нужны
Events::Get()->addEvent('EE:content.process:before', 'EE_Event_ContentProcess');
Events::Get()->addEvent('EE:content.process:after', 'EE_Event_ContentProcess');
Events::Get()->addEvent('EE:content.render:before', 'EE_Event_ContentRender');
Events::Get()->addEvent('EE:content.render:after', 'EE_Event_ContentRender');
Events::Get()->addEvent('EE:routing:before', 'Events_Event');
Events::Get()->addEvent('EE:routing:after', 'Events_Event');
Events::Get()->addEvent('EE:execute:before', 'Events_Event');
Events::Get()->addEvent('EE:execute:exception', 'EE_Event_Exception');
Events::Get()->addEvent('EE:execute:after', 'Events_Event');