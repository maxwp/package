<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Storage handler: memcached.
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Storage
 */
class Storage_MemSock implements Storage_IHandler {

    /**
     * Create memcache handler.
     * Prefix - string key to identify storage.
     *
     * @param string $prefix
     * @param string $host
     * @param string $port
     */
    public function __construct($prefix, $host = 'localhost', $port = 10001) {
        if (!class_exists('MemSockClient')) {
            throw new Storage_Exception();
        }

        $this->_prefix = $prefix;
        $this->_host = $host;
        $this->_port = $port;
        $this->_link = null;
    }

    /**
     * Записать данные в кеш.
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value, $ttl = false) {
        if (!empty($ttl)) {
            throw new Storage_Exception("Incorrect TTL '{$ttl}'");
        }

        $this->_getMemsock()->set($this->_prefix.$key, $value);
    }

    /**
     * Получить данные по ключу
     *
     * @param string $key
     */
    public function get($key) {
        if (is_array($key)) {
            $key = 'all';
        }

        if ($key === 'all' && empty($this->_prefix)) {
            return $this->_getMemsock()->getAll();
        } else {
            return $this->_getMemsock()->get($this->_prefix.$key);
        }
    }

    /**
     * Узнать, есть ли такой ключ
     *
     * @param string $key
     */
    /*public function has($key) {
        return ($this->_getMemsock()->get($this->_prefix.$key) !== false);
    }*/

    /**
     * Удалить данные
     *
     * @param string $key
     */
    public function remove($key) {
        throw new Storage_Exception('Cannot delete key');
    }

    /**
     * Очистить кеш
     */
    public function clean() {
        if ($this->_prefix) {
            throw new Storage_Exception('Cannot flush all cache');
        }

        $this->_getMemsock()->clear();
    }

    public function getLink() {
        return $this->_getMemsock();
    }

    /**
     * @return MemSockClient
     */
    private function _getMemsock() {
        if (!$this->_link) {
            $this->_link = new MemSockClient($this->_host, $this->_port);
        }
        return $this->_link;
    }

    private $_prefix;

    private $_host;

    private $_port;

    private $_link;

}