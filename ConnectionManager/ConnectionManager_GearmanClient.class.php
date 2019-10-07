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
class ConnectionManager_GearmanClient
implements ConnectionManager_IConnection {

    private $_linkID = null;

    public function doBackground($key, $data = false) {
        if (!$data) {
            $data = date('Y-m-d H:i:s');
        } elseif (is_array($data)) {
            $data = json_encode($data);
        }

        if (!$this->getLinkID()) {
            $this->connect();
        }

        $this->getLinkID()->doBackground($key, $data);
    }

    public function __construct($host = false) {
        // проверка
        if (!class_exists('GearmanClient')) {
            throw new ConnectionManager_Exception("class GearmanClient not exists");
        }

        $this->_host = $host;
    }

    public function connect() {
        $gearman = new GearmanClient();
        if ($this->_host) {
            $gearman->addServer($this->_host);
        } else {
            $gearman->addServer();
        }
        $this->_linkID = $gearman;
    }

    public function disconnect() {
        $this->_linkID = null;
    }

    /**
     * Получить ссылку на link
     *
     * @return GearmanClient
     */
    public function getLinkID() {
        return $this->_linkID;
    }

    public function __destruct() {
        @$this->disconnect();
    }

    private $_host;

}