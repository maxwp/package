<?php
/**
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 * @copyright WebProduction
 * @package EE
 */
abstract class EE_AContent implements EE_IContent {

    /**
     * Получить входящий аргумент
     * Если аргумента нет - будет EE_Exception
     *
     * @param string $key
     * @param mixed $argType
     *
     * @return mixed
     */
    public function getArgument($key, $argType = false) {
        if (isset($this->_argumentArray[$key])) {
            $x = $this->_argumentArray[$key];

            if ($argType) {
                $x = StringUtils_Typing::TypeString($x, $argType);
            }
        } else {
            $x = EE::Get()->getRequest()->getArgument($key, $argType);
        }

        return $x;
    }

    /**
     * Безопасно получить аргумент.
     * Если аргумента нет - будет false.
     *
     * @param string $key
     * @param mixed $argType
     *
     * @return mixed
     * @see getArgument()
     *
     */
    public function getArgumentSecure($key, $argType = false) {
        try {
            return $this->getArgument($key, $argType);
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Получить все возможные аргументы.
     * Вернется ассоциативный массив key-value.
     *
     * @return array
     */
    public function getArgumentArray() {
        return $this->_argumentArray;
    }

    public function setArgument($key, $value) {
        if (!$key) {
            throw new EE_Exception("Invalid argument key");
        }

        $this->_argumentArray[$key] = $value;
    }

    public function unsetArgument($key) {
        unset($this->_argumentArray[$key]);
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
     * @return mixed
     */
    public function render() {
        $event = Events::Get()->generateEvent('EE:content.process:before');
        $event->setContent($this);
        $event->notify();

        $this->process();

        // вызываем все пост-процессоры
        $event = Events::Get()->generateEvent('EE:content.process:after');
        $event->setContent($this);
        $event->notify();

        return $this->getValueArray();
    }

    public function clear() {
        $this->_valueArray = [];
        $this->_argumentArray = [];
    }

    protected $_valueArray = [];

    // массив локальных аргументов
    protected $_argumentArray = [];

}