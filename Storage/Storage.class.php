<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
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
class Storage {

    /**
     * Get storage by key.
     *
     * Получить хранилище.
     *
     * Возможно хранение нескольких хоанилищ по разным ключам ($storageKey)
     * В случае использования ключа - хранилище должен быть инициирован явно:
     *
     * @see Initialize()
     *
     * @package string $storageKey
     * @return Storage_IHandler
     */
    public static function Get($storageKey) {
        if (!$storageKey) {
            throw new Storage_Exception("Empty storage key");
        }

        if (!isset(self::$_InstanceArray[$storageKey])) {
            throw new Storage_Exception("Storage with key '{$storageKey}' not found, please, call Initialize() before.");
        }

        return self::$_InstanceArray[$storageKey];
    }

    /**
     * Initialize new storage.
     *
     * Инициировать хранилище. Передается первый handler.
     * Далее через addHandler() можно добавлять еще обработчики.
     *
     * @see addHandler()
     *
     * @param Storage_IHandler $handler
     * @param string $storageKey
     * @return Storage_IHandler
     */
    public static function Initialize($storageKey, Storage_IHandler $handler) {
        if (!$storageKey) {
            throw new Storage_Exception("Empty storage key");
        }

        self::$_InstanceArray[$storageKey] = $handler;
        return $handler;
    }

    /**
     * Remove all storage pull
     * Очистить весь реестр Storage-ей
     *
     * @access public
     * @static
     */
    public static function Reset() {
        self::$_InstanceArray = array();
    }

    private function __construct() {
        // singleton
    }

    private function __clone() {

    }

    /**
     * @var Storage_IHandler[] $_InstanceArray
     */
    private static $_InstanceArray = [];

}