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
class Storage_Memcached implements Storage_IHandler {

    /**
     * Create memcache handler.
     *
     * @param Connection_IConnection $connection
     */
    public function __construct(Connection_IConnection $connection) {
        $this->_connection = $connection;
    }

    /**
     * Записать данные в кеш.
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value) {
        if (is_array($key)) {
            return $this->getLink()->setMulti($key);
        } else {
            return $this->getLink()->set($key, $value);
        }
    }

    public function setEx($key, $value, $ttl) {
        if ($ttl && $ttl < 0) {
            throw new Storage_Exception("Incorrect TTL '{$ttl}'");
        }

        if (is_array($key)) {
            return $this->getLink()->setMulti($key, $ttl);
        } else {
            return $this->getLink()->set($key, $value, $ttl);
        }
    }

    /**
     * Получить данные по ключу
     *
     * @param string|array $key
     */
    public function get($key) {
        if (is_array($key)) {
            return $this->getLink()->getMulti($key);
        } else {
            // single
            $x = $this->getLink()->get($key);
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
        return ($this->getLink()->get($key) != false);
    }

    /**
     * Удалить данные
     *
     * @param string $key
     */
    public function remove($key) {
        $this->getLink()->delete($key);
    }

    /**
     * Очистить кеш
     */
    public function clean() {
        $this->getLink()->flush();
    }

    /**
     * @return Memcached|resource
     */
    public function getLink() {
        return $this->getConnection()->getLink();
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
}