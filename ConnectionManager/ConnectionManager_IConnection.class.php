<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Интерфейс "соединения" для ConnectionManager'a
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package ConnectionManager
 */
interface ConnectionManager_IConnection {

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
    public function getLinkID();

}