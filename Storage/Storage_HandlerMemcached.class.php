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
    public function __construct($prefix, $host = 'localhost', $port = 11211) {
        if (!class_exists('Memcached')) {
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
    public function set($key, $value, $ttl = false, $parentKey = false) {
        if ($parentKey) {
            throw new Storage_Exception('Parent keys is not supported for memcached yet.');
        }
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

            $this->_getMemcached()->setMulti($keyArray, $ttl);
        } else {
            $this->_getMemcached()->set($this->_prefix.$key, $value, $ttl);
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

                $result = $this->_getMemcached()->getMulti($keyArray);
                $a = array();
                foreach ($result as $key => $x) {
                    $a[str_replace($this->_prefix, '', $key)] = $x;
                }
                return $a;
            } else {
                // multi without prefix
                return $this->_getMemcached()->getMulti($key);
            }
        } else {
            // single
            $x = $this->_getMemcached()->get($this->_prefix.$key);
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
        return ($this->_getMemcached()->get($this->_prefix.$key) != false);
    }

    /**
     * Удалить данные
     *
     * @param string $key
     */
    public function remove($key) {
        $this->_getMemcached()->delete($this->_prefix.$key);
    }

    /**
     * Очистить кеш
     */
    public function clean() {
        if ($this->_prefix) {
            throw new Storage_Exception('Cannot flush all cache');
        }

        $this->_getMemcached()->flush();
    }

    public function getLink() {
        return $this->_getMemcached();
    }

    private function _getMemcached() {
        if (!$this->_link) {
            $this->_link = new Memcached();
            $this->_link->addServer($this->_host, $this->_port);
            $this->_link->setOption(Memcached::OPT_TCP_NODELAY, 1);

            // эти три опции приводят к полному пиздецу, но какая именно - не понятно
            //$this->_link->setOption(Memcached::OPT_BINARY_PROTOCOL, 1);
            //$this->_link->setOption(Memcached::OPT_BUFFER_WRITES, 1);
            //$this->_link->setOption(Memcached::OPT_NO_BLOCK, 1);
        }
        return $this->_link;
    }

    private $_prefix;

    private $_host;

    private $_port;

    private $_link;

    /**
     * @deprecated
     */
    public function getData($key) {
        return $this->get($key);
    }

    /**
     * @deprecated
     */
    public function setData($key, $value, $ttl = false, $parentKey = false) {
        return $this->set($key, $value, $ttl, $parentKey);
    }

    /**
     * @deprecated
     */
    public function hasData($key) {
        return $this->has($key);
    }

    /**
     * @deprecated
     */
    public function removeData($key) {
        return $this->remove($key);
    }

    /**
     * @deprecated
     */
    public function clearData() {
        return $this->clean();
    }

}