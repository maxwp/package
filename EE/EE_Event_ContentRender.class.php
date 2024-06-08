<?php
/**
 * Событие, которое вызывается после рендеринга контента шаблонизатором
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 * @copyright WebProduction
 * @package EE
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