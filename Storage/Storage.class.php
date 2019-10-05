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
     * @return Storage
     */
    public static function Get($storageKey) {
        if (!$storageKey) {
            throw new Storage_Exception("Empty Storage key.");
        }

        if (!isset(self::$_Instance[$storageKey])) {
            throw new Storage_Exception("Storage with key '{$storageKey}' not found, please, call Initialize() before.");
        }

        return self::$_Instance[$storageKey];
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
     * @return Storage
     */
    public static function Initialize($storageKey, Storage_IHandler $handler) {
        if (!$storageKey) {
            throw new Storage_Exception("Empty Storage key.");
        }

        self::$_Instance[$storageKey] = new self();
        self::Get($storageKey)->clearHandlers();
        self::Get($storageKey)->addHandler($handler);
        return self::Get($storageKey);
    }

    /**
     * Add handler to storage.
     *
     * Добавить обработчик кеша
     *
     * @param Storage_IHandler $handler
     */
    public function addHandler(Storage_IHandler $handler) {
        $this->_handlersArray[] = $handler;
    }

    /**
     * Remove handlers from storage.
     *
     * Очистить все обработчики
     */
    public function clearHandlers() {
        $this->_handlersArray = array();
    }

    /**
     * Put data to storage.
     *
     * Записать данные в кеш.
     *
     * Если вместо ключа будет передан false, то
     * ключ будет построен автоматически (на основе md5-суммы)
     * и возвращен методом
     *
     * @param mixed $key
     * @param mixed $parentKey
     * @param mixed $value
     * @param int $ttl
     * @return string
     */
    public function setData($key, $value, $ttl = false, $parentKey = false) {
        if ($key === false) {
            $key = md5($value);
        }
        foreach ($this->_handlersArray as $h) {
            // пишем данные во все хранилища
            $h->set($key, $value, $ttl, $parentKey);
        }

        return $key;
    }

    /**
     * Get data from storage by key
     *
     * Получить данные по ключу
     *
     * @param string $key
     * @return mixed
     */
    public function getData($key) {
        foreach ($this->_handlersArray as $h) {
            return $h->get($key);
        }
    }

    /**
     * Has data on key or no?
     *
     * Узнать, существует ли ключ?
     *
     * @param string $key
     * @return bool
     */
    public function hasData($key) {
        foreach ($this->_handlersArray as $h) {
            return $h->has($key);
        }
    }

    /**
     * Remove data from storage
     *
     * Удалить данные из кеша
     *
     * @param string $key
     */
    public function removeData($key) {
        foreach ($this->_handlersArray as $h) {
            return $h->remove($key);
        }
    }

    /**
     * Clean all storage
     *
     * Очистить всё хранилище
     */
    public function clearData() {
        foreach ($this->_handlersArray as $h) {
            $h->clean();
        }
    }

    /**
     * Remove all storage pull
     * Очистить весь реестр Storage-ей
     *
     * @access public
     * @static
     */
    public static function Reset() {
        self::$_Instance = array();
    }

    private function __construct() {

    }

    private function __clone() {

    }

    /**
     * @var array of Storage
     */
    private static $_Instance = array();

    /**
     * @var array
     */
    private $_handlersArray = array();

}