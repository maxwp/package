<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Подключение ConnectionManager
 *
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @package ConnectionManager
 * @copyright WebProduction
 */

ClassLoader::Get()->registerClass(__DIR__.'/Connection.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection_IConnection.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection_IDatabaseAdapter.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection_MySQLi.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection_PgSQL.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection_PDO.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection_Redis.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection_Memcached.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection_SocketUDP.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection_WebSocket.class.php');