<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Событие, которое вызывается после рендеринга контента шаблонизатором
 */
class EE_Event_ContentRender extends EE_Event_ContentProcess {

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