<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2026 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * AWS RDS adapter
 */
class Connection_RDS
implements Connection_IDatabaseAdapter {

    public function __construct($hostname, $username, $password, $database, $encoding = 'utf8', $port = 3306, $timezone = false) {
        if (!class_exists('mysqli')) {
            throw new Connection_Exception("PHP extension 'mysqli' not available");
        }

        $this->_hostname = $hostname;
        $this->_username = $username;
        $this->_password = $password;
        $this->_database = $database;
        $this->_encoding = $encoding;
        $this->_port = $port;
        $this->_timezone = $timezone;
    }

    public function connect() {
        // fucking report mode was enabled after php 8
        // https://www.php.net/manual/en/mysqli-driver.report-mode.php
        mysqli_report(MYSQLI_REPORT_OFF);

        $this->_link = mysqli_init();

        $this->_link->ssl_set(
            null,
            null,
            __DIR__.'/rds-global-bundle.pem',
            null,
            null
        );

        $this->_link->real_connect(
            $this->_hostname,
            $this->_username,
            $this->_password,
            $this->_database,
            $this->_port,
            null,
            MYSQLI_CLIENT_SSL
        );

        $e = $this->_link->connect_error;
        if ($e) {
            throw new Connection_Exception("Cannot connect to database $this->_database@$this->_hostname: ".$e);
        }

        if ($this->_encoding) {
            mysqli_set_charset($this->_link, $this->_encoding);
        }

        // пометка что все установлено
        $this->_linkConnected = true;

        // Специальный fix для MySQL 5.7, отключает STRICT MODE
        $this->query('SET sql_mode = ""');

        if ($this->_timezone) {
            $this->query('SET time_zone = "'.$this->_timezone.'"');
        }
    }

    /**
     * Выполнить SQL-запрос.
     * Через этот метод теоретически проходят все SQL-запросы в системе.
     *
     * @param string $queryString
     *
     * @return bool|mysqli_result
     */
    public function query($queryString) {
        if (!$this->_linkConnected) {
            $this->connect();
        }

        // issue #63722 - умный старт транзакций:
        // нет смысла открывать транзакцию пока нет запросов
        if ($this->_transactionRequested) {
            // сбрасываем флаг
            $this->_transactionRequested = false;

            // затем запускаем транзакцию
            $this->query('START TRANSACTION');
        }

        $result = $this->_link->query($queryString);

        $e = $this->_link->error;
        if ($e) {
            throw new Connection_Exception("Executing error: {$e} in query: {$queryString}");
        }

        return $result;
    }

    public function disconnect() {
        if ($this->_link) {
            $this->_link->close();
        }

        $this->_linkConnected = false;
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
        // см метод query, там умный старт транзакции только когда есть первый запрос
        if ($this->_transactionCount <= 0) {
            $this->_transactionRequested = true;
        } elseif ($force) {
            $this->_transactionRequested = true;
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
        if ($this->_transactionCount == 1 || $force) {
            // если транзакция была запрошена но не запущена - то не надо и коммитить
            if ($this->_transactionRequested) {
                $this->_transactionRequested = false;
            } else {
                $this->query('COMMIT');
            }
        }

        $this->_transactionCount -= 1;

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
        if ($this->_transactionCount == 1 || $force) {
            // если транзакция была запрошена но не запущена - то не надо и отменять
            if ($this->_transactionRequested) {
                $this->_transactionRequested = false;
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

        $this->_transactionRequested = false;
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

    public function prepare($query) {
        if (!$this->_linkConnected) {
            $this->connect();
        }

        return $this->_link->prepare($query);
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

        if (!$this->_linkConnected) {
            $this->connect();
        }
        return $this->_link->real_escape_string($string);
    }

    public function getLastInsertID() {
        return $this->_link->insert_id;
    }

    public function getAffectedRows() {
        return $this->_link->affected_rows;
    }

    private $_hostname;
    private $_username;
    private $_password;
    private $_database;
    private $_port;
    private $_encoding;
    private $_timezone;
    /**
     * @var mysqli
     */
    private $_link = null;
    private $_linkConnected = false;
    private $_transactionCount = 0;
    private $_transactionRequested = false; // @todo поменять на started/inited

}
