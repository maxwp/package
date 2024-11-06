<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class Connection_Exception extends Exception {

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
        $query = $this->getQuery();
        $r = '';
        if ($query) {
            $r .= 'Query: '.$query."\n";
        }
        return $r.parent::__toString();
    }

    private $_query;

}