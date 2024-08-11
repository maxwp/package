<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @package Storage
 * @copyright WebProduction
 */

ClassLoader::Get()->registerClass(__DIR__.'/Storage.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage_IHandler.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage_HandlerArray.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage_HandlerCacheFiles.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage_HandlerFiles.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage_HandlerMemcached.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage_HandlerMemSock.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage_HandlerRedis.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage_HandlerSession.class.php');