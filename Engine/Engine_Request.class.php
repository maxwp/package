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

class Engine_Request implements Engine_IURLParser {

    protected function __construct() {
        $this->_setArguments();
        $this->_setTotalUrl(@$_SERVER['REQUEST_URI']);
        $this->_setHost();
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
     * Получить хеш текущего request'a
     * Для текущей открытой страницы получить идентификатор, который
     * с большой степенью вероятности будет ее однозначно идентифицировать
     *
     * @return string
     */
    public function makeURLID() {
        return @md5($this->getTotalURL().serialize($this->getArguments()));
    }

    /**
     * Снять экранирование с массива, рекурсивно
     *
     * @param array $a
     *
     * @return array
     */
    protected function _unescapeArray($a) {
        foreach ($a as $k => $v) {
            if (is_array($v)) {
                $a[$k] = $this->_unescapeArray($v);
            } else {
                $a[$k] = stripslashes($v);
            }
        }
        return $a;
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
    protected function _setArguments() {
        $files = array();
        foreach ($_FILES as $file => $val) {
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

        $a = array_merge(array_merge($files, $_GET), $_POST);

        if (get_magic_quotes_gpc()) {
            // если включены magic quotes - вручную снимаем экранирование
            $a = $this->_unescapeArray($a);
        }

        $this->arguments = $a;
        $this->argumentsPost = $_POST;
        $this->argumentsGet = $_GET;
        $this->argumentsFile = $_FILES;

        // очищаем массивы GET/POST/FILES,
        // чтобы не повадно было с ними работать :-)
        $_FILES = array();
        $_GET = array();
        $_POST = array();
        $_SERVER['argv'] = array();
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['REDIRECT_QUERY_STRING'] = '';
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
    protected function _setHost() {
        $this->host = @$_SERVER['HTTP_HOST'];
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
        return $this->arguments;
    }

    /**
     * Возвращает аргумент по ключу
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws Engine_Exception
     */
    public function getArgument($key, $argType = false) {
        $argType = strtolower($argType);

        if ($argType == 'post') {
            if (!isset($this->argumentsPost[$key])) {
                throw new Engine_Exception("Argument {$key} is missing");
            }
        } elseif ($argType == 'get') {
            if (!isset($this->argumentsGet[$key])) {
                throw new Engine_Exception("Argument {$key} is missing");
            }
        } elseif ($argType == 'file') {
            if (!isset($this->argumentsFile[$key])) {
                throw new Engine_Exception("Argument {$key} is missing");
            }
        } else {
            if (!isset($this->arguments[$key])) {
                throw new Engine_Exception("Argument {$key} is missing");
            }
        }

        return $this->arguments[$key];
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
        $this->arguments[$key] = $value;
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

        if ($argType == 'post') {
            if (!isset($this->argumentsPost[$key])) {
                return false;
            }
        } elseif ($argType == 'get') {
            if (!isset($this->argumentsGet[$key])) {
                return false;
            }
        } elseif ($argType == 'file') {
            if (!isset($this->argumentsFile[$key])) {
                return false;
            }
        } else {
            if (!isset($this->arguments[$key])) {
                return false;
            }
        }

        return $this->arguments[$key];
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
    protected $arguments = array();
    protected $argumentsGet = array();
    protected $argumentsPost = array();
    protected $argumentsFile = array();

    /**
     * Локальная часть URL
     *
     * @var string
     */
    protected $local = false;

    /**
     * @return Engine_Request
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    private static $_Instance = null;

}