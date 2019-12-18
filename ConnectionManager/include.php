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

ClassLoader::Get()->registerClass(dirname(__FILE__).'/ConnectionManager.class.php');
ClassLoader::Get()->registerClass(dirname(__FILE__).'/ConnectionManager_IConnection.class.php');
ClassLoader::Get()->registerClass(dirname(__FILE__).'/ConnectionManager_IDatabaseAdapter.class.php');
ClassLoader::Get()->registerClass(dirname(__FILE__).'/ConnectionManager_Exception.class.php');
ClassLoader::Get()->registerClass(dirname(__FILE__).'/ConnectionManager_MySQLi.class.php');
ClassLoader::Get()->registerClass(dirname(__FILE__).'/ConnectionManager_PgSQL.class.php');
ClassLoader::Get()->registerClass(dirname(__FILE__).'/ConnectionManager_PDO.class.php');
ClassLoader::Get()->registerClass(dirname(__FILE__).'/ConnectionManager_SphinxQL.class.php');
ClassLoader::Get()->registerClass(dirname(__FILE__).'/ConnectionManager_GearmanClient.class.php');