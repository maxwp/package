<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Event - событие.
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Events
 */
class Events_Event {

    /**
     * Добавить наблюдателя за событием.
     * Можно имя класса, можно Events_EventObserver
     *
     * @param mixed $observer
     * @param string $parameter
     */
    public function addObserver($observer, $parameter = false) {
        if (is_object($observer) && $parameter) {
            throw new Events_Exception('Can not add observer objects with parameters');
        }

        if (is_object($observer)) {
            $this->_observerArray[] = $observer;
        } else {
            $this->_observerArray[] = array($observer, $parameter);
        }
    }

    /**
     * Получить массив наблюдателей за событием.
     *
     * @return array of Events_IEventObserver
     */
    public function getObserverArray() {
        foreach ($this->_observerArray as $index => $observer) {
            // если это класс с параметром - то заменяем его на объект
            if (!is_object($observer)) {
                $classname = $observer[0];
                $param = $observer[1];
                $observer = new $classname($param);
                $this->_observerArray[$index] = $observer;
            }
        }
        return $this->_observerArray;
    }

    /**
     * Удалить наблюдателя из события.
     * Можно имя класса, можно Events_EventObserver
     *
     * @param mixed $observer
     */
    public function deleteObserver($observer) {
        foreach ($this->_observerArray as $index => $v) {
            if ($observer == $v) {
                unset($this->_observerArray[$index]);
            }
        }
    }

    /**
     * Уведомить всех наблюдателей о событии.
     * Наблюдателю передается полностью объект события Event.
     *
     * Если передать $secure = true, то каждый вызов обработчика будет заключен
     * в try-catch (то есть, изолированным)
     *
     * @param bool $secure
     */
    public function notify($secure = false) {
        $observerArray = $this->getObserverArray();
        foreach ($observerArray as $observer) {
            if ($secure) {
                try {
                    $observer->notify($this);
                } catch (Exception $ex) {
                    print $ex;
                }
            } else {
                $observer->notify($this);
            }
        }
    }

    private $_observerArray = [];

}