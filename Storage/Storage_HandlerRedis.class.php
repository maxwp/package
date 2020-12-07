<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2020 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Storage handler: Redis.
 * Обработчик кеша "хранение кеша в redis"
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Storage
 */
class Storage_HandlerRedis implements Storage_IHandler {

    /**
     * Create memcache handler.
     * Prefix - string key to identify storage.
     *
     * @param string $prefix
     * @param string $host
     * @param string $port
     */
    public function __construct($prefix, $host = '127.0.0.1', $port = 6379) {
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

        $result = $this->_getRedis()->set($this->_prefix.$key, $value, $ttl);
        /*if (!$result) {
            var_dump($value);
        }*/
    }

    /**
     * Получить данные по ключу
     *
     * @param mixed $key
     */
    public function get($key) {
        if (is_array($key)) {
            // multi
            $keyArray = array();
            foreach ($key as $x) {
                $keyArray[$x] = $this->_prefix.$x;
            }
            $result = $this->_getRedis()->mget($keyArray);
            // в $result будет не ассоциативный массив, а набор по индексам
            // массив еще нужно собрать
            return array_combine($key, $result);
        } else {
            // single
            $x = $this->_getRedis()->get($this->_prefix.$key);
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
        return ($this->_getRedis()->get($this->_prefix.$key) != false);
    }

    /**
     * Удалить данные
     *
     * @param string $key
     */
    public function remove($key) {
        $this->_getRedis()->del($this->_prefix.$key);
    }

    /**
     * Очистить кеш
     */
    public function clean() {
        throw new Storage_Exception('Cannot remove all');

        /*if ($this->_prefix) {
            throw new Storage_Exception('Cannot flush all cache');
        }*/

        //$this->_getRedis()->del();
    }

    private function _getRedis() {
        if (!$this->_link) {
            $this->_link = new Redis();
            $this->_link->connect($this->_host, $this->_port);
        }
        return $this->_link;
    }

    private $_prefix;

    private $_host;

    private $_port;

    private $_link;

}