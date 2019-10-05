<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2014 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Адаптер для соеденения с MySQL базой.
 *
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package ConnectionManager
 */
class ConnectionManager_SphinxQL
implements ConnectionManager_IConnection {

    public function __construct($hostname, $port) {
        if (!class_exists('mysqli')) {
            throw new ConnectionManager_Exception("PHP extension 'mysqli' not available");
        }

        $this->_hostname = $hostname;
        $this->_port = $port;
    }

    public function connect() {
        $this->_linkID = new mysqli(
            $this->_hostname,
            '',
            '',
            '',
            $this->_port
        );

        $e = $this->getLinkID()->connect_error;
        if ($e) {
            throw new ConnectionManager_Exception("Cannot connect to database: ".$e);
        }
    }

    /**
     * Выполнить SQL-запрос.
     * Через этот метод теоретически проходят все SQL-запросы в системе.
     *
     * @param string $query
     *
     * @return resource
     */
    public function query($query) {
        if (!$this->getLinkID()) {
            $this->connect();
        }

        $result = $this->getLinkID()->query($query);

        $e = $this->getLinkID()->error;
        if ($e) {
            throw new ConnectionManager_Exception("Executing error: {$e} in query: {$query}");
        }

        return $result;
    }

    public function disconnect() {
        if ($this->getLinkID()) {
            $this->getLinkID()->close();
        }
    }

    /**
     * Получить соеденение
     *
     * @return mysqli
     */
    public function getLinkID() {
        return $this->_linkID;
    }

    public function __destruct() {
        $this->disconnect();
    }

    /**
     * Выполнить обработку запроса.
     *
     * @param mixed $queryResource
     *
     * @return array
     */
    public function fetch($queryResource) {
        if (!$queryResource) {
            throw new ConnectionManager_Exception("No query result to fetch");
        }
        $result = $queryResource->fetch_assoc();
        if (!$result) {
            $queryResource->free();
        }
        return $result;
    }

    /**
     * Экранировать строку
     *
     * @param string $string
     *
     * @return string
     */
    public function escapeString($string) {
        if (!$string) {
            return $string;
        }

        if (!$this->getLinkID()) {
            $this->connect();
        }
        return mysqli_real_escape_string($this->getLinkID(), $string);
    }

    private $_hostname;

    private $_port;

    private $_linkID = null;

}