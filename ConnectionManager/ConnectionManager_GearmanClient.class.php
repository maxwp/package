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

    public function doBackground($key, $data = false, $unique = false, $priority = 'normal') {
        if (!$data) {
            $data = date('Y-m-d H:i:s');
        } elseif (is_array($data)) {
            $data = json_encode($data);
        }

        if (!$this->getLinkID()) {
            $this->connect();
        }

        if ($priority == 'normal' || !$priority) {
            return $this->getLinkID()->doBackground($key, $data, $unique);
        } elseif ($priority == 'high') {
            return $this->getLinkID()->doHighBackground($key, $data, $unique);
        } elseif ($priority == 'low') {
            return $this->getLinkID()->doLowBackground($key, $data, $unique);
        }
    }

    public function do($key, $data = false, $unique = false, $priority = 'normal') {
        if (!$data) {
            $data = date('Y-m-d H:i:s');
        } elseif (is_array($data)) {
            $data = json_encode($data);
        }

        if (!$this->getLinkID()) {
            $this->connect();
        }

        if ($priority == 'normal' || !$priority) {
            return $this->getLinkID()->doNormal($key, $data, $unique);
        } elseif ($priority == 'high') {
            return $this->getLinkID()->doHigh($key, $data, $unique);
        } elseif ($priority == 'low') {
            return $this->getLinkID()->doLow($key, $data, $unique);
        }
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
        $gearman->setTimeout(60);
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