<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Remote request
 */
class EE_RequestRemote implements EE_IRequest {

    public function __construct($content, $argumentArray) {
        $this->_argumentArray = $argumentArray;
        $this->_argumentArray['ee-content'] = $content;
    }

    public function getArgumentArray() {
        return $this->_argumentArray;
    }

    public function getArgument($key, $source = false) {
        if (isset($this->_argumentArray[$key])) {
            return $this->_argumentArray[$key];
        }

        throw new EE_Exception("No argument $key");
    }

    private $_argumentArray = [];

}