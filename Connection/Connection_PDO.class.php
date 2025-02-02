<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Адаптер для соеденения с любой базой через стандартный php PDO-класс.
 * @todo
 */
class Connection_PDO
implements Connection_IConnection, Connection_IDatabaseAdapter {

    /**
     * @param $key
     * @return Connection_PDO
     * @throws Connection_Exception
     */
    public static function Get($key) {
        return Connection::Get($key);
    }

    private $_dsn;

    private $_link = null;

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
            throw new Connection_Exception("PHP extension 'PDO' not available");
        }

        $this->_dsn = $dsn;
    }

    public function connect() {
        try {
            $this->_link = new PDO($this->_dsn);
        } catch (Exception $e) {
            throw new Connection_Exception($e->getMessage());
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
        if (!$this->getLink()) {
            $this->connect();
        }

        try {
            $time = microtime(true);
            $result = $this->getLink()->prepare($query);
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
            throw new Connection_Exception($e->getMessage());
        }
    }

    public function disconnect() {
        $this->_link = null;
    }

    /**
     * Получить ссылку на link
     *
     * @return PDO
     */
    public function getLink() {
        return $this->_link;
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
            throw new Connection_Exception("No PDOStatement to fetch");
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
            throw new Connection_Exception('PDO do not support force-transactions');
        }
        $this->getLink()->beginTransaction();
    }

    /**
     * Выполнить транзакцию
     * force - принудительно
     *
     * @param bool $force
     */
    public function transactionCommit($force = false) {
        if ($force) {
            throw new Connection_Exception('PDO do not support force-transactions');
        }
        $this->getLink()->commit();
    }

    /**
     * Откатить транзакцию
     * force - принудительно
     *
     * @param bool $force
     */
    public function transactionRollback($force = false) {
        if ($force) {
            throw new Connection_Exception('PDO do not support force-transactions');
        }
        $this->getLink()->rollBack();
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
        if (!$this->getLink()) {
            $this->connect();
        }
        return $this->getLink()->quote($string);
    }

}