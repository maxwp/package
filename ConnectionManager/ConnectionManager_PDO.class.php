<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Адаптер для соеденения с любой базой через стандартный php PDO-класс.
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package ConnectionManager
 */
class ConnectionManager_PDO
implements ConnectionManager_IConnection, ConnectionManager_IDatabaseAdapter {

    private $_dsn;

    private $_linkID = null;

    private $_queryStat = array();

    /**
     * Получить статистику
     *
     * @deprecated
     * @return array
     */
    public function getStatistics() {
        return $this->_queryStat;
    }

    /**
     * Создать соеденение
     *
     * @param string $dsn Data Source Name
     */
    public function __construct($dsn) {
        // проверка
        if (!class_exists('PDO')) {
            throw new ConnectionManager_Exception("PHP extension 'PDO' not available");
        }

        $this->_dsn = $dsn;
    }

    public function connect() {
        try {
            $this->_linkID = new PDO($this->_dsn);
        } catch (Exception $e) {
            throw new ConnectionManager_Exception($e->getMessage());
        }
    }

    /**
     * Выполнить SQL-запрос.
     *
     * @param string $query
     *
     * @return PDOStatement
     */
    public function query($query) {
        if (!$this->getLinkID()) {
            $this->connect();
        }

        try {
            $time = microtime(true);
            $result = $this->getLinkID()->prepare($query);
            $result->execute();
            $time = microtime(true) - $time;

            if (PackageLoader::Get()->getMode('debug')) {
                $statArray = array();
                $statArray['query'] = $query;
                $statArray['time'] = $time;
                $this->_queryStat[] = $statArray;
            }

            return $result;
        } catch (Exception $e) {
            throw new ConnectionManager_Exception($e->getMessage());
        }
    }

    public function disconnect() {
        $this->_linkID = null;
    }

    /**
     * Получить ссылку на link
     *
     * @return PDO
     */
    public function getLinkID() {
        return $this->_linkID;
    }

    public function __destruct() {
        @$this->disconnect();
    }

    /**
     * Выполнить обработку запроса.
     *
     * @param PDOStatement $queryResource
     *
     * @return mixed
     */
    public function fetch($queryResource) {
        if (!$queryResource) {
            throw new ConnectionManager_Exception("No PDOStatement to fetch");
        }

        return $queryResource->fetch();
    }

    /**
     * Начать транзакцию
     * force - принудительно.
     *
     * @param bool $force
     */
    public function transactionStart($force = false) {
        if ($force) {
            throw new ConnectionManager_Exception('PDO do not support force-transactions');
        }
        $this->getLinkID()->beginTransaction();
    }

    /**
     * Выполнить транзакцию
     * force - принудительно
     *
     * @param bool $force
     */
    public function transactionCommit($force = false) {
        if ($force) {
            throw new ConnectionManager_Exception('PDO do not support force-transactions');
        }
        $this->getLinkID()->commit();
    }

    /**
     * Откатить транзакцию
     * force - принудительно
     *
     * @param bool $force
     */
    public function transactionRollback($force = false) {
        if ($force) {
            throw new ConnectionManager_Exception('PDO do not support force-transactions');
        }
        $this->getLinkID()->rollBack();
    }

    /**
     * Получить уровень вложенности транзакции, которая сейчас открыта.
     * 0 - нет транзакции.
     * 1..N - глубина транзакции.
     *
     * @return int
     */
    public function getTransactionLevel() {
        return false;
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
        return $this->getLinkID()->quote($string);
    }

}