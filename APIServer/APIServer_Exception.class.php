<?php
/**
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package Package
 */
class APIServer_Exception extends Exception {

    public function __construct($errorArray) {
        if (!is_array($errorArray)) {
            $errorArray = array($errorArray);
        }

        $this->_errorArray = $errorArray;
    }

    public function getErrorArray() {
        return $this->_errorArray;
    }

    private $_errorArray;

}