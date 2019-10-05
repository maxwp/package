<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Менеджер соединений.
 * MySQL, SMTP, POP, IMAP, memcache, APC, memcached, SOAPs, etc.
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package ConnectionManager
 */
class ConnectionManager {

    /**
     * Получить соеденение с базой данных
     *
     * @param string $key
     * @return ConnectionManager_IDatabaseAdapter
     */
    public function getConnectionDatabase($key = 'database-default') {
        $connection = $this->getConnection($key);
        if ($connection instanceof ConnectionManager_IDatabaseAdapter) {
            return $connection;
        }
        throw new ConnectionManager_Exception("No database connection with key '{$key}'");
    }

    /**
     * Добавить соеденение с базой данных.
     * По умолчанию выставлен ключ database-default.
     *
     * @param ConnectionManager_IDatabaseAdapter $connection
     * @param string $key
     * @param bool $force
     */
    public function addConnectionDatabase(ConnectionManager_IDatabaseAdapter $connection, $key = 'database-default', $force = false) {
        $this->addConnection($connection, $key, $force);
    }

    /**
     * Добавить коннектор.
     * Если ключ не указать, будет использован передаваемый класс
     *
     * @param ConnectionManager_IConnection $handler
     * @param string $key
     * @param bool $force Замещять соеденение новым, в случае дублирования
     */
    public function addConnection(ConnectionManager_IConnection $connection, $key = false, $force = false) {
        if (!$connection) {
            throw new ConnectionManager_Exception("Connection is empty or null");
        }

        if (!$key) {
            $key = get_class($connection);
        }

        // проверяем дубликат соеденения
        if (!$force && !empty($this->_connectionsArray[$key])) {
            // если соеденение с таким ключем есть,
            // и не включен force-режим, то выдаем ошибку:
            throw new ConnectionManager_Exception("Connection with key '{$key}' already exists. Use parameter force=true to replace it.");
        }

        // записываем соеденение
        $this->_connectionsArray[$key] = $connection;
    }

    /**
     * @return ConnectionManager_IConnection
     * @param string $key
     */
    public function getConnection($key) {
        if (!empty($this->_connectionsArray[$key])) {
            return $this->_connectionsArray[$key];
        }
        throw new ConnectionManager_Exception("Connection with key '{$key}' not found");
    }

    /**
     * Получить MySQL-соеденение.
     * Если ключ не указать, будет выдан первый найденный MySQL-коннекшн
     *
     * @param string $key
     * @return ConnectionManager_MySQL
     */
    public function getConnectionMySQL($key = false) {
        if (!$key) {
            $key = 'database-default';
        }

        try {
            $connection = $this->getConnectionDatabase($key);
            if ($connection instanceof ConnectionManager_MySQL) {
                return $connection;
            }
            if ($connection instanceof ConnectionManager_MySQLi) {
                return $connection;
            }
        } catch (Exception $e) {

        }

        // поиск любого соединения MySQL
        foreach ($this->_connectionsArray as $connection) {
        	if ($connection instanceof ConnectionManager_MySQL) {
                return $connection;
            }
            if ($connection instanceof ConnectionManager_MySQLi) {
                return $connection;
            }
        }

        throw new ConnectionManager_Exception('Any MySQL connection not found');
    }

    /**
     * @return ConnectionManager
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    /**
     * Очистить все соеденения (адаптеры)
     */
    public function clearConnections() {
        $this->_connectionsArray = array();
    }

    private function __construct() {

    }

    private function __clone() {

    }

    /**
     * @var ConnectionManager
     */
    private static $_Instance = null;

    /**
     * @var array
     */
    private $_connectionsArray = array();

}