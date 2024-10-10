<?php
/**
 * Eventic Engine
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 * @copyright WebProduction
 * @package EE
 */

// подключаем все классы Engine
// тут важно подключить их в правильном порядкен
$path = __DIR__.'/';
ClassLoader::Get()->registerClass($path.'EE_Exception.class.php');
ClassLoader::Get()->registerClass($path.'EE.class.php');
ClassLoader::Get()->registerClass($path.'EE_Smarty.class.php');
ClassLoader::Get()->registerClass($path.'EE_IRequest.interface.php');
ClassLoader::Get()->registerClass($path.'EE_IResponse.interface.php');
ClassLoader::Get()->registerClass($path.'EE_IContent.interface.php');
ClassLoader::Get()->registerClass($path.'EE_IRouting.interface.php');
ClassLoader::Get()->registerClass($path.'EE_Request.class.php');
ClassLoader::Get()->registerClass($path.'EE_Response.class.php');
ClassLoader::Get()->registerClass($path.'EE_Routing.class.php');
ClassLoader::Get()->registerClass($path.'EE_RequestCLI.class.php');
ClassLoader::Get()->registerClass($path.'EE_ResponseCLI.class.php');
ClassLoader::Get()->registerClass($path.'EE_RoutingCLI.class.php');
ClassLoader::Get()->registerClass($path.'EE_AContent.class.php');
ClassLoader::Get()->registerClass($path.'EE_AContentCli.class.php');
ClassLoader::Get()->registerClass($path.'EE_AContentSmarty.class.php');
ClassLoader::Get()->registerClass($path.'EE_Network.class.php');
ClassLoader::Get()->registerClass($path.'EE_RequestRemote.class.php');
ClassLoader::Get()->registerClass($path.'EE_RoutingRemote.class.php');

// Engine events
ClassLoader::Get()->registerClass($path.'EE_Event_ContentProcess.class.php');
ClassLoader::Get()->registerClass($path.'EE_Event_ContentRender.class.php');
ClassLoader::Get()->registerClass($path.'EE_Event_Exception.class.php');

// default contents
ClassLoader::Get()->registerClass($path.'content/ee500.class.php');
