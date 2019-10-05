<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2016 WebProduction <webproduction.ua>
 *
 * This program is commetcial software; you can not redistribute it and/or
 * modify it under any terms.
 */

/**
 * Событийная система Events.
 * Event system или Event Manager.
 * Events выступает в роли строителя-диспетчера-прототипов событий.
 *
 * Система Events может быть встроена во многие другие подсистемы
 * типа Engine, SQLObject, ...
 * Встраивание путем инкапсуляции (аггрегации) или наследования.
 *
 * Паттерны: Observer/Listener/Publish-Subscribe, Prototype.
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Events
 */
class Events {

    /**
     * Получить событие
     *
     * @param string $name
     *
     * @return Events_Event
     *
     * @throws Events_Exception
     */
    public function getEvent($name) {
        if (empty($this->_eventArray[$name])) {
            throw new Events_Exception("Event with name '{$name}' not found");
        }

        // если событие еще не инициировано - то инициируем его
        if (!is_object($this->_eventArray[$name])) {
            $classname = $this->_eventArray[$name];
            $this->_eventArray[$name] = $this->_cloneEvent($classname);

            // вешаем на него все обработчики, если они есть
            if (isset($this->_observerArray[$name])) {
                foreach ($this->_observerArray[$name] as $x) {
                    $this->_eventArray[$name]->addObserver($x[0], $x[1]);
                }
            }
        }

        return $this->_eventArray[$name];
    }

    /**
     * Проверить есть такое событие или нет в системе и есть ли у него обработчики.
     * (Иначе его нет смысла вызывать)
     * В отличии от getEvent() или generateEvent() текущий метод возвращает true/false
     * и работает БЫСТРЕЕ, что очень важно для производительности системы.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasEvent($name) {
        if (empty($this->_eventArray[$name])) {
            // если события нет - то false
            return false;
        }

        // если событие еще не ициниировано (new-clone)
        if (!is_object($this->_eventArray[$name])) {
            // и если на него нет обработчиков
            if (empty($this->_observerArray[$name])) {
                // то нет смысла его вызывать
                return false;
            }
        }

        return true;
    }

    /**
     * Сгенерировать событие на основе прототипа.
     * (Клонировать прототип и вернуть результат)
     *
     * ООП-паттерн: Prototype
     *
     * @param string $name
     *
     * @return Events_Event
     * @throws Events_Exception
     */
    public function generateEvent($name) {
        return clone $this->getEvent($name);
    }

    /**
     * Прицепить наблюдатель к событию.
     * Можно задать класс наблюдателя+параметр или объект
     *
     * @param string $eventName
     * @param mixed $observer
     * @param string $parameter
     *
     * @throws Events_Exception
     */
    public function observe($eventName, $observer, $parameter = false) {
        // если нет такого события - то будет ошибка
        if (empty($this->_eventArray[$eventName])) {
            throw new Events_Exception('Invalid event '.$eventName);
        }

        if (PackageLoader::Get()->getMode('check')
            && is_object($observer)
        ) {
            print "Warning! Using old-style observe for ".$eventName.". Please, use string.\n";
        }

        if (is_object($this->_eventArray[$eventName])) {
            // это обычный объект - вешаем обработчик сразу на него
            $this->getEvent($eventName)->addObserver($observer, $parameter);
        } else {
            // запоминаем обработчик, пока событие не будет вызвано
            $this->_observerArray[$eventName][] = array($observer, $parameter);
        }
    }

    /**
     * Зарегистрировать событие.
     * Можно передавать класс, а можно сразу объект события.
     * Рекомендуется передавать класс.
     *
     * @param string $name
     * @param mixed $event
     */
    public function addEvent($name, $event) {
        if (PackageLoader::Get()->getMode('check')
            && is_object($event)
        ) {
            print "Warning! Using old-style addEvent for ".$name.". Please, use string.\n";
        }

        if (!isset($this->_eventArray[$name])) {
            $this->_eventArray[$name] = $event;
        }
    }

    /**
     * Получить все события.
     * Метод вернет ассоциативный массив eventName:eventObject|eventClass.
     *
     * @return array
     */
    public function getEventArray() {
        return $this->_eventArray;
    }

    /**
     * Получить систему событий (Events)
     *
     * @return Events
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    /**
     * Создать событие с таким классом.
     * Так как clone быстрее чем new, то мы кешируем.
     *
     * @param string $classname
     *
     * @return Events_Event
     */
    private function _cloneEvent($classname) {
        if (!isset($this->_eventCloneArray[$classname])) {
            $this->_eventCloneArray[$classname] = new $classname();
        }

        return clone $this->_eventCloneArray[$classname];
    }

    /**
     * Массив существущих событий
     *
     * @var array
     */
    private $_eventArray = array();

    /**
     * Кеш оригинальных Events_Event объектов, из которых мы будем клонировать.
     *
     * @var unknown_type
     */
    private $_eventCloneArray = array();

    /**
     * Кеш наблюдателей, еще до того как вообще событие вызовется.
     *
     * @var array
     */
    private $_observerArray = array();

    /**
     * Instanse
     *
     * @var Events
     */
    private static $_Instance = null;

}