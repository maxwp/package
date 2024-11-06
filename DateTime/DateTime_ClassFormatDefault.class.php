<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Обычное (дефолтное) форматирование дат
 */
class DateTime_ClassFormatDefault implements DateTime_IClassFormat {

    private $_format;

    private $_timestamp;

    public function setFormat($format) {
        $this->_format = $format;
    }

    public function setDate($timestamp) {
        $this->_timestamp = $timestamp;
    }

    public function __toString() {
        return date($this->_format, $this->_timestamp);
    }

}