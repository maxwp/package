<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Abstract content
 */
abstract class EE_AContent implements EE_IContent {

    /**
     * Получить входящий аргумент
     * Если аргумента нет - будет EE_Exception
     *
     * @param string $key
     * @param mixed $type
     *
     * @return mixed
     */
    public function getArgument($key, $type = false, $source = false) {
        if ($source && $source == EE_IRequest::ARG_SOURCE_INTERNAL) {
            // только внутренние аргументы
            $checkInternal = true;
            $checkExternal = false;
        } elseif ($source && $source != EE_IRequest::ARG_SOURCE_INTERNAL) {
            // только внешние
            // (ARG_SOURCE_EXTERNAL нет, внешними считаются GET/POST/... - все что не INTERNAL)
            $checkInternal = false;
            $checkExternal = true;
        } else {
            // все подряд
            $checkInternal = true;
            $checkExternal = true;
        }

        // сначала проверяем внутренние аргументы
        if ($checkInternal) {
            if (isset($this->_argumentArray[$key])) {
                $value = $this->_argumentArray[$key];

                // опциональная типизация
                if ($type) {
                    $value = EE_Typing::TypeString($value, $type);
                }

                return $value;
            }
        }

        // затем проверяю внешние аргументы
        if ($checkExternal) {
            $value = EE::Get()->getRequest()->getArgument($key, $source);

            // опциональная типизация
            if ($type) {
                $value = EE_Typing::TypeString($value, $type);
            }

            return $value;
        }

        throw new EE_Exception("Argument {$key} not found");
    }

    /**
     * Безопасно получить аргумент.
     * Если аргумента нет - будет false.
     *
     * @param string $key
     * @param mixed $type
     *
     * @return mixed
     * @see getArgument()
     *
     */
    public function getArgumentSecure($key, $type = false, $source = false) {
        try {
            return $this->getArgument($key, $type, $source);
        } catch (Exception $exception) {
            return EE_Typing::TypeString(false, $type);
        }
    }

    /**
     * Получить все внутренние аргументы
     *
     * @return array
     */
    public function getArgumentArray() {
        return $this->_argumentArray;
    }

    /**
     * Задать внутренних аргумент контенту
     *
     * @param $key
     * @param $value
     * @return void
     * @throws EE_Exception
     */
    public function setArgument($key, $value) {
        if (!$key) {
            throw new EE_Exception("Invalid argument key");
        }

        $this->_argumentArray[$key] = $value;
    }

    public function unsetArgument($key) {
        unset($this->_argumentArray[$key]);
    }

    public function unsetArgumentArray() {
        $this->_argumentArray = [];
    }

    /**
     * Установить значение в контент.
     * Если secure - то автоматически делается htmlspecialchars
     *
     * @param string $key
     * @param mixed $value
     */
    public function setValue($key, $value) {
        // @todo скорее всего будет отрефакторено при разделении Smarty vs Content based on EE_DataBus

        if (!$key) {
            throw new EE_Exception("Empty key name. Nothing to set");
        }

        $this->_valueArray[$key] = $value;
    }

    public function unsetValue($key) {
        unset($this->_valueArray[$key]);
    }

    public function unsetValueArray() {
        $this->_valueArray = [];
    }

    /**
     * Получить значение контента
     *
     * @param string $key
     * @return mixed
     */
    public function getValue($key) {
        if (!$key) {
            throw new EE_Exception('Empty key name');
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

    public function reset() {
        $this->unsetArgumentArray();
        $this->unsetValueArray();
    }

    private array $_valueArray = [];

    /**
     * массив внутренних аргументов
     * @todo registry?
     */
    private array $_argumentArray = [];

}