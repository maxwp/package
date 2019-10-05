<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Родительный класс для всех классов, которые привязуются к контенту
 *
 * @author DFox
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 *
 * @copyright WebProduction
 *
 * @package Engine
 */
class Engine_Class extends Engine_Content {

    /**
     * Получить авторизированного пользователя
     *
     * @return User
     */
    public function getUser() {
        return Engine::getAuth()->getUser();
    }

    /**
     * Безопастно получить авторизированного пользователя.
     * Либо User, либо null.
     *
     * @return User
     */
    public function getUserSecure() {
        try {
            return $this->getUser();
        } catch (Exception $e) {

        }
        return null;
    }

    /**
     * Узнать, авторизирован ли юзер
     *
     * @return bool
     */
    public function isUserAuthorized() {
        try {
            $user = $this->getUser();
            if (!$user) {
                return false;
            }

            return true;
        } catch (Exception $e) {

        }
        return false;
    }

    /**
     * Получить данные текущего контента.
     * Данные получаются из ContentDataSource
     *
     * @return Engine_ContentDataArray
     */
    public function getContentData() {
        return Engine::GetContentDataSource()->getDataByID($this->getContentID());
    }

    /**
     * Построить URL на текущую страницу (контент)
     *
     * @param array $paramsArray
     *
     * @return string
     */
    public function makeURL($paramsArray = array()) {
        return Engine::GetLinkMaker()->makeURLCurrentByReplaceParams($paramsArray);
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
        $value = Engine::GetURLParser()->getArgumentSecure($controlName, $argType);
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
        $x = Engine::GetURLParser()->getArgument($name, $argType);
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
        $x = Engine::GetURLParser()->getArgumentSecure($name, $argType);
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
    public function getArguments() {
        return Engine::GetURLParser()->getArguments();
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

    private $_controlArray = array();

    private $_controlUnsetArray = array();

}