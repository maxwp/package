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
class ConnectionManager_Redis
implements ConnectionManager_IConnection {

    public function __construct($hostname, $port) {
        if (!class_exists('Redis')) {
            throw new ConnectionManager_Exception("PHP extension 'Redis' not available");
        }

        ini_set('default_socket_timeout', -1); // for redis timeout

        $this->_hostname = $hostname;
        $this->_port = $port;
    }

    public function connect() {
        $this->_linkID = new Redis();
        $this->_linkID->pconnect($this->_hostname, $this->_port);

        $e = $this->getLinkID()->getLastError();
        if ($e) {
            throw new ConnectionManager_Exception("Cannot connect to Redis: ".$e);
        }
    }

    public function disconnect() {
        if ($this->getLinkID()) {
            $this->getLinkID()->close();
        }
    }

    /**
     * Получить соеденение Redis
     *
     * @return Redis
     */
    public function getLinkID() {
        if (!$this->_linkID) {
            $this->connect();
        }

        return $this->_linkID;
    }

    public function __destruct() {
        $this->disconnect();
    }

    private $_hostname;

    private $_port;

    /**
     * @var Redis
     */
    private $_linkID = null;

}