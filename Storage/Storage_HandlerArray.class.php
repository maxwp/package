<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Storage handler: array in memory.
 * Useful for registries, pulls, arrays.
 *
 * Обработчик данных "массив во временной памяти".
 * Можно использовать для построение реестров, пулов и т.п.
 *
 * @author Maxim Miroshnichenko
 * @copyright WebProduction
 * @package Storage
 */
class Storage_HandlerArray implements Storage_IHandler {

    /**
     * Put data to array
     * Записать данные в кеш.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value, $ttl = false, $parentKey = false) {
        // @todo: TTL
        $this->_array[$key] = $value;
    }

    /**
     * Get data by key
     * Получить данные по ключу
     *
     * @param string $key
     */
    public function get($key) {
        if ($this->has($key)) {
            return $this->_array[$key];
        }
        throw new Storage_Exception("Storage data not found by key '{$key}'");
    }

    /**
     * Has data on key or no?
     *
     * Узнать, есть ли такой ключ
     *
     * @param string $key
     */
    public function has($key) {
        return isset($this->_array[$key]);
    }

    /**
     * Remove data by key
     * Удалить данные
     *
     * @param string $key
     */
    public function remove($key) {
        unset($this->_array[$key]);
    }

    /**
     * Clean all storage
     *
     * Очистить
     */
    public function clean() {
        $this->_array = array();
    }

    private $_array = array();

}