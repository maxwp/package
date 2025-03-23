<?php
/**
 * Eventic Engine
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 * @copyright WebProduction
 * @package EE
 */

ClassLoader::Get()->registerClass(__DIR__.'/EE_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_Typing.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_Smarty.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_IRequest.interface.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_IResponse.interface.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_IContent.interface.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_IRouting.interface.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_Request.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_RequestFile.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_Response.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_Routing.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_RequestCLI.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_ResponseCLI.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_RoutingCLI.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_AContent.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_AContentCli.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_AContentSmarty.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_Network.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_RequestRemote.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_RoutingRemote.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_PrintCli.class.php');

// Engine events
ClassLoader::Get()->registerClass(__DIR__.'/EE_Event_ContentProcess.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_Event_ContentRender.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE_Event_Exception.class.php');

// default contents
ClassLoader::Get()->registerClass(__DIR__.'/content/ee500.class.php');
