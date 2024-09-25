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
class Connection_Memcached
implements Connection_IConnection {

    public function __construct($host = 'localhost', $port = 11211, $binaryProtocol = false) {
        if (!class_exists('Memcached')) {
            throw new Connection_Exception("PHP extension 'Memcached' not available");
        }

        $this->_host = $host;
        $this->_port = $port;
        $this->_link = null;
        $this->_binaryProtocol = (bool) $binaryProtocol;
    }

    public function connect() {
        $this->_link = new Memcached();
        $this->_link->addServer($this->_host, $this->_port);
        $this->_link->setOption(Memcached::OPT_TCP_NODELAY, 1);
        if ($this->_binaryProtocol) {
            $this->_link->setOption(Memcached::OPT_BINARY_PROTOCOL, 1);
        }
        //$this->_link->setOption(Memcached::OPT_NO_BLOCK, 1); // @todo пока-что не работает, может косяк php8.2+debian12
    }

    public function disconnect() {
        if ($this->_link) {
            $this->_link->quit();
        }
    }

    /**
     * Получить соеденение Redis
     *
     * @return Memcached
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

    private $_host;

    private $_port;

    /**
     * @var Memcached
     */
    private $_link;

    private $_binaryProtocol = false;

}