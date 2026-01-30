<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Менеджер соединений.
 * MySQL, SMTP, POP, IMAP, memcache, APC, memcached, SOAPs, etc.
 */
class Connection extends Pattern_ARegistrySingleton {

    /**
     * @param $key
     * @return Connection_IConnection
     * @throws Connection_Exception
     */
    public static function Get(string $key) {
        return self::_Get($key);
    }

    /**
     * @param $key
     * @return Connection_MySQLi
     * @throws Connection_Exception
     */
    public static function GetMySQL(string $key = 'mysql') {
        return self::_Get($key);
    }

    /**
     * @param $key
     * @return Connection_RDS
     * @throws Connection_Exception
     */
    public static function getRDS(string $key = 'rds') {
        return self::_Get($key);
    }

    /**
     * @param $key
     * @return Connection_Redis
     * @throws Connection_Exception
     */
    public static function GetRedis(string $key = 'redis') {
        return self::_Get($key);
    }

    /**
     * @param $key
     * @return Connection_Memcached
     * @throws Connection_Exception
     */
    public static function GetMemcached(string $key = 'memcached') {
        return self::_Get($key);
    }

    public static function Register(string $key, $object) {
        self::_Register($key, $object, 'Connection_IConnection');
    }

    protected static $_ExceptionClass = Connection_Exception::class;

}