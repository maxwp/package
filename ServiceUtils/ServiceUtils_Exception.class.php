<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2016 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Exeption, который могут выбрасывать сервисы
 * Хранит в себе массив ошибок
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   ServiceUtils
 */
class ServiceUtils_Exception extends Exception {

    public function __construct($message = '', $code = 0, $errorText = false) {
        parent::__construct($message, $code);

        if ($code === 0 && $message) {
            $this->addError($message);
        }
        $this->_errorText = $errorText;
    }

    /**
     * Добавить ошибку-сообщение в массив
     *
     * @param mixed $code
     * @param mixed $parameterArray
     */
    public function addError($code, $parameterArray = array()) {
        // @todo: проверка на наличие кода?
        $this->_errorsArray[] = $code;
        $this->_errorFullArray[] = array(
            'key' => $code,
            'parameterArray' => $parameterArray
        );
    }

    /**
     * Получить массив ошибок
     *
     * @return array
     *
     * @deprecated getErrorsArray()
     */
    public function getErrors() {
        return $this->getErrorsArray();
    }

    /**
     * Получить массив ошибок
     *
     * @param bool $full
     *
     * @return array
     */
    public function getErrorsArray($full = false) {
        if ($full) {
            return $this->_errorFullArray;
        } else {
            return $this->_errorsArray;
        }
    }

    /**
     * Получить количество ошибок
     *
     * @return int
     */
    public function getCount() {
        return count($this->getErrorsArray());
    }

    public function __toString() {
        if (class_exists('DebugException')) {
            return DebugException::Display($this, __CLASS__);
        }

        return parent::__toString();
    }

    /**
     * Записать полученный exception в log-файл
     *
     * @deprecated
     */
    public function log($file = false) {

    }

    public function setCode($code) {
        $this->code = $code;
    }

    /**
     * Метод, позволяющий выводить необходимый текст, генерируемый на уровне возникновения ошибки
     */
    public function getErrorText () {
        return $this->_errorText;
    }

    private $_errorsArray = array();

    private $_errorFullArray = array();

    private $_errorText = '';

}