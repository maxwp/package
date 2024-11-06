<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Адаптер для соеденения с MySQL базой.
 */
class Connection_MySQLi
implements Connection_IDatabaseAdapter {

    public function __construct($hostname, $username, $password, $database = false, $encoding = 'utf8', $port = false) {
        if (!class_exists('mysqli')) {
            throw new Connection_Exception("PHP extension 'mysqli' not available");
        }

        $this->_hostname = $hostname;
        $this->_username = $username;
        $this->_password = $password;
        $this->_database = $database;
        $this->_encoding = $encoding;
        $this->_port = $port;
    }

    public function connect() {
        // fucking report mode was enabled after php 8
        // https://www.php.net/manual/en/mysqli-driver.report-mode.php
        mysqli_report(MYSQLI_REPORT_OFF);

        $this->_link = new mysqli(
            $this->_hostname,
            $this->_username,
            $this->_password,
            $this->_database,
            $this->_port
        );

        $e = $this->getLink()->connect_error;
        if ($e) {
            throw new Connection_Exception("Cannot connect to database $this->_database@$this->_hostname: ".$e);
        }

        if ($this->_encoding) {
            mysqli_set_charset($this->_link, $this->_encoding);
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
        if (!$this->getLink()) {
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

        $result = $this->getLink()->query($query);

        $e = $this->getLink()->error;
        if ($e) {
            throw new Connection_Exception("Executing error: {$e} in query: {$query}");
        }

        return $result;
    }

    public function disconnect() {
        if ($this->_link) {
            $this->_link->close();
        }
    }

    /**
     * Получить соеденение
     *
     * @return mysqli
     */
    public function getLink() {
        return $this->_link;
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
     * Отмотать транзакцию на самый старт
     *
     */
    public function transactionClear() {
        $this->query('ROLLBACK');

        $this->_transactionStart = false;
        $this->_transactionCount = 0;
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
            throw new Connection_Exception("No query result to fetch");
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

        if (!$this->getLink()) {
            $this->connect();
        }
        return @mysqli_real_escape_string($this->getLink(), $string);
    }

    public function getLastInsertID() {
        return $this->getLink()->insert_id;
    }

    public function getAffectedRows() {
        return $this->getLink()->affected_rows;
    }

    private $_hostname;

    private $_username;

    private $_password;

    private $_database;

    private $_port;

    private $_encoding;

    private $_link = null;

    private $_transactionCount = 0;

    private $_transactionStart = false;

}
