<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Storage handler: memcache.
 * Обработчик кеша "хранение кеша в memcache"
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Storage
 */
class Storage_HandlerMemcache implements Storage_IHandler {

    /**
     * Create memcache handler.
     * Prefix - string key to identify storage.
     *
     * @param string $prefix
     * @param string $host
     * @param string $port
     */
    public function __construct($prefix, $host = 'localhost', $port = 11211) {
        if (!class_exists('Memcache')) {
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
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value, $ttl = false, $parentKey = false) {
        if ($parentKey) {
            throw new Storage_Exception('Parent keys is not supported for memcached yet.');
        }
        if ($ttl && $ttl < 0) {
            throw new Storage_Exception("Incorrect TTL '{$ttl}'");
        }

        $this->_getMemcache()->set($this->_prefix.$key, $value, false, $ttl);
    }

    /**
     * Получить данные по ключу
     *
     * @param string $key
     */
    public function get($key) {
        if (is_array($key)) {
            // multi
            $keyArray = array();
            foreach ($key as $x) {
                $keyArray[$x] = $this->_prefix.$x;
            }
            $result = $this->_getMemcache()->get($keyArray);
            $a = array();
            foreach ($result as $key => $x) {
                $a[str_replace($this->_prefix, '', $key)] = $x;
            }
            return $a;
        } else {
            // single
            $x = $this->_getMemcache()->get($this->_prefix.$key);
            if ($x === false) {
                throw new Storage_Exception("Cache by key '{$key}' not found");
            }
            return $x;
        }
    }

    /**
     * Узнать, есть ли такой ключ
     *
     * @param string $key
     */
    public function has($key) {
        return ($this->_getMemcache()->get($this->_prefix.$key) != false);
    }

    /**
     * Удалить данные
     *
     * @param string $key
     */
    public function remove($key) {
        $this->_getMemcache()->delete($this->_prefix.$key);
    }

    /**
     * Очистить кеш
     */
    public function clean() {
        if ($this->_prefix) {
            throw new Storage_Exception('Cannot flush all cache');
        }

        $this->_getMemcache()->flush();
    }

    private function _getMemcache() {
        if (!$this->_link) {
            $this->_link = new Memcache();
            $this->_link->connect($this->_host, $this->_port);
        }
        return $this->_link;
    }

    private $_prefix;

    private $_host;

    private $_port;

    private $_link;

}