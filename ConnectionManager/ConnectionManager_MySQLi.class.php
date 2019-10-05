<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2016 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Адаптер для соеденения с MySQL базой.
 *
 * @author    Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package   ConnectionManager
 */
class ConnectionManager_MySQLi
implements ConnectionManager_IDatabaseAdapter, ConnectionManager_IConnection {

    public function __construct($hostname, $username, $password, $database = false, $encoding = 'utf8', $port = false) {
        if (!class_exists('mysqli')) {
            throw new ConnectionManager_Exception("PHP extension 'mysqli' not available");
        }

        $this->_hostname = $hostname;
        $this->_username = $username;
        $this->_password = $password;
        $this->_database = $database;
        $this->_encoding = $encoding;
        $this->_port = $port;

        // в режиме debug включаем статистику
        if (PackageLoader::Get()->getMode('debug')) {
            $this->enableStatistic();
        }
    }

    public function connect() {
        $this->_linkID = new mysqli(
            $this->_hostname,
            $this->_username,
            $this->_password,
            $this->_database,
            $this->_port
        );

        $e = $this->_linkID->connect_error;
        if ($e) {
            throw new ConnectionManager_Exception("Cannot connect to database: ".$e);
        }

        if ($this->_encoding) {
            mysqli_set_charset($this->_linkID, $this->_encoding);
        }

        // Специальный fix для MySQL 5.7, отключает STRICT MODE
        $this->query('SET sql_mode = ""');
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
        if (!$this->_linkID) {
            $this->connect();
        }

        // issue #63722 - умный старт транзакций:
        // нет смысла открывать транзакцию пока нет запросов
        if ($this->_transactionStart) {
            // сначала сбрасываем флаг
            $this->_transactionStart = false;

            // затем запускаем транзакцию
            $this->query('START TRANSACTION');
        }

        $result = $this->_linkID->query($query);

        $e = $this->_linkID->error;
        if ($e) {
            throw new ConnectionManager_Exception("Executing error: {$e} in query: {$query}");
        }

        return $result;
    }

    public function disconnect() {
        if ($this->_linkID) {
            $this->_linkID->close();
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
        @$this->disconnect();
    }

    /**
     * Начать транзакцию
     * force - принудительно.
     *
     * @param bool $force
     */
    public function transactionStart($force = false) {
        // транзакцию нужно открывать либо по force, либо когда счетчик = 0
        if (!$this->_transactionCount || $force) {
            $this->_transactionStart = true;
        }

        $this->_transactionCount ++;
        return true;
    }

    /**
     * Выполнить транзакцию
     * force - принудительно
     *
     * @param bool $force
     */
    public function transactionCommit($force = false) {
        // коммит нужно выполнить только если force или транзакция одна
        if ($force || $this->_transactionCount == 1) {
            // если транзакция была запрошена но не запущена - то не надо и коммитить
            if ($this->_transactionStart) {
                $this->_transactionStart = false;
            } else {
                $this->query('COMMIT');
            }
        }
        $this->_transactionCount --;

        if ($this->_transactionCount < 0) {
            $this->_transactionCount = 0;
        }
    }

    /**
     * Откатить транзакцию
     * force - принудительно
     *
     * @param bool $force
     */
    public function transactionRollback($force = false) {
        // rollback нужно выполнить только если force или транзакция одна
        if ($force || $this->_transactionCount == 1) {
            // если транзакция была запрошена но не запущена - то не надо и отменять
            if ($this->_transactionStart) {
                $this->_transactionStart = false;
            } else {
                $this->query('ROLLBACK');
            }
        }
        $this->_transactionCount --;

        if ($this->_transactionCount < 0) {
            $this->_transactionCount = 0;
        }
    }

    /**
     * Получить уровень вложенности транзакции, которая сейчас открыта.
     * 0 - нет транзакции.
     * 1..N - глубина транзакции.
     *
     * @return int
     */
    public function getTransactionLevel() {
        return $this->_transactionCount;
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

        if (!$this->_linkID) {
            $this->connect();
        }
        return @mysqli_real_escape_string($this->_linkID, $string);
    }

    public function getLastInsertID() {
        return $this->_linkID->insert_id;
    }

    private $_hostname;

    private $_username;

    private $_password;

    private $_database;

    private $_port;

    private $_encoding;

    private $_linkID = null;

    private $_transactionCount = 0;

    private $_transactionStart = false;

}