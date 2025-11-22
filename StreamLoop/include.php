<?php
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_Handler_Abstract.class.php');
include __DIR__.'/StreamLoop_HTTPS_Const.class.php';
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_HTTPS.class.php'); // @todo A + rename
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_HTTPS_ICallback.interface.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_UDP_Abstract.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_UDP.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_UDP_DrainForward.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_UDP_DrainBackward.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_UDP_DrainBackward_Abstract.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_UDP_ICallback.interface.php');
include __DIR__.'/StreamLoop_WebSocket_Const.class.php';
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_WebSocket_Abstract.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_Timer_Abstract.class.php');
