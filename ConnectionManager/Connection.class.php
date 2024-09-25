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
class Connection {

    /**
     * @param $connectionKey
     * @return Connection_IConnection
     * @throws Connection_Exception
     */
    public static function Get($connectionKey) {
        // @todo как возвращать типизированные коннекторы?

        if (!$connectionKey) {
            throw new Connection_Exception("Empty connection key");
        }

        if (!isset(self::$_InstanceArray[$connectionKey])) {
            throw new Connection_Exception("Connection with key '{$connectionKey}' not found, please, call Initialize() before.");
        }

        return self::$_InstanceArray[$connectionKey];
    }

    public static function Initialize($connectionKey, Connection_IConnection $handler) {
        // @todo как инициализировать типизированные коннкторы?

        if (!$connectionKey) {
            throw new Connection_Exception("Empty connection key");
        }

        self::$_InstanceArray[$connectionKey] = $handler;
        return $handler;
    }

    public static function Reset() {
        self::$_InstanceArray = array();
    }

    private function __construct() {

    }

    private function __clone() {

    }

    /**
     * @var array<Connection_IConnection>
     */
    private static $_InstanceArray = [];

}