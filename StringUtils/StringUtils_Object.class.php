<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Value Object (VO) for String.
 * Designed for UTF-8 strings
 *
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package StringUtils
 */
class StringUtils_Object {

    /**
     * @param string $string
     * @return StringUtils_Object
     */
    public static function Create($string) {
        return new self($string);
    }

    public function __construct($string) {
        $this->_string = $string;
    }

    /**
	 * Trim string
	 *
	 * @return StringUtils_Object
	 */
    public function trim() {
        $this->_string = trim($this->_string);
        return $this;
    }

    /**
	 * Strip tags
	 *
	 * @return StringUtils_Object
	 */
    public function stripTags() {
        $this->_string = strip_tags($this->_string);
        return $this;
    }

    /**
	 * Limit string to $length
	 *
	 * @param int $length
	 * @return StringUtils_Object
	 */
    public function limit($length) {
        if (function_exists('mb_substr')) {
            $this->_string = mb_substr($this->_string, 0, $length);
        } else {
            $this->_string = substr($this->_string, 0, $length);
        }
        return $this;
    }

    /**
	 * Get string length
	 *
	 * @return int
	 */
    public function getLength() {
        if (function_exists('mb_strlen')) {
            return mb_strlen($this->_string);
        } else {
            return strlen($this->_string);
        }
    }

    public function __toString() {
        return $this->_string;
    }

    private $_string;

}