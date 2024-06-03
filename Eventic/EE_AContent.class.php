<?php
/**
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 * @copyright WebProduction
 * @package EE
 */
abstract class EE_AContent implements EE_IContent {

    public function __construct() {
        $this->clear();
    }

    /**
     * Получить аргумент из запроса (POST, GET, FILES).
     * Если аргумента нет - будет EE_Exception
     *
     * @param string $name
     * @param mixed $typing
     *
     * @return mixed
     */
    public function getArgument($name, $typing = false, $argType = false) {
        $x = EE::Get()->getRequest()->getArgument($name, $argType);
        if ($typing) {
            $x = StringUtils_Typing::TypeString($x, $typing);
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
        $x = EE::Get()->getRequest()->getArgumentSecure($name, $argType);
        if ($typing) {
            $x = StringUtils_Typing::TypeString($x, $typing);
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
        return EE::Get()->getRequest()->getArgumentArray();
    }

    /**
     * Установить значение в контент.
     * Если secure - то автоматически делается htmlspecialchars
     *
     * @param string $key
     * @param mixed $value
     */
    public function setValue($key, $value) {
        if (!$key) {
            throw new EE_Exception("Empty key name. Nothing to set");
        }


        $this->_valueArray[$key] = $value;
    }

    public function setValueSecure($key, $value) {
        $this->setValue($key, htmlspecialchars($value));
    }

    /**
     * Получить значение контента
     *
     * @param string $key
     * @return mixed
     */
    public function getValue($key) {
        if (!$key) {
            throw new EE_Exception("Empty key name. Nothing to get");
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

    abstract public function process();

    /**
     * Отрисовать контент (отрендерить в html-код).
     *
     * @return string
     */
    public function render() {
        $event = Events::Get()->generateEvent('EE:content.process:before');
        $event->setContent($this);
        $event->notify();

        // @todo может ли контент что-то вернуть?
        $this->process();

        // вызываем все пост-процессоры
        $event = Events::Get()->generateEvent('EE:content.process:after');
        $event->setContent($this);
        $event->notify();

        return $this->getValueArray();
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

    public function clear() {
        $this->_valueArray = [];
        $this->_fieldArray = [];
    }

    protected $_valueArray = [];

    protected $_fieldArray = [];

}