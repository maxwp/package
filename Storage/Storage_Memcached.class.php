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
 * Storage handler: memcached.
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Storage
 */
class Storage_Memcached implements Storage_IHandler {

    /**
     * Create memcache handler.
     * Prefix - string key to identify storage.
     *
     * @param string $prefix
     * @param string $host
     * @param string $port
     */
    public function __construct(Connection_IConnection $connection, $prefix = '') {
        $this->_prefix = $prefix;
        $this->_connection = $connection;
    }

    /**
     * Записать данные в кеш.
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value, $ttl = false) {
        if ($ttl && $ttl < 0) {
            throw new Storage_Exception("Incorrect TTL '{$ttl}'");
        }

        if (is_array($key)) {
            if ($this->_prefix) {
                $keyArray = array();
                foreach ($key as $k => $v) {
                    $keyArray[$this->_prefix.$k] = $v;
                }
            } else {
                $keyArray = $key;
            }

            $this->getLink()->setMulti($keyArray, $ttl);
        } else {
            $this->getLink()->set($this->_prefix.$key, $value, $ttl);
        }
    }

    /**
     * Получить данные по ключу
     *
     * @param string $key
     */
    public function get($key) {
        if (is_array($key)) {
            // multi with prefix
            if ($this->_prefix) {
                $keyArray = array();
                foreach ($key as $x) {
                    $keyArray[$x] = $this->_prefix.$x;
                }

                $result = $this->getLink()->getMulti($keyArray);
                $a = array();
                foreach ($result as $key => $x) {
                    $a[str_replace($this->_prefix, '', $key)] = $x;
                }
                return $a;
            } else {
                // multi without prefix
                return $this->getLink()->getMulti($key);
            }
        } else {
            // single
            $x = $this->getLink()->get($this->_prefix.$key);
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
        return ($this->getLink()->get($this->_prefix.$key) != false);
    }

    /**
     * Удалить данные
     *
     * @param string $key
     */
    public function remove($key) {
        $this->getLink()->delete($this->_prefix.$key);
    }

    /**
     * Очистить кеш
     */
    public function clean() {
        if ($this->_prefix) {
            throw new Storage_Exception('Cannot flush all cache');
        }

        $this->getLink()->flush();
    }

    /**
     * @return Memcached|resource
     */
    public function getLink() {
        return $this->getConnection()->getLinkID();
    }

    /**
     * @return Connection_IConnection
     */
    public function getConnection() {
        return $this->_connection;
    }

    /**
     * @var Connection_IConnection
     */
    private $_connection;

    private $_prefix;

}