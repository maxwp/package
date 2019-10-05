<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Событие, которое вызывается после рендеринга контента шаблонизатором
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package Engine
 */
class Engine_Event_ContentRender extends Engine_Event_ContentProcess {

    /**
     * Задать результат рендеринга (html-код)
     *
     * @param string $html
     */
    public function setRenderHTML($html) {
        $this->_html = $html;
    }

    /**
     * Получить результат рендеринга (html-код)
     *
     * @return string
     */
    public function getRenderHTML() {
        return $this->_html;
    }

    private $_html;

}