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
 * Движок Eventic Engine
 *
 * @copyright WebProduction
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @package   Engine
 */
class EE {

    private function __construct() {
        // задаем обработчик контентов по умолчанию
        $this->_smarty = new EE_Smarty();
    }

    /**
     * Вызвать движок.
     * Передаем параметр $request, получаем $responce
     *
     * @return EE_Responce
     */
    public function execute(EE_IRequest $request) {
        $event = Events::Get()->generateEvent('EE:execute:before');
        $event->notify();

        // сохраняем request в себе
        $this->_request = $request;

        // создаем чистый объект responce
        $responce = new EE_Response();

        // до того как сработал роутинг
        $event = Events::Get()->generateEvent('EE:routing:before');
        $event->notify();

        // получаем систему роутинга
        $routing = $this->getRouting();

        // по системе роутинга определяем что у нас за контент
        try {
            $className = $routing->matchClassName($request);
            // в этой точке мы нашли класс который надо запустить

            $responce->setHTTPStatus('200 OK');
        } catch (Exception $e) {
            // сюда мы прилетаем если не нашли класс который запустить
            // реально это ошибка 404
            // ставим класс 404
            $className = 'error404';
            $responce->setHTTPStatus('404 Not found');
        }

        // после того как сработал роутинг
        $event = Events::Get()->generateEvent('EE:routing:after');
        $event->notify();

        // формируем ответ
        try {
            // запускаем рендеринг ответа
            $html = $this->_run($className);

            // пишем ответ
            $responce->setBody($html);
            $responce->setContentType('text/html; charset=utf-8');
        } catch (Exception $ex500) {
            $responce->setHTTPStatus('500 Internal server error');

            // если есть событие и обработчики EE:execute:exception - то перенаправляем вывод
            if (Events::Get()->hasEvent('EE:execute:exception')) {
                $event = Events::Get()->generateEvent('EE:execute:exception');
                $event->setException($ex500);
                $event->notify();
            } else {
                // иначе все как обычно - fatal в экран как нибудь
                $this->getResponse()->setHTTPStatus('500 Internal Server Error');
                throw $ex500;
            }
        }

        $event = Events::Get()->generateEvent('EE:execute:after');
        $event->notify();

        // очищаем объекты request/responce
        $this->_request = false;
        $this->_responce = false;

        // очищаем все контенты
        // ради следующего запуска движка
        foreach ($this->_contentArray as $content) {
            $content->clear();
        }

        return $responce;
    }

    private function _run($className) {
        $content = $this->getContent($className);

        // записываем ссылку на контент в Engine
        $this->setContentCurrent($content);

        $html = $this->renderTree($content);

        // если в процессе обработки контента поменялся указатель contentID,
        // значит повторяем вызов рекурсивно, пока не будет изменений
        $newClassName = get_class($this->getContentCurrent());
        if ($newClassName != $className) {
            return $this->_run($newClassName);
        }

        return $html;
    }

    /**
     * @return EE_IRequest
     */
    public function getRequest() {
        return $this->_request;
    }

    /**
     * @return EE_IRouting
     */
    public function getRouting() {
        return $this->_routing;
    }

    public function setRouting(EE_IRouting $routing) {
        $this->_routing = $routing;
    }

    /**
     * Получить систему ответа Engine.
     *
     * @return EE_Response
     */
    public function getResponse() {
        return $this->_responce;
    }

    /**
     * @return EE_Smarty
     */
    public function getSmarty() {
        return $this->_smarty;
    }

    /**
     * Установить конфигурационное поле
     *
     * @param string $field
     * @param mixed $value
     */
    public function setConfigField($field, $value) {
        $field = trim($field);
        $this->_configFieldArray[$field] = $value;
    }

    /**
     * Получить значение конфигурационного поля
     *
     * @param string $field
     * @param string $typing
     *
     * @return mixed
     *
     * @throws EE_Exception
     */
    public function getConfigField($field, $typing = false) {
        $field = trim($field);
        if (isset($this->_configFieldArray[$field])) {
            $x = $this->_configFieldArray[$field];
            if ($typing) {
                $x = $this->typeArgument($x, $typing);
            }
            return $x;
        }
        throw new EE_Exception("ConfigField '{$field}' not exists");
    }

    /**
     * Безопастно получить значение конфигурационного поля
     *
     * @param string $field
     * @param string $typing
     *
     * @return mixed
     */
    public function getConfigFieldSecure($field, $typing = false) {
        $field = trim($field);
        if (isset($this->_configFieldArray[$field])) {
            $x = $this->_configFieldArray[$field];
            if ($typing) {
                $x = $this->typeArgument($x, $typing);
            }
            return $x;
        }
        return false;
    }

    /**
     * Привести аргумент к необходимому типу данных
     *
     * @param mixed $value
     * @param string $typing
     *
     * @return mixed
     */
    public function typeArgument($value, $typing) {
        if ($typing == 'string') {
            $value = (string) $value;
        }
        if ($typing == 'int') {
            $value = (int) $value;
        }
        if ($typing == 'bool') {
            if ($value == 'true') {
                $value = true;
            } elseif ($value == 'false') {
                $value = false;
            } else {
                $value = (bool) $value;
            }
        }
        if ($typing == 'array') {
            if (!$value) {
                $value = array();
            } elseif (!is_array($value)) {
                $value = (array) $value;
            }
        }
        if ($typing == 'float') {
            $value = preg_replace("/[^0-9\.\,]/ius", '', $value);
            $value = str_replace(',', '.', $value);
            $value = (float) $value;
        }
        if ($typing == 'date') {
            $x = strtotime($value);
            if (!$x || $x < 0) {
                $value = '';
            } else {
                $value = date('Y-m-d', $x);
            }
        }
        if ($typing == 'datetime') {
            $x = strtotime($value);
            if (!$x || $x < 0) {
                $value = '';
            } else {
                $value = date('Y-m-d H:i:s', $x);
            }
        }
        if ($typing == 'file') {
            if (isset($value['tmp_name'])) {
                $value = $value['tmp_name'];
            } else {
                $value = false;
            }
        }
        return $value;
    }

    // @todo может в систему роутинга перенести?
    public function setContentCurrent($content) {
        if ($content instanceof EE_Content) {
            $this->_content = $content;
        } else {
            $content = $this->getContent($content);
            $this->_content = $content;
        }
    }

    /**
     * @return EE_Content
     */
    public function getContentCurrent() {
        return $this->_content;
    }

    private $_content;

    /**
     * Вернуть готовое html-содержимое контента и всех его вложений
     * с учетом иерархии
     *
     * @param EE_Content $content
     * @return string
     */
    public function renderTree(EE_Content $content) {
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
     * @return EE_Content
     */
    public function getContent($className, $cacheObject = true) {
        if (!$className) {
            throw new EE_Exception('Empty className');
        }

        if (is_object($className)) {
            throw new EE_Exception('Object className');
        }

        if (empty($this->_contentArray[$className])) {
            $content = new $className();

            // кешируем объект
            if ($cacheObject) {
                $this->_contentArray[$className] = $content;
            }

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
     * @param EE_Content $content
     * @return string
     */
    public function renderOne($content) {
        $event = Events::Get()->generateEvent('EE:content.process:before');
        $event->setContent($content);
        $event->notify();

        $content->process();

        // вызываем все пост-процессоры
        $event = Events::Get()->generateEvent('EE:content.process:after');
        $event->setContent($content);
        $event->notify();

        // если html-файла нет - то нет смысла продолжать
        $file = $content->getField('filehtml');
        if (!$file) {
            return '';
        }

        // получаем все параметры, которые надо передать в smarty
        $a = $content->getValueArray();

        $arguments = $this->getRequest()->getArgumentArray();
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
        $event = Events::Get()->generateEvent('EE:content.render:before');
        $event->setContent($content);
        $event->setRenderHTML('');
        $event->notify();

        $html = $this->getSmarty()->fetch($file, $a);

        // генерируем событие afterRender
        $event = Events::Get()->generateEvent('EE:content.render:after');
        $event->setContent($content);
        $event->setRenderHTML($html);
        $event->notify();

        // достаем новый $html из события
        $html = $event->getRenderHTML();

        return $html;
    }

    /**
     * Получить объект движка Eventic Engine
     *
     * @return EE
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    /**
     * Instance of Eventic Engine
     *
     * @var EE
     */
    private static $_Instance = false;

    private $_smarty = null;

    private $_request = null;

    private $_responce = null;

    private $_routing = null;

    private $_configFieldArray = array();

    /**
     * Массив загруженных контентов
     * array of EE_Content
     *
     * @var array
     */
    private $_contentArray = array();

}