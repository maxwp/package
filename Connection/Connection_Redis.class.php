<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Адаптер для соеденения с Redis
 */
class Connection_Redis
implements Connection_IConnection {

    public function __construct($hostname, $port) {
        if (!class_exists('Redis')) {
            throw new Connection_Exception("PHP extension 'Redis' not available");
        }

        $this->_hostname = $hostname;
        $this->_port = $port;
    }

    public function connect() {
        $this->_link = new Redis();
        $this->_link->pconnect($this->_hostname, $this->_port, $this->_timeoutConnect);

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

    public function setTimeoutConnect(float $timeout) {
        $this->_timeoutConnect = $timeout;
    }

    public function setTimeoutRead(float $timeout) {
        $this->_link->setOption(Redis::OPT_READ_TIMEOUT, $timeout);
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

    private $_timeoutConnect = 3.0;

}