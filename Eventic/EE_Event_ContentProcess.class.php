<?php
/**
 * Событие, которое вызывается до и после обработки контента
 * (до и после вызова метода EE_Content->process())
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 * @copyright WebProduction
 * @package EE
 */
class EE_Event_ContentProcess extends Events_Event {

    /**
     * Задать контент, для которого вызвано событие
     *
     * @param EE_Content $content
     */
    public function setContent(EE_Content $content) {
        $this->_content = $content;
    }

    /**
     * Получить контент, для которого вызвано событие
     *
     * @return EE_Content
     */
    public function getContent() {
        return $this->_content;
    }

    private $_content;

}