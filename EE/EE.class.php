<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Движок Eventic Engine
 */
class EE extends Pattern_ASingleton {

    protected function __construct() {
        // регистрация событий которые понимает Eventic Engine
        // @todo попрравить на нормальный :class
        Events::Get()->addEvent('EE:content.process:before', 'EE_Event_ContentProcess');
        Events::Get()->addEvent('EE:content.process:after', 'EE_Event_ContentProcess');
        Events::Get()->addEvent('EE:content.render:before', 'EE_Event_ContentRender');
        Events::Get()->addEvent('EE:content.render:after', 'EE_Event_ContentRender');
        Events::Get()->addEvent('EE:routing:before', 'Events_Event');
        Events::Get()->addEvent('EE:routing:after', 'Events_Event');
        Events::Get()->addEvent('EE:execute:before', 'Events_Event');
        Events::Get()->addEvent('EE:execute:exception', 'EE_Event_Exception');
        Events::Get()->addEvent('EE:execute:after', 'Events_Event');
    }

    /**
     * Вызвать движок
     * Передаем параметр $request, получаем $response
     */
    public function execute(EE_IRequest $request, EE_IResponse $response) {
        $event = Events::Get()->generateEvent('EE:execute:before');
        $event->notify();

        // сохраняем request в себе
        // это нужно чтобы в процессе работы движка любой контент мог получить доступ к Request
        $this->_request = $request;

        // создаем чистый объект response
        // это нужно чтобы в процессе работы движка любой контент мог получить доступ к Response
        $this->_response = $response;

        // до того как сработал роутинг
        $event = Events::Get()->generateEvent('EE:routing:before');
        $event->notify();

        // получаем систему роутинга
        // она должна быть инициирована заранее
        $routing = $this->getRouting();

        // по системе роутинга определяем что у нас за контент
        try {
            $className = $routing->matchContent($request);

            // в этой точке мы нашли класс который надо запустить,
            // причем роутинг сам должен вернуть класс или класс-404,
            // в противном случае он должен вернуть пустоту или бросить exception - и это будет считаться ошибкой 500

            // на всякий случай проверяем чтобы роутинг не вернул пустоту
            if (!$className) {
                throw new EE_Exception("Routing returned null");
            }

            // по умолчанию ставится код 200, но в процессе его можно поменять
            $this->getResponse()->setCode(200);
        } catch (Exception $routingException) {
            $this->getResponse()->setCode(500);
            $this->getResponse()->setData($routingException->getMessage());
            $className = 'ee500'; // штатный контент
        }

        // после того как сработал роутинг
        $event = Events::Get()->generateEvent('EE:routing:after');
        $event->notify();

        // формируем ответ
        try {
            // запускаем рендеринг ответа
            $data = $this->_run($className);

            // пишем ответ
            $this->getResponse()->setData($data);
        } catch (Exception $ex500) {
            // что-то пошло не так
            $this->getResponse()->setCode(500);

            // если есть событие и обработчики EE:execute:exception - то перенаправляем вывод
            if (Events::Get()->hasEvent('EE:execute:exception')) {
                $event = Events::Get()->generateEvent('EE:execute:exception');
                $event->setException($ex500);
                $event->notify();
            } else {
                // иначе все как обычно - fatal в экран как нибудь
                throw $ex500;
            }
        }

        $event = Events::Get()->generateEvent('EE:execute:after');
        $event->notify();

        // очищаем все контенты,
        // это нужно следующего запуска движка в режиме non-stop
        foreach ($this->_contentArray as $content) {
            $content->reset();
        }

        // очищаем объекты request/response
        // чтобы движок был готов к следующему запуску
        $this->_request = false;
        $this->_response = false;
    }

    private function _run($className) {
        // получаем объект
        $content = $this->getContent($className);

        // записываем ссылку на контент в Engine
        $this->setContentCurrent($content);

        $data = $this->renderTree($content);

        // Если в процессе обработки контента поменялся указатель на запускаемый контент,
        // значит повторяем вызов рекурсивно.
        // Это нужно чтобы контент мог в процессе сказать "нет, сейчас запускаем что-то другое"
        $newClassName = get_class($this->getContentCurrent());
        if ($newClassName != $className) {
            return $this->_run($newClassName);
        }

        return $data;
    }

    /**
     * @return EE_IRequest
     */
    public function getRequest() {
        if (!$this->_request) {
            throw new EE_Exception('Request object not set');
        }

        return $this->_request;
    }

    /**
     * @return EE_IRouting
     */
    public function getRouting() {
        // @todo эти все проверки надо делать init в самом начале и не задрачивать потом ifами код
        if (!$this->_routing) {
            throw new EE_Exception('Routing object not set');
        }

        return $this->_routing;
    }

    public function setRouting(EE_IRouting $routing) {
        $this->_routing = $routing;
    }

    /**
     * @return EE_Response
     */
    public function getResponse() {
        if (!$this->_response) {
            throw new EE_Exception('Response object not set');
        }

        return $this->_response;
    }

    public function setContentCurrent($content) {
        if ($content instanceof EE_IContent) {
            $this->_contentCurrent = $content;
        } else {
            $content = $this->getContent($content);
            $this->_contentCurrent = $content;
        }
    }

    /**
     * @return EE_IContent
     */
    public function getContentCurrent() {
        return $this->_contentCurrent;
    }

    private $_contentCurrent;

    /**
     * Вернуть готовое html-содержимое контента и всех его вложений
     * с учетом иерархии
     *
     * @param EE_IContent $content
     * @return string
     */
    public function renderTree(EE_IContent $content) {
        $data = $content->render();

        $moveTo = $content->getValue('moveto');
        $moveAs = $content->getValue('moveas');

        if ($moveTo) {
            $moveToContent = $this->getContent($moveTo);
            if ($moveAs) {
                $moveToContent->setValue($moveAs, $data);
            }
            return $this->renderTree($moveToContent);
        }

        return $data;
    }

    /**
     * Вернуть объект имени класса
     *
     * @template T of EE_IContent
     * @param class-string<T> $className
     * @param bool $cache
     * @return T
     */
    public function getContent(string $className, $cache = true) {
        if (!$className) {
            throw new EE_Exception('Empty className');
        }

        if (is_object($className)) {
            throw new EE_Exception('Classname is an object');
        }

        if (empty($this->_contentArray[$className])) {
            $content = new $className();

            // кешируем объект
            if ($cache) {
                $this->_contentArray[$className] = $content;
            }

            return $content;
        }

        return $this->_contentArray[$className];
    }

    /**
     * Узнать, был ли загружен/вызван контент
     *
     * @param string $content
     * @return bool
     */
    public function isContentLoaded($content) {
        return isset($this->_contentArray[$content]);
    }

    private $_request = null;

    private $_response = null;

    private $_routing = null;

    /**
     * Массив загруженных контентов
     *
     * @var EE_IContent[] $_contentArray
     */
    private $_contentArray = [];

}