<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software; you cannot redistribute it and/or
 * modify it.
 */

/**
 * Текстовый процессор.
 * Позволяет выполнять операции над любыми текстовыми данными.
 * Операции выполняют команды-обработчики (TextProcessor_IAction).
 *
 * ООП-паттерн: Facade + Action
 *
 * Например, есть коллекции обработчиков, которые позволяют
 * обрабатывать
 * - bbcode to html
 * - texile to html
 * - csv to html
 * - txt to html
 * - подсвечивать в тексте ссылки, emailы
 * - и так далее: любые текстовые данные в любые текстовые данные.
 *
 * Текстовые данные - это не бинарные данные.
 * Текстовые данные - данные, которые может прочитать и понять человек.
 *
 * @todo textprocessor == action!
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */
class TextProcessor {

    /**
     * Выполнить все процессоры и вернуть обработанный текст (код)
     *
     * @param string $text Текст на обработку
     * @return string
     */
    public function process($text = false) {
        $actionsArray = $this->getActionsArray();
        foreach ($actionsArray as $action) {
            $text = $action->process($text);
        }
        return $text;
    }

    /**
     * Добавить обработчик (action)
     *
     * @param TextProcessor_IAction $processor
     */
    public function addAction(TextProcessor_IAction $action) {
        if (!in_array($action, $this->_actionsArray)) {
            $this->_actionsArray[] = $action;
        }
    }

    /**
     * Получить массив обработчиков
     *
     * @return array of TextProcessor_IAction
     */
    public function getActionsArray() {
        return $this->_actionsArray;
    }

    private $_actionsArray = array();

}