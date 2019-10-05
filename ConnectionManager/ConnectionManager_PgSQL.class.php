<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Адаптер для соеденения с PgSQL (PostgreSQL) базой.
 * Использует встроенные php-функции pg_*
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package ConnectionManager
 */
class ConnectionManager_PgSQL
implements ConnectionManager_IDatabaseAdapter, ConnectionManager_IConnection {

    /**
     * Получить статистику
     *
     * @return array
     */
    public function getStatistics() {
        return $this->_queryStat;
    }

    public function __construct($hostname, $username, $password, $database = false,
    $encoding = 'unicode', $permanent = true) {
        // проверка
        if (!function_exists('pg_connect')) {
            throw new ConnectionManager_Exception("PHP extension 'pgsql' not available");
        }

        $this->_hostname = $hostname;
        $this->_username = $username;
        $this->_password = $password;
        $this->_database = $database;
        $this->_encoding = $encoding;
        $this->_permanent = $permanent;
    }

    public function connect() {
        $s = '';
        $s .= "host={$this->_hostname} ";
        $s .= "port=5432 ";
        $s .= "dbname={$this->_database} ";
        $s .= "user={$this->_username} ";
        $s .= "password={$this->_password}";

        if ($this->_permanent) {
            $this->_linkID = pg_pconnect($s);
        } else {
            $this->_linkID = pg_connect($s);
        }

        if (pg_connection_status($this->_linkID) == PGSQL_CONNECTION_BAD) {
            throw new ConnectionManager_Exception("Cannot connect to PgSQL-database");
        }

        if ($this->_encoding) {
            $this->query("SET NAMES '{$this->_encoding}'");
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

        $time = microtime(true);
        $result = @pg_query($this->getLinkID(), $query);
        $time = microtime(true) - $time;

        if (PackageLoader::Get()->getMode('debug')) {
            $statArray = array();
            $statArray['query'] = $query;
            $statArray['time'] = $time;
            //$statArray['trace'] = $trace;
            /*if (strpos($query, 'SELECT ') === 0) {
            $statArray['explain']
                = mysql_fetch_assoc(SQLObjectConfig::Get()->getDatabaseConnection()->query("EXPLAIN $query"));
            } else {
            $statArray['explain'] = array();
            }*/
            $this->_queryStat[] = $statArray;
        }

        if (!$result && $e = pg_last_error($this->getLinkID())) {
            $ex = new ConnectionManager_Exception($e);
            $ex->setQuery($query);
            throw $ex;
        }

        return $result;
    }

    public function disconnect() {
        if ($this->getLinkID()) {
            pg_close($this->getLinkID());
        }
    }

    public function getLinkID() {
        return $this->_linkID;
    }

    public function __destruct() {
        @$this->disconnect();
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
        return pg_fetch_assoc($queryResource);
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
            $this->query("BEGIN TRANSACTION");
        }

        $this->_transactionCount ++;
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
            $this->query("COMMIT");
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
            $this->query("ROLLBACK");
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
     * Экранировать строку
     *
     * @param string $string
     *
     * @return string
     */
    public function escapeString($string) {
        if (!$this->getLinkID()) {
            $this->connect();
        }
        return pg_escape_string($this->getLinkID(), $string);
    }

    public function getHostname() {
        return $this->_hostname;
    }

    public function getUsername() {
        return $this->_username;
    }

    public function getPassword() {
        return $this->_password;
    }

    public function getDatabase() {
        return $this->_database;
    }

    private $_hostname;

    private $_username;

    private $_password;

    private $_database;

    private $_encoding;

    private $_linkID = null;

    private $_permanent;

    private $_queryStat = array();

    private $_transactionCount = 0;

}