<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2014 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package Engine
 */
class Engine_Content {

    /**
     * Установить значение в контент.
     * Если secure - то автоматически делается htmlspecialchars
     *
     * @param string $key
     * @param mixed $value
     * @param bool $secure
     */
    public function setValue($key, $value, $secure = false) {
        if (!$key) {
            throw new Engine_Exception("Empty key name. Nothing to set");
        }

        if ($secure && $value) {
            $value = htmlspecialchars($value);
        }

        $this->_valueArray[$key] = $value;
    }

    /**
     * Получить значение контента
     *
     * @param string $key
     * @return mixed
     */
    public function getValue($key) {
        if (!$key) {
            throw new Engine_Exception("Empty key name. Nothing to get");
        }

        if (isset($this->_valueArray[$key])) {
            return $this->_valueArray[$key];
        }

        return false;
    }

    /**
     * Добавить значений в контент (массово)
     *
     * @param array $a
     */
    public function addValuesArray($a) {
        if (!$this->_valueArray) {
            $this->_valueArray = $a;
        } else {
            $this->_valueArray = array_merge($this->_valueArray, $a);
        }
    }

    /**
     * Получить все установленные поля
     * 2D-array {key => value}
     *
     * @return array
     */
    public function getValuesArray() {
        return $this->_valueArray;
    }

    public function __construct($contentID) {
        $this->_contentID = $contentID;
    }

    /**
     * Получить ID текущего контента
     *
     * @return string
     */
    public function getContentID() {
        return $this->_contentID;
    }

    public function process() {

    }

    /**
     * Отрисовать контент (отрендерить в html-код).
     *
     * @return string
     */
    public function render() {
        return Engine::GetContentDriver()->displayOne($this->getContentID());
    }

    /**
     * Получить поле контента.
     * Поля - это те же value, но они не передаются в шаблонизатор
     *
     * @param string $key
     * @return mixed
     */
    public function getField($key) {
        if ($key && isset($this->_fieldArray[$key])) {
            return $this->_fieldArray[$key];
        }
        return false;
    }

    /**
     * Задать поле контента.
     * Поля - это те же value, но они не передаются в шаблонизатор
     *
     * @param string $key
     * @param mixed $value
     */
    public function setField($key, $value) {
        $this->_fieldArray[$key] = $value;
    }

    /**
     * Задать поля контента массово.
     *
     * @param array $fieldArray
     */
    public function setFieldArray($fieldArray) {
        if (!$this->_fieldArray) {
            $this->_fieldArray = $fieldArray;
        } else {
            $this->_fieldArray = array_merge($this->_fieldArray, $fieldArray);
        }
    }

    /**
     * Текуший идентификатор контента
     *
     * @var string
     */
    protected $_contentID;

    protected $_valueArray = array();

    protected $_fieldArray = array();

}