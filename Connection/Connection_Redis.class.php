<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2014 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Адаптер для соеденения с Redis
 *
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package ConnectionManager
 */
class Connection_Redis
implements Connection_IConnection {

    public function __construct($hostname, $port) {
        if (!class_exists('Redis')) {
            throw new Connection_Exception("PHP extension 'Redis' not available");
        }

        ini_set('default_socket_timeout', -1); // for redis timeout

        $this->_hostname = $hostname;
        $this->_port = $port;
    }

    public function connect() {
        $this->_link = new Redis();
        $this->_link->pconnect($this->_hostname, $this->_port);

        $e = $this->getLink()->getLastError();
        if ($e) {
            throw new Connection_Exception("Cannot connect to Redis: ".$e);
        }
    }

    public function disconnect() {
        if ($this->_link) {
            $this->_link->close();
        }
    }

    /**
     * Получить соеденение Redis
     *
     * @return Redis
     */
    public function getLink() {
        if (!$this->_link) {
            $this->connect();
        }

        return $this->_link;
    }

    public function __destruct() {
        $this->disconnect();
    }

    private $_hostname;

    private $_port;

    /**
     * @var Redis
     */
    private $_link = null;

}