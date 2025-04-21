<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Storage.
 *
 * Allows you to put and get any data from the universal storage interface.
 *
 * Data can be put to the storage with a specified key
 * or the key will be built as a md5-hash for data (if possible).
 *
 * It is possible to use handlers memcached, redis, shmop, array, etc.
 *
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package Storage
 */
class Storage extends Pattern_ARegistrySingleton {

    /**
     * Get storage by key.
     *
     * Получить хранилище.
     *
     * Возможно хранение нескольких хранилищ по разным ключам ($storageKey)
     * В случае использования ключа - хранилище должен быть инициирован явно:
     *
     * @return Storage_IHandler
     */
    public static function Get($key) {
        return self::_Get($key);
    }

    /**
     * @param string $key
     * @return Storage_Redis
     * @throws Storage_Exception
     */
    public static function GetRedis(string $key = 'redis') {
        return self::_Get($key);
    }

    /**
     * @param string $key
     * @return Storage_Memcached
     * @throws Storage_Exception
     */
    public static function GetMemcached(string $key = 'memcached') {
        return self::_Get($key);
    }

    /**
     * @param string $key
     * @return Storage_Shmop
     * @throws Storage_Exception
     */
    public static function GetShmop(string $key = 'shmop') {
        return self::_Get($key);
    }

    /**
     * Initialize new storage.
     *
     * @param Storage_IHandler $object
     * @param string $key
     */
    public static function Register(string $key, $object) {
        self::_Register($key, $object, 'Storage_IHandler');
    }

    protected static $_ExceptionClass = Storage_Exception::class;

}