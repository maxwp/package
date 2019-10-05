<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Утилита для быстрого форматирования чего-либо в нужный формат
 *
 * @author Max
 * @copyright WebProduction
 * @package StringUtils
 * @subpackage Formatter
 */
abstract class StringUtils_AFormatter {

    public function __construct($data) {
        $this->setData($data);
    }

    public function getData() {
        return $this->_data;
    }

    public function setData($data) {
        $data = trim($data);
        if (!$data) {
        	throw new StringUtils_Exception('Empty data');
        }
        $this->_data = $data;
    }

    /**
     * Отформатировать телефонный номер по заданному форматтеру
     *
     * @throws StringUtils_Exception
     * @return string
     */
    abstract public function format();

    /**
     * Отформатировать без исключений, по-любому :-)
     *
     * @return string
     */
    public function formatSecure() {
        try {
            return $this->format();
        } catch (Exception $e) {
            return $this->getData();
        }
    }

}