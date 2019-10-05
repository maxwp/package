<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Storage handler: data in session.
 *
 * Обработчик данных в сессии.
 * Можно использовать для построение реестров, пулов и т.п.
 *
 * @author Maxim Miroshnichenko
 * @author Vladimir Gromyak
 * @copyright WebProduction
 * @package Storage
 */
class Storage_HandlerSession implements Storage_IHandler {

    public function __construct() {
        @session_start();
    }

    /**
     * Записать данные в кеш.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value, $ttl = 0, $parentKey = false) {
        //if ($ttl) throw new Storage_Exception("TTL not supported for files");
        if ($parentKey) {
            throw new Storage_Exception("Parent key not supported for files");
        }
        if (substr($key, -10) == '_cache_ttl') {
            throw new Storage_Exception("Unable to store data with such key");
        }
        $_SESSION[$key.'_cache_ttl'] = $ttl?time() + $ttl:0;
        $_SESSION[$key] = $value;
    }

    /**
     * Получить данные по ключу
     *
     * @param string $key
     */
    public function get($key) {
        if ($this->has($key)) {
            return $_SESSION[$key];
        }
        throw new Storage_Exception("Storage data not found by key '{$key}'");
    }

    /**
     * Узнать, есть ли такой ключ
     *
     * @param string $key
     */
    public function has($key) {
        $i = isset($_SESSION[$key]);
        $ttl = true;
        if ($t = @$_SESSION[$key . '_cache_ttl']) {
            $ttl = $t > time();
        }
        return $i && $ttl;
    }

    /**
     * Удалить данные
     *
     * @param string $key
     */
    public function remove($key) {
        unset($_SESSION[$key]);
    }

    /**
     * Очистить
     */
    public function clean() {
        @session_unset();
    }

}