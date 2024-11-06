<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Интерфейс "соединения" для ConnectionManager'a
 */
interface Connection_IConnection {

    /**
     * Выполнить соеденение
     */
    public function connect();

    /**
     * Выполнить разрыв соеденения
     */
    public function disconnect();

    /**
     * Получить идентификатор ресурса
     *
     * @return resource
     */
    public function getLink();

}