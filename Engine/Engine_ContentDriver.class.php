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
     * Получить содержимое контента
     *
     * @param mixed $contentID
     *
     * @return string
     */
    public function getString($contentID) {
        $queryStartID = $contentID;

        $contentData = Engine::GetContentDataSource()->getDataByID($contentID);

        $ttl = @$contentData['cache']['ttl'];
        $type = @$contentData['cache']['type'];

        // специальный тип кеша:
        // кешировать всю страницу полностью,
        // если есть юзер - то только контент (для всех юзеров)
        if ($type == 'page-content') {
            try {
                Engine::GetAuth()->getUser();
                $type = 'content';
            } catch (Exception $userEx) {
                $type = 'page';
            }
        }

        if ($ttl > 0 && $type == 'page') {
            $modifiersArray = @$contentData['cache']['modifiers'];

            try {
                $html = Engine::GetCache()->getData($type.$contentID, $modifiersArray);

                // данные получены, передаем заголовок last modified
                // с текущей датой
                /*$lastModifiedDate = gmdate('r').' GMT';
                Engine::Get()->getResponse()->setHeader('Last-Modified', $lastModifiedDate);
                Engine::Get()->getResponse()->setHeader("Cache-Control", "max-age={$ttl}, private");
                Engine::Get()->getResponse()->setHeader('Vary', "Accept-Encoding, User-Agent");*/
            } catch (Exception $e) {

            }
        }

        if (empty($html)) {
            // по заголовку X-engine-render определяем, показывать весь
            // контент или нет
            if (!empty($_SERVER['HTTP_X_ENGINE_RENDER'])) {
                $headerRenderType = $_SERVER['HTTP_X_ENGINE_RENDER'];
            } else {
                $headerRenderType = false;
            }

            if ($headerRenderType == 'content') {
                $html = $this->displayOne($contentID);
            } else {
                $html = $this->display($contentID);
            }

            // кешировать можно только если не поменялся contentID в процессе
            if (isset($modifiersArray) && Engine::Get()->getRequest()->getContentID() == $queryStartID) {
                try {
                    Engine::GetCache()->setData(
                        $type.$contentID,
                        $html,
                        $modifiersArray,
                        $ttl
                    );

                    // кеш сохранен
                    // выдаем заголовок last modified
                    /*$lastModifiedDate = gmdate('r').' GMT';
                    Engine::Get()->getResponse()->setHeader('Last-Modified', $lastModifiedDate);
                    Engine::Get()->getResponse()->setHeader("Cache-Control", "max-age={$ttl}, private");
                    Engine::Get()->getResponse()->setHeader('Vary', "Accept-Encoding, User-Agent");*/
                } catch (Exception $e) {

                }
            }
        }

        if (Engine::Get()->getRequest()->getContentID() != $queryStartID) {
            // если в процессе обработки контента поменялся указатель contentID,
            // значит повторяем вызов рекурсивно, пока не будет изменений
            return $this->getString(Engine::Get()->getRequest()->getContentID());
        }

        return $html;
    }

    /**
     * Вернуть готовое html-содержимое контента и всех его вложений
     * с учетом иерархии
     *
     * @param mixed $contentID
     *
     * @return string
     */
    public function display($contentID) {
        $str = $this->displayOne($contentID);
        $content = $this->getContent($contentID);
        $moveTo = $content->getField('moveto');
        $moveAs = $content->getField('moveas');

        if ($moveTo) {
            if ($moveAs) {
                $this->getContent($moveTo)->setValue($moveAs, $str);
            }
            return $this->display($moveTo);
        }
        return $str;
    }

    /**
     * Вернуть объект контента по его ID
     *
     * @param string $contentID
     * @param bool $cacheObject Сохранить ли объект во внутреннем кеш-pool'e?
     *
     * @return Engine_Content
     */
    public function getContent($contentID, $cacheObject = true) {
        // @todo: returns Engine_Content

        if (empty($this->_contentsArray[$contentID])) {
            $data = Engine_ContentDataSource::Get()->getDataByID($contentID);
            if (!$data) {
                throw new Engine_Exception("Content #{$contentID} not found in ContentDataSource", 1);
            }

            $classname = $data['fileclass'];
            if (!class_exists($classname) && $data['filephp']) {
                // если задан php-файл
                include_once($data['filephp']);
            }

            if (!class_exists($classname)) {
                throw new Engine_Exception("Content class '{$classname}' not found", 2);
            }

            $obj = new $classname($contentID);

            // устанавливаем все поля как значения из DataSource'a
            $obj->setFieldArray((array) $data);

            // для того, что-бы были объект, а не строки
            $obj->setField('filephp', $data['filephp']);
            $obj->setField('filehtml', $data['filehtml']);

            // кешируем объект
            if ($cacheObject) {
                $this->_contentsArray[$contentID] = $obj;
            }

            // вызываем все пре-процессоры
            $event = Events::Get()->generateEvent('beforeContentProcess');
            $event->setContent($obj);
            $event->notify();

            return $obj;
        }

        return $this->_contentsArray[$contentID];
    }

    /**
     * Узнать, был ли загружен/вызван контент
     *
     * @param string $contentID
     *
     * @return bool
     */
    public function isContentLoaded($contentID) {
        return isset($this->_contentsArray[$contentID]);
    }

    /**
     * Обработать и вернуть содержимое контента $contentID
     *
     * @param int $contentID
     *
     * @return string
     *
     * @deprecated
     *
     * @see render() in class Engine_Content
     */
    public function displayOne($contentID) {
        $data = Engine::GetContentDataSource()->getDataByID($contentID);
        if (!$data) {
            throw new Engine_Exception("Content #{$contentID} not found in ContentDataSource", 1);
        }

        $ttl = @$data['cache']['ttl'];
        $type = @$data['cache']['type'];
        $modifiersArray = @$data['cache']['modifiers'];

        // специальный тип кеша:
        // кешировать всю страницу полностью,
        // если есть юзер - то только контент (для всех юзеров)
        if ($type == 'page-content') {
            try {
                Engine::GetAuth()->getUser();
                $type = 'content';
            } catch (Exception $userEx) {
                $type = 'page';
            }
        }

        $obj = $this->getContent($contentID);

        if ($ttl > 0 && $type == 'content') {
            try {
                return Engine::GetCache()->getData($type.$contentID, $modifiersArray);
            } catch (Exception $e) {

            }
        }

        // если события выше стерли файл - то значит запускать процессор
        // нельзя
        // @todo: это похоже на костыль
        if ($obj->getField('filephp')) {
            $obj->process();
        }

        // вызываем все пост-процессоры
        $event = Events::Get()->generateEvent('afterContentProcess');
        $event->setContent($obj);
        $event->notify();

        // если html-файла нет - то нет смысла продолжать
        $file = $obj->getField('filehtml');
        if (!$file) {
            return '';
        }

        // получаем все параметры, которые надо передать в smarty
        $a = $obj->getValuesArray();

        $arguments = Engine::GetURLParser()->getArguments();
        foreach ($arguments as $name => $value) {
            if (is_array($value)) {
                continue;
            }
            $a['arg_'.$name] = $value;
            if ($obj->isControlValue($name)) {
                $a['control_'.$name] = htmlspecialchars($value);
            }
        }

        // передаем все параметры еще раз, в виде массива
        $a['contentValueArray'] = $a;

        // рендерим контент
        $html = Engine::GetSmarty()->fetch($file, $a);

        // генерируем событие afterRender
        $event = Events::Get()->generateEvent('afterContentRender');
        $event->setContent($obj);
        $event->setRenderHTML($html);
        $event->notify();

        // достаем новый $html
        $html = $event->getRenderHTML();

        if ($ttl > 0 && $type == 'content') {
            try {
                Engine::GetCache()->setData(
                    $type.$contentID,
                    $html,
                    $modifiersArray,
                    $ttl
                );
            } catch (Exception $e) {

            }
        }

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
    private $_contentsArray = array();

}