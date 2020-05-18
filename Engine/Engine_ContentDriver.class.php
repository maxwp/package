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
 * Класс управления выводом контентов
 *
 * @copyright WebProduction
 * @author    DFox
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @package   Engine
 */
class Engine_ContentDriver {

    /**
     * Вернуть готовое html-содержимое контента и всех его вложений
     * с учетом иерархии
     *
     * @param Engine_Content $content
     * @return string
     */
    public function renderTree(Engine_Content $content) {
        $html = $this->renderOne($content);

        $moveTo = $content->getField('moveto');
        $moveAs = $content->getField('moveas');

        if ($moveTo) {
            $moveToContent = $this->getContent($moveTo);
            if ($moveAs) {
                $moveToContent->setValue($moveAs, $html);
            }
            return $this->renderTree($moveToContent);
        }

        return $html;
    }

    /**
     * Вернуть объект контента по его ID
     *
     * @param string $className
     * @param bool $cacheObject Сохранить ли объект во внутреннем кеш-pool'e?
     *
     * @return Engine_Content
     */
    public function getContent($className, $cacheObject = true) {
        if (!$className) {
            throw new Engine_Exception('Empty className');
        }

        if (is_object($className)) {
            throw new Engine_Exception('Object className');
        }

        if (empty($this->_contentArray[$className])) {
            $content = new $className();

            // кешируем объект
            if ($cacheObject) {
                $this->_contentArray[$className] = $content;
            }

            // вызываем все пре-процессоры
            // @todo возможно не правильная точка вызова
            $event = Events::Get()->generateEvent('beforeContentProcess');
            $event->setContent($content);
            $event->notify();

            return $content;
        }

        return $this->_contentArray[$className];
    }

    /**
     * Узнать, был ли загружен/вызван контент
     *
     * @param string $contentID
     * @return bool
     */
    public function isContentLoaded($contentID) {
        return isset($this->_contentArray[$contentID]);
    }

    /**
     * Обработать и вернуть содержимое контента $contentID
     *
     * @param Engine_Content $content
     * @return string
     */
    public function renderOne($content) {
        $content->process();

        // вызываем все пост-процессоры
        $event = Events::Get()->generateEvent('afterContentProcess');
        $event->setContent($content);
        $event->notify();

        // если html-файла нет - то нет смысла продолжать
        $file = $content->getField('filehtml');
        if (!$file) {
            return '';
        }

        // получаем все параметры, которые надо передать в smarty
        $a = $content->getValueArray();

        $arguments = Engine::GetRequest()->getArgumentArray();
        foreach ($arguments as $name => $value) {
            if (is_array($value)) {
                continue;
            }
            $a['arg_'.$name] = $value;
            if ($content->isControlValue($name)) {
                $a['control_'.$name] = htmlspecialchars($value);
            }
        }

        // передаем все параметры еще раз, в виде массива
        $a['contentValueArray'] = $a;

        // рендерим контент
        $html = Engine::GetSmarty()->fetch($file, $a);

        // генерируем событие afterRender
        $event = Events::Get()->generateEvent('afterContentRender');
        $event->setContent($content);
        $event->setRenderHTML($html);
        $event->notify();

        // достаем новый $html
        $html = $event->getRenderHTML();

        return $html;
    }

    /**
     * Получить объект Engine_ContentDriver'a
     *
     * @return Engine_ContentDriver
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    private function __construct() {

    }

    private function __clone() {

    }

    /**
     * Instance
     *
     * @var Engine_ContentDriver
     */
    private static $_Instance = null;

    /**
     * Массив созданных контентов
     * array of Engine_Content
     *
     * @var array
     */
    private $_contentArray = array();

}