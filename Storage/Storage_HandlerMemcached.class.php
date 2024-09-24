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
class Storage_HandlerMemcached implements Storage_IHandler {

    /**
     * Create memcache handler.
     * Prefix - string key to identify storage.
     *
     * @param string $prefix
     * @param string $host
     * @param string $port
     */
    public function __construct($prefix, $host = 'localhost', $port = 11211, $binaryProtocol = false) {
        if (!class_exists('Memcached')) {
            throw new Storage_Exception();
        }

        $this->_prefix = $prefix;
        $this->_host = $host;
        $this->_port = $port;
        $this->_link = null;
        $this->_binaryProtocol = (bool) $binaryProtocol;
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
    /*public function has($key) {
        return ($this->getLink()->get($this->_prefix.$key) != false);
    }*/

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

    public function getLink() { // @todo а интерфейс есть?
        if (!$this->_link) {
            $this->_link = new Memcached();
            $this->_link->addServer($this->_host, $this->_port);
            $this->_link->setOption(Memcached::OPT_TCP_NODELAY, 1);
            if ($this->_binaryProtocol) {
                $this->_link->setOption(Memcached::OPT_BINARY_PROTOCOL, 1);
            }
            //$this->_link->setOption(Memcached::OPT_NO_BLOCK, 1);
        }
        return $this->_link;
    }

    private $_prefix;

    private $_host;

    private $_port;

    private $_link;

    private $_binaryProtocol = false;

}