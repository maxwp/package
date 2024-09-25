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
ClassLoader::Get()->registerClass(__DIR__.'/Storage_Array.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage_Memcached.class.php');
//ClassLoader::Get()->registerClass(__DIR__.'/Storage_MemSock.class.php'); // @todo
ClassLoader::Get()->registerClass(__DIR__.'/Storage_Redis.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage_Shmop.class.php');