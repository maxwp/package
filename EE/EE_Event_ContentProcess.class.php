<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Событие, которое вызывается до и после обработки контента
 * (до и после вызова метода EE_Content->process())
 */
class EE_Event_ContentProcess extends Events_Event {

    /**
     * Задать контент, для которого вызвано событие
     *
     * @param EE_AContent $content
     */
    public function setContent(EE_IContent $content) {
        $this->_content = $content;
    }

    /**
     * Получить контент, для которого вызвано событие
     *
     * @return EE_IContent
     */
    public function getContent() {
        return $this->_content;
    }

    private $_content;

}