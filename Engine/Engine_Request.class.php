<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Реализация класса работающего с запросом
 * для древовидной структуры сайта
 *
 * @author DFox (idea)
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 *
 * @copyright WebProduction
 *
 * @package Engine
 */

class Engine_Request {

    public function __construct() {
        // по умолчанию пытаемся поставить контент 404
        $this->setContentIDNotFound(404);
    }

    /**
     * Получить идентификатор контента,
     * который обрабатывает (собирается обрабатывать) Engine
     *
     * @return string
     */
    public function getContentID() {
        return $this->_contentID;
    }

    /**
     * Установить "контент 404" - перключить движок на 404ю страницу
     */
    public function setContentNotFound() {
        Engine::Get()->getResponse()->setHTTPStatus404();
        $this->setContentID($this->getContentIDNotFound());
    }


    /**
     * Установить "контент 500" - переключить движок на 500ю страницу
     */
    public function setContentServerError() {
        Engine::Get()->getResponse()->setHTTPStatus('500 Internal Server Error');
        $this->setContentID($this->getContentIDServerError());
    }

      /**
       * Получить contentID, который установится в случае
       * отсутствия подходящего контента
       *
       * @return string
       */
    public function getContentIDNotFound() {
        return $this->_contentID404;
    }

    /**
     *Получить ID в случае 500 ошибки
     *
     * Author: Maesh Kyryll
     *
     * @return string
     */
    public function getContentIDServerError() {
        return $this->_contentID500;
    }

    /**
     * Установить contentID, который установится в случае
     * отсутствия подходящего контента
     *
     * @param string $contentID
     */
    public function setContentIDNotFound($contentID) {
        $this->_contentID404 = $contentID;
    }

    /**
     * Установить идентифиактор контента
     * (переключить движок на контент)
     * Вызов этого метода внутри работы -
     * по сути абсолютный редирект
     *
     * @param string $contentID
     */
    public function setContentID($contentID) {
        $this->_contentID = $contentID;
    }

    /**
     * Задать URL index-страницы, который будет использован,
     * если запращивается страница /
     * По умолчанию /index.html
     *
     * @param string $url
     */
    public function setContentURLIndex($url) {
        $this->_contentURLIndex = $url;
    }

    /**
     * Получить URL, который будет считаться первым,
     * если будет запрошена страница /
     * По умолчанию /index.html
     *
     * @return string
     */
    public function getContentURLIndex() {
        return $this->_contentURLIndex;
    }

    /**
     * Задать GURL index-страницы который будет использован,
     * если запращивается страница /
     *
     * @param $gurl
     * @param array $params
     *
     * @throws Engine_Exception
     */
    public function setContentGURLIndex($gurl, array $params = array()) {
        $this->_contentURLIndex = Engine::GetLinkMaker()->makeURLByContentIDParams(
            $gurl,
            $params
        );
    }

    /**
     * По переданному URL и аргументам определить ID контента
     * и вернуть его.
     *
     * Если контент не будет найден - метод вернет false.
     *
     * @param string $url
     * @param array $args
     * @param bool $return Вернуть или записать в _contentID?
     *
     * @return string
     *
     * @todo сделать чтобы возвращал exception
     */
    public function defineContentID($url, $args = array(), $return = false) {
        if ($url == '/') {
            $url = $this->getContentURLIndex();
        }

        // перебираем все данные из источника
        $data = Engine::GetContentDataSource()->getData();
        foreach ($data as $a) {
            // получаем URL
            if (!$a['url']) {
                continue;
            }

            $urlsArray = $a['url'];
            if (!is_array($urlsArray)) {
                $urlsArray = array($urlsArray);
            }

            foreach ($urlsArray as $murl) {
                // так как preg-выражения долгие, то используем их только если на то есть повод
                $found = false;
                if (substr_count($murl, '{')) {
                    $this->_callbackArray = array();

                    // убираем из URL'a все необязательные параметры вида [*]
                    $this->_callbackReturn = "(.*?)";

                    // заменяем в URL'е все {*} конструкции на (.*?) и запоминаем порядок их следования
                    $this->_callbackReturn = "([^/]+?)";
                    $murl = preg_replace_callback("/\{(.*?)\}/is", array($this, '_callbackPregMatchURL'), $murl);
                    $murl = str_replace('/', '\/', $murl);
                    $murl = "/^{$murl}$/u";

                    if (preg_match($murl, $url, $r)) {
                        $found = true;

                        // url подошел - необходимо найти и создать все обязательные аргументы
                        $urlParams = $this->_callbackArray;
                        foreach ($urlParams as $index => $name) {
                            if (isset($r[$index+1])) {
                                $value = $r[$index+1];

                                // var_dump($name); // - это может быть регулярное выражение
                                // var_dump($value); // - значение

                                if (substr_count($name, '{')) {
                                    $expression = "{$name}";
                                    // заменяем в URL'е все {*} конструкции на (.*?) и запоминаем порядок их следования
                                    $this->_callbackArray = array();
                                    $this->_callbackReturn = "([^/]+?)";
                                    $expression = preg_replace_callback(
                                        "/\{(.*?)\}/is",
                                        array($this, '_callbackPregMatchURL'),
                                        $expression
                                    );
                                    $expression = str_replace('/', '\/', $expression);
                                    $urlParams2 = $this->_callbackArray;

                                    if (preg_match("/{$expression}/", $value, $rx)) {
                                        foreach ($urlParams2 as $index2 => $name2) {
                                            if (!isset($rx[$index2+1])) {
                                                continue;
                                            }

                                            // print_r($name2);
                                            // var_dump($rx[$index2+1]);
                                            // добавляем агрумент в URLParser
                                            Engine::GetURLParser()->setArgument($name2, $rx[$index2+1]);
                                            // добавляем агрумент в $args
                                            // хотя скорее всего это и не нужно при правильном проектировании
                                            $args[$name2] = $rx[$index2+1];
                                        }
                                    }
                                } else {
                                    // добавляем агрумент в URLParser
                                    Engine::GetURLParser()->setArgument($name, $value);
                                    // добавляем агрумент в $args
                                    $args[$name] = $value;
                                }
                            }
                        }
                    }
                } else {
                    // обычное сравнение
                    if ($murl == $url) {
                        $found = true;
                    }
                }

                if (!$found) continue;

                // проверяем агрументы
                $params_ok = true;
                $argumentsArray = $a['arguments'];
                foreach ($argumentsArray as $argname) {
                    if (!isset($args[$argname])) {
                        $params_ok = false;
                    }
                }

                if (!$params_ok) continue;

                if ($return) {
                    return $a['id'];
                }
                return $a['id'];
            }
        }

        if ($return) {
            // @todo: exception?
            return false;
        }

        return false;
    }

    private function _callbackPregMatchURL($paramArray) {
        $param = trim($paramArray[1]);
        if (!$param) {
            throw new Engine_Exception("Empty param in match URL!");
        }
        $this->_callbackArray[] = $param;

        return $this->_callbackReturn;
    }

    private $_callbackArray = array();

    private $_callbackReturn;

    private $_contentURLIndex = '/index.html';

    private $_contentID;

    private $_contentID404 = 404;

    private $_contentID500 = 500;

}