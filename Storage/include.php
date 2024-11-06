<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
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