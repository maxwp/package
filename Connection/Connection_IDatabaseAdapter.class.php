<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Адаптер для соединений с базами данных.
 * По сути дополняет интерфейс ConnectionManager_IConnection
 */
interface Connection_IDatabaseAdapter extends Connection_IConnection {

    /**
     * Выполнить запрос.
     * В большинстве случаев - SQL-запрос.
     *
     * @param string $queryString
     *
     * @return resource
     */
    public function query($queryString);

    /**
     * Выполнить обработку запроса.
     *
     * @param mixed $queryResource
     *
     * @return mixed
     */
    public function fetch($queryResource);

    /**
     * Начать транзакцию
     * force - принудительно.
     *
     * @param bool $force
     */
    public function transactionStart($force = false);

    /**
     * Выполнить транзакцию
     * force - принудительно
     *
     * @param bool $force
     */
    public function transactionCommit($force = false);

    /**
     * Откатить транзакцию
     * force - принудительно
     *
     * @param bool $force
     */
    public function transactionRollback($force = false);

    /**
     * Получить уровень вложенности транзакции, которая сейчас открыта.
     * 0 - нет транзакции.
     * 1..N - глубина транзакции.
     *
     * @return int
     */
    public function getTransactionLevel();

    /**
     * Экранировать строку
     *
     * @param string $string
     *
     * @return string
     */
    public function escapeString($string);

}