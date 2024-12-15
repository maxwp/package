<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Реализация класса работающего с запросом
 * для древовидной структуры сайта
 */
class EE_Request implements EE_IRequest {

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
        $files = array();
        foreach ($FILESArray as $file => $val) {
            if (is_array($val['tmp_name'])) {
                foreach ($val['tmp_name'] as $key => $name) {
                    if (is_uploaded_file($val['tmp_name'][$key])) {
                        $files[$file]['name'][$key] = $val['name'][$key];
                        $files[$file]['type'][$key] = $val['type'][$key];
                        $files[$file]['tmp_name'][$key] = $val['tmp_name'][$key];
                        $files[$file]['error'][$key] = $val['error'][$key];
                        $files[$file]['size'][$key] = $val['size'][$key];
                    }
                }
            } else {
                if (is_uploaded_file($val['tmp_name'])) {
                    $files[$file]['name'] = $val['name'];
                    $files[$file]['type'] = $val['type'];
                    $files[$file]['tmp_name'] = $val['tmp_name'];
                    $files[$file]['error'] = $val['error'];
                    $files[$file]['size'] = $val['size'];
                }
            }
        }

        $a = array_merge(array_merge($files, $GETArray), $POSTArray);

        $this->_argumentArray = $a;
        $this->_argumentsPost = $POSTArray;
        $this->_argumentsGet = $GETArray;
        $this->_argumentsFile = $FILESArray;

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
        $this->totalURL = $temp[0];
        while (substr_count($this->totalURL, '//')) {
            $this->totalURL = str_replace('//', '/', $this->totalURL);
        }
        if (isset($temp[1])) {
            $this->stringGET = $temp[1];
        }
    }

    /**
     * Установить хост
     * Должен вызываться при старте
     *
     * @author Ramm
     */
    protected function _setHost($host) {
        $this->host = $host;
    }

    /**
     * Получить локальную часть URL
     *
     * @return string
     */
    public function getLocal() {
        return $this->local;
    }

    /**
     * GetMatchURL
     *
     * @return string
     */
    public function getMatchURL() {
        $url = $this->totalURL;

        if ($this->local) {
            $url = preg_replace("/^".str_replace('/', '\/', preg_quote($this->local))."/", '', $url);
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
        return $this->totalURL;
    }

    /**
     * Возвращает часть URL запроса, которая содержит содержит GET параметры
     *
     * @return string
     */
    public function getGETString() {
        return $this->stringGET;
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
        return $this->host;
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
    public function getArgument($key, $argType = false) {
        $argType = strtolower($argType);

        if ($argType == self::ARG_TYPE_POST) {
            if (!isset($this->_argumentsPost[$key])) {
                throw new EE_Exception("Argument {$key} is missing");
            }
        } elseif ($argType == self::ARG_TYPE_GET) {
            if (!isset($this->_argumentsGet[$key])) {
                throw new EE_Exception("Argument {$key} is missing");
            }
        } elseif ($argType == self::ARG_TYPE_PUT) {
            // @todo
        } elseif ($argType == self::ARG_TYPE_DELETE) {
            // @todo
        } elseif ($argType == self::ARG_TYPE_FILE) {
            if (!isset($this->_argumentsFile[$key])) {
                throw new EE_Exception("Argument {$key} is missing");
            }

            if (empty($this->_argumentsFile[$key]['tmp_name'])
                || !is_uploaded_file($this->_argumentsFile[$key]['tmp_name'])) {
                throw new EE_Exception("Argument {$key} is missing");
            }
        } else {
            if (!isset($this->_argumentArray[$key])) {
                throw new EE_Exception("Argument {$key} is missing");
            }
        }

        return $this->_argumentArray[$key];
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
     * Возвращает аргумент по ключу.
     * Без генерации исключения.
     * В случае его отсутствия - тогда вернет false.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getArgumentSecure($key, $argType = false) {
        $argType = strtolower($argType);

        if ($argType == self::ARG_TYPE_POST) {
            if (!isset($this->_argumentsPost[$key])) {
                return false;
            }
        } elseif ($argType == self::ARG_TYPE_GET) {
            if (!isset($this->_argumentsGet[$key])) {
                return false;
            }
        } elseif ($argType == self::ARG_TYPE_PUT) {
            // @todo
        } elseif ($argType == self::ARG_TYPE_DELETE) {
            // @todo
        } elseif ($argType == self::ARG_TYPE_FILE) {
            if (!isset($this->_argumentsFile[$key])) {
                return false;
            }
        } else {
            if (!isset($this->_argumentArray[$key])) {
                return false;
            }
        }

        return $this->_argumentArray[$key];
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
     *
     * @param string $local
     */
    public function setLocal($local) {
        $this->local = $local;
    }

    /**
     * Указатель на экземпляр объекта в системе (шаблон Singleton)
     *
     * @var Engine_URLParser
     */
    protected static $Instance = null;

    protected $host;

    /**
     * "Чистый" URL, без get'a
     *
     * @author Ramm
     *
     * @var string
     */
    protected $totalURL = '';

    /**
     * Часть URL запроса содержащая GET параметры
     *
     * @author Ramm
     *
     * @var string
     */
    protected $stringGET = '';

    /**
     * Массив аргументов страници
     * Фактически это объединение POST и GET параметров с преимуществом первых
     *
     * @author Ramm
     *
     * @var array
     */
    protected $_argumentArray = [];
    protected $_argumentsGet = array();
    protected $_argumentsPost = array();
    protected $_argumentsFile = array();

    private $_cookie = array();

    /**
     * Локальная часть URL
     *
     * @todo
     * @deprecated
     * @var string
     */
    protected $local = false;

}