<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2016 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Событие, которое возникает если в Engine есть не отловленный Exception
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Engine
 */
class Engine_Event_Exception extends Events_Event {

    /**
     * Задать Exception
     *
     * @param Exception $exception
     */
    public function setException(Exception $exception) {
        $this->_exception = $exception;
    }

    /**
     * Получить Exception
     *
     * @return Exception
     */
    public function getException() {
        return $this->_exception;
    }

    private $_exception;

}