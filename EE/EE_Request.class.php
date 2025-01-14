<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class EE_Request implements EE_IRequest {

    // @todo допилить и убрать старое говно

    public function __construct($url, $host, $GET, $POST, $FILES, $COOKIE) {
        // сначала задаем хост
        $this->_setHost($host);

        // затем переменные
        $this->_setArguments($GET, $POST, $FILES);

        // затем уже URL, потому что в URL могут быть GET-параметры
        // и нам их надо перетереть
        $this->_setTotalUrl($url); // @$_SERVER['REQUEST_URI']

        $this->_cookie = $COOKIE;
    }

    public function getCOOKIEArray() {
        return $this->_cookie;
    }

    /**
     * Устанавливает GET параметры
     * при создании парсера путем передачи URL строки
     *
     * @param string $getstring
     *
     * @author Ramm
     */
    protected function _stringGETParser($getstring) {
        $temp = explode('&', $getstring);
        foreach ($temp as $param) {
            $p = explode('=', $param);
            if (isset($p[1])) {
                $_GET[$p[0]] = $p[1];
            } else {
                $_GET[$p[0]] = '';
            }
        }
    }

    /**
     * Установка аргументов передаваемых странице посредством суперглобальных массивов
     * Здесь происходит очистка суперглобальных массивов
     * Должен вызываться при старте
     *
     * @author Ramm
     * @author Max
     * @author Vova (found bugs)
     */
    protected function _setArguments($GETArray, $POSTArray, $FILESArray) {
        $this->_argumentArray = [];

        foreach ($FILESArray as $key => $val) {
            if (is_array($val['tmp_name'])) {
                foreach ($val['tmp_name'] as $key2 => $name) {
                    if (is_uploaded_file($val['tmp_name'][$key2])) {
                        $this->_argumentArray[$key][$key2] = [new EE_RequestFile($val['tmp_name'][$key2], $val['name'][$key2]), self::ARG_SOURCE_FILE];
                    }
                }
            } else {
                if (is_uploaded_file($val['tmp_name'])) {
                    $files[$key] = [new EE_RequestFile($val['tmp_name'], $val['name']), self::ARG_SOURCE_FILE];
                }
            }
        }

        foreach ($GETArray as $key => $value) {
            $this->_argumentArray[$key] = [$value, self::ARG_SOURCE_GET];
        }

        foreach ($POSTArray as $key => $value) {
            $this->_argumentArray[$key] = [$value, self::ARG_SOURCE_POST];
        }

        // очищаем массивы GET/POST/FILES,
        // чтобы не повадно было с ними работать :-)
        $_FILES = [];
        $_GET = [];
        $_POST = [];
        $_ENV = [];
        $_REQUEST = [];
        $_SERVER['argv'] = [];
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['REDIRECT_QUERY_STRING'] = '';
        //$GLOBALS = []; // в php8 запрещено его менять
    }

    /**
     * Устанавливает "чистый" URL-запрос и GET строку
     * Должен вызываться при старте
     *
     * @param string $url
     */
    protected function _setTotalUrl($url) {
        $temp = explode('?', $url);
        $this->_totalURL = $temp[0];
        while (substr_count($this->_totalURL, '//')) {
            $this->_totalURL = str_replace('//', '/', $this->_totalURL);
        }
        if (isset($temp[1])) {
            $this->_stringGET = $temp[1];
        }
    }

    /**
     * Установить хост
     * Должен вызываться при старте
     *
     * @author Ramm
     */
    protected function _setHost($host) {
        $this->_host = $host;
    }

    /**
     * Получить локальную часть URL
     *
     * @return string
     */
    public function getLocal() {
        return $this->_local;
    }

    /**
     * GetMatchURL
     *
     * @return string
     */
    public function getMatchURL() {
        $url = $this->_totalURL;

        if ($this->_local) {
            $url = preg_replace("/^".str_replace('/', '\/', preg_quote($this->_local))."/", '', $url);
        }

        return $url;
    }

    /**
     * Возвращает "чистый" URL запрос
     *
     * @author Ramm
     * @return string
     */
    public function getTotalURL() {
        return $this->_totalURL;
    }

    /**
     * Возвращает часть URL запроса, которая содержит содержит GET параметры
     *
     * @return string
     */
    public function getGETString() {
        return $this->_stringGET;
    }

    public function getURL() {
        return $this->getMatchURL();
    }

    /**
     * Возвращает хост
     *
     * @return string
     */
    public function getHost() {
        return $this->_host;
    }

    /**
     * Возвращает аргументы передаваемые странице
     *
     * @return array
     */
    public function getArgumentArray() {
        return $this->_argumentArray;
    }

    /**
     * Возвращает аргумент по ключу
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws EE_Exception
     */
    public function getArgument($key, $source = false) {
        // проверка чтобы был такой аргумент
        if (empty($this->_argumentArray[$key])) {
            throw new EE_Exception("Argument {$key} is missing");
        }

        // опциональная проверка на источник
        if ($source && $this->_argumentArray[$key][1] != $source) {
            throw new EE_Exception("Argument {$key} source is not equal to {$source}");
        }

        return $this->_argumentArray[$key][0];
    }

    /**
     * Добавить агрумент.
     * Метод добавлен по инициативе.
     *
     * @param string $key
     * @param mixed $value
     *
     * @author Max
     */
    public function setArgument($key, $value) {
        $this->_argumentArray[$key] = $value;
    }

    /**
     * Возвращает ПОЛНЫЙ URL с GET параметрами (если они были переданы)
     *
     * @return string
     */
    public function getCurrentURL() {
        $url = $this->getTotalURL();
        if ($this->getGETString()) {
            $url .= '?' . $this->getGETString();
        }
        return $url;
    }

    /**
     * Задать локальную часть URL'a, которую необходимо отбрасывать при анализе
     */
    public function setLocal($local) {
        $this->_local = $local;
    }

    protected $_host;

    /**
     * "Чистый" URL, без get'a
     *
     * @author Ramm
     *
     * @var string
     */
    protected $_totalURL = '';

    /**
     * Часть URL запроса содержащая GET параметры
     *
     * @author Ramm
     *
     * @var string
     */
    protected $_stringGET = '';

    protected $_argumentArray = [];

    private $_cookie = [];

    /**
     * Локальная часть URL
     *
     * @todo
     * @deprecated
     * @var string
     */
    protected $_local = false;

}