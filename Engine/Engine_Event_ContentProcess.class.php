<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Событие, которое вызывается до и после обработки контента
 * (до и после вызова метода Engine_Content->process())
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package Engine
 */
class Engine_Event_ContentProcess extends Events_Event {

    /**
     * Задать контент, для которого вызвано событие
     *
     * @param Engine_Content $content
     */
    public function setContent(Engine_Content $content) {
        $this->_content = $content;
    }

    /**
     * Получить контент, для которого вызвано событие
     *
     * @return Engine_Content
     */
    public function getContent() {
        return $this->_content;
    }

    private $_content;

}