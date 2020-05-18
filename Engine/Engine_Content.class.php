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

    public function __construct() {
        $filePHP = new ReflectionClass($this);
        $fileHTML = str_replace('.php', '.html', $filePHP->getFileName());
        $this->setField('filehtml', $fileHTML);
    }

    /**
     * Получить control-значение.
     * Метод проверяет, было ли ранее установлено control-value и
     * возвращает его значение.
     * Иначе работает как getArgumentSecure()
     *
     * @param string $controlName
     *
     * @see setControlValue()
     * @see getArgumentSecure()
     * @see Engine_IURLParser()
     *
     * @return mixed
     *
     * @throws Engine_Exception
     */
    public function getControlValue($controlName, $argType = false) {
        $argType = strtolower($argType);

        $controlName = trim($controlName);
        if (!$controlName) {
            throw new Engine_Exception("Empty control value name. Nothing to get");
        }
        if (isset($this->_controlArray[$controlName])) {
            return $this->_controlArray[$controlName];
        }
        $value = Engine::GetRequest()->getArgumentSecure($controlName, $argType);
        if ($value && !is_array($value)) {
            $value = trim($value);
        }
        return $value;
    }

    /**
     * Задать control-значение.
     * Метод записывает control-value во внутренний буфер текущиего контента,
     * а затем просто делает setValue() его.
     *
     * @param string $controlName
     * @param mixed $controlValue
     *
     * @throws Engine_Exception
     */
    public function setControlValue($controlName, $controlValue) {
        // @todo: возможно controlvalue стоит сделать общим static.

        if (is_object($controlName)) {
            throw new Engine_Exception("Empty control name must be a string");
        }

        if ($controlValue && is_object($controlValue)) {
            throw new Engine_Exception("Empty control value must be a string");
        }

        $controlName = trim($controlName);
        if (!$controlName) {
            throw new Engine_Exception("Empty control value name. Nothing to set");
        }

        $this->_controlArray[$controlName] = $controlValue;
        unset($this->_controlUnsetArray[$controlName]);

        $this->setValue('arg_'.$controlName, $controlValue);
        $this->setValue('control_'.$controlName, htmlspecialchars($controlValue));
    }

    /**
     * Удалить заданное ранее control-значение
     *
     * @param string $controlName
     *
     * @return string
     */
    public function unsetControlValue($controlName) {
        $controlName = trim($controlName);
        if (!$controlName) {
            throw new Engine_Exception("Empty control value name. Nothing to unset");
        }
        unset($this->_controlArray[$controlName]);
        $this->_controlUnsetArray[$controlName] = true;
    }

    /**
     * Доступно ли значение control-value
     * true - доступно
     * false - явно стерто
     *
     * @param string $controlName
     *
     * @return bool
     */
    public function isControlValue($controlName) {
        if (isset($this->_controlUnsetArray[$controlName])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Получить аргумент из запроса (POST, GET, FILES).
     * Если аргумента нет - будет Engine_Exception
     *
     * @param string $name
     * @param mixed $typing
     *
     * @return mixed
     */
    public function getArgument($name, $typing = false, $argType = false) {
        $x = Engine::GetRequest()->getArgument($name, $argType);
        if ($typing) {
            $x = Engine::Get()->typeArgument($x, $typing);
        }
        return $x;
    }

    /**
     * Безопасно получить аргумент.
     * Если аргумента нет - будет false.
     *
     * @param string $name
     * @param mixed $typing
     *
     * @see getArgument()
     *
     * @return mixed
     */
    public function getArgumentSecure($name, $typing = false, $argType = false) {
        $x = Engine::GetRequest()->getArgumentSecure($name, $argType);
        if ($typing) {
            $x = Engine::Get()->typeArgument($x, $typing);
        }
        return $x;
    }

    /**
     * Получить все возможные аргументы.
     * Вернется ассоциативный массив key-value.
     *
     * @return array
     */
    public function getArgumentArray() {
        return Engine::GetRequest()->getArgumentArray();
    }

    /**
     * Получить аргументы, ключ которых подходит под preg-pattern.
     * Вернется ассоциативный массив key-value.
     *
     * @param string $pattern
     * @param bool $match
     *
     * @return array
     */
    public function getArgumentsByPattern($pattern, $match = true) {
        $match;
        $arguments = $this->getArguments();
        $a = array();
        foreach ($arguments as $key => $value) {
            if (preg_match($pattern, $key, $r)) {
                $a[$r[1]] = $value;
            }
        }
        return $a;
    }

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
    public function addValueArray($a) {
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
    public function getValueArray() {
        return $this->_valueArray;
    }

    public function process() {

    }

    /**
     * Отрисовать контент (отрендерить в html-код).
     *
     * @return string
     */
    public function render() {
        return Engine::GetContentDriver()->renderOne($this);
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

    private $_controlArray = array();

    private $_controlUnsetArray = array();

}