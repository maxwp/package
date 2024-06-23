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
 * It is possible to use handlers APC, memcache, memcached, directory,
 * SQL-table, noSQL, etc.
 *
 * Supports storages with multiple caches.
 * For example, you can write to multiple handlers.
 *
 * ----
 *
 * Хранилище.
 *
 * Позволяет отправлять и извлекать из хранилища какие-либо данные.
 *
 * Данные можно отправить к кеш по определенному ключу,
 * либо этот ключ будет построен как md5-хеш по данным (по возможности).
 *
 * Есть возможность использовать кешеры APC, memcache, memcached, directory,
 * table in SQL-database, noSQL, etc.
 *
 * Есть поддержка множественных кешей-хранилищ.
 * Например, можно писать сразу в несколько handlerов, даже одинакового
 * класса.
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
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
            throw new Storage_Exception("Empty Storage key.");
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
            throw new Storage_Exception("Empty Storage key.");
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

    }

    private function __clone() {

    }

    /**
     * @var array<Storage_IHandler>
     */
    private static $_InstanceArray = array();

}