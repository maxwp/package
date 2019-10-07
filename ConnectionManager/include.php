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

if (class_exists('PackageLoader')) {
    PackageLoader::Get()->registerPHPClass(dirname(__FILE__).'/ConnectionManager.class.php');
    PackageLoader::Get()->registerPHPClass(dirname(__FILE__).'/ConnectionManager_IConnection.class.php');
    PackageLoader::Get()->registerPHPClass(dirname(__FILE__).'/ConnectionManager_IDatabaseAdapter.class.php');
    PackageLoader::Get()->registerPHPClass(dirname(__FILE__).'/ConnectionManager_Exception.class.php');
    PackageLoader::Get()->registerPHPClass(dirname(__FILE__).'/ConnectionManager_MySQLi.class.php');
    PackageLoader::Get()->registerPHPClass(dirname(__FILE__).'/ConnectionManager_PgSQL.class.php');
    PackageLoader::Get()->registerPHPClass(dirname(__FILE__).'/ConnectionManager_PDO.class.php');
    PackageLoader::Get()->registerPHPClass(dirname(__FILE__).'/ConnectionManager_SphinxQL.class.php');
    PackageLoader::Get()->registerPHPClass(dirname(__FILE__).'/ConnectionManager_GearmanClient.class.php');
} else {
    include_once(dirname(__FILE__).'/ConnectionManager.class.php');
    include_once(dirname(__FILE__).'/ConnectionManager_Exception.class.php');
    include_once(dirname(__FILE__).'/ConnectionManager_IConnection.class.php');
    include_once(dirname(__FILE__).'/ConnectionManager_IDatabaseAdapter.class.php');
    include_once(dirname(__FILE__).'/ConnectionManager_MySQLi.class.php');
    include_once(dirname(__FILE__).'/ConnectionManager_PgSQL.class.php');
    include_once(dirname(__FILE__).'/ConnectionManager_PDO.class.php');
    include_once(dirname(__FILE__).'/ConnectionManager_SphinxQL.class.php');
    include_once(dirname(__FILE__).'/ConnectionManager_GearmanClient.class.php');
}