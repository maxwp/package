<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Событие, которое возникает если в Engine есть не отловленный Exception
 */
class EE_Event_Exception extends Events_Event {

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