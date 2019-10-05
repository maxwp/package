<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package ConnectionManager
 */
class ConnectionManager_Exception extends Exception {

    public function __construct($message = '', $code = 0) {
        parent::__construct($message, $code);
    }

    public function setQuery($query) {
        $this->_query = $query;
    }

    public function getQuery() {
        return $this->_query;
    }

    public function __toString() {
        if (class_exists('DebugException')) {
            return DebugException::Display($this, __CLASS__);
        }

        $query = $this->getQuery();
        $r = '';
        if ($query) {
            $r .= 'Query: '.$query."\n";
        }
        return $r.parent::__toString();
    }

    private $_query;

}