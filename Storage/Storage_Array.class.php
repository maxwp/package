<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
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
class Storage_Array implements Storage_IHandler {

    /**
     * Put data to array
     * Записать данные в кеш.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value, $ttl = false) {
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
        if (isset($this->_array[$key])) {
            return $this->_array[$key];
        }
        throw new Storage_Exception("Storage data not found by key '{$key}'");
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
        $this->_array = [];
    }

    private $_array = [];

}