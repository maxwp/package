<?php
class EE_RequestRemote implements EE_IRequest {

    public function __construct($content, $argumentArray) {
        $this->_argumentArray = $argumentArray;
        $this->_argumentArray['ee-content'] = $content;
    }

    public function getArgumentArray() {
        return $this->_argumentArray;
    }

    public function getArgument($key, $argType = false) {
        if (isset($this->_argumentArray[$key])) {
            return $this->_argumentArray[$key];
        }

        throw new EE_Exception("No argument $key");
    }

    public function getArgumentSecure($key, $argType = false) {
        try {
            return $this->getArgument($key, $argType);
        } catch (Exception $e) {

        }

        return false;
    }

    private $_argumentArray = [];

}