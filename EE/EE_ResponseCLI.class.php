<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Response for CLI
 */
class EE_ResponseCLI implements EE_IResponse {

    public function getCode() {
        return $this->_code;
    }

    public function getData() {
        return $this->_data;
    }

    public function setCode($code) {
        $this->_code = $code;
    }

    public function setData($data) {
        $this->_data = $data;
    }

    private $_code;

    private $_data;

}