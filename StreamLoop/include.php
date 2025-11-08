<?php
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_AHandler.class.php'); // @todo rename
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_HTTPS.class.php'); // @todo consts + A
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_HTTPS_ICallback.interface.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_AUDP.class.php'); // @todo rename
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_UDP.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_UDP_DrainForward.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_UDP_DrainBackward.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_UDP_ICallback.interface.php');
include __DIR__.'/StreamLoop_WebSocket_Const.class.php';
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_AWebSocket.class.php'); // @todo rename
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop_ATimer.class.php'); // @todo rename
