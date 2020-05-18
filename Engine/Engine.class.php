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
 * Движок Engine
 *
 * @copyright WebProduction
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @package   Engine
 */
class Engine {

    private function __construct() {
        // URLParser по умолчанию
        if (class_exists('Engine_URLParser')) {
            $this->setURLParser(Engine_URLParser::Get());
        }

        // LinkMaker по умолчанию
        if (class_exists('Engine_LinkMaker')) {
            $this->setLinkMaker(Engine_LinkMaker::Get());
        }
    }

    /**
     * Предварительная инициализация движка.
     * Определяются и проверяются необходимые константы,
     * подключаются и отрабатываются engine.config.php и engine.mode.php файлы.
     * Перед инициализацией движок уже должен быть подключен.
     *
     * Без вызова этого метода дальше нельзя работать с Engine::Get()->...
     *
     * @static
     * @access public
     * @throws Engine_Exception
     */
    public static function Initialize() {
        if (!Engine::Get()->getMediaPath()) {
            Engine::Get()->setMediaPath(dirname(__FILE__).'/../media/');
        }
        if (!Engine::Get()->getMediaDirectory()) {
            Engine::Get()->setMediaDirectory('/media/');
        }

        // регистрация существующих событий
        Events::Get()->addEvent('beforeContentProcess', 'Engine_Event_ContentProcess');
        Events::Get()->addEvent('afterContentProcess', 'Engine_Event_ContentProcess');
        Events::Get()->addEvent('afterContentRender', 'Engine_Event_ContentRender');
        Events::Get()->addEvent('afterEngineFinal', 'Events_Event');
        Events::Get()->addEvent('afterEngineException', 'Engine_Event_Exception');
        Events::Get()->addEvent('afterQueryDefine', 'Events_Event');
        Events::Get()->addEvent('beforeContentLoad', 'Events_Event');
        Events::Get()->addEvent('afterContentLoad', 'Events_Event');

        // подключаем engine.init.php - файл, в котором можно досрочно
        // подключать некоторые пакеты (например, ConnectionManager) и т.п.
        // (В engine.init.php еще не заполнен Engine_DataSource!)
        $engineInitFilePath = dirname(__FILE__).'/../../engine.init.php';
        include($engineInitFilePath);

        // отключаем вывод ошибок
        self::Get()->disableErrorReporting();

        // подключаем конфигурационные файлы
        $filePath = dirname(__FILE__).'/../../engine.mode.php';
        include_once($filePath);

        $filePath = dirname(__FILE__).'/../../engine.config.php';
        include_once($filePath);
    }

    /**
     * Вызвать движок и отработать всю схему от получения запроса, до вывода информации.
     * Метод вернет готовый html-код.
     *
     * @return Engine_Response
     */
    public function execute() {
        // по умолчанию достаем все аргументы и определяем страницу
        $parser = self::GetURLParser();
        $query = $this->getRequest();
        $argumentsArray = $parser->getArguments();
        $languageCurrent = $this->getLanguage();
        if ($languageCurrent) {
            $argumentsArray['engine-language'] = $languageCurrent;
        }

        // получаем контент
        $contentID = $query->defineContentID($parser->getMatchURL(), $argumentsArray);

        if ($contentID) {
            $query->setContentID($contentID);
        } else {
            $query->setContentNotFound();
        }

        // после того, как query определил точку входа
        // выбрасываем событие
        $event = Events::Get()->generateEvent('afterQueryDefine');
        $event->notify();

        // задаем параметры вывода
        $this->getResponse()->setContentType('text/html; charset=utf-8');
        try {
            $this->getResponse()->setBody(
                self::GetContentDriver()->getString(
                    $this->getRequest()->getContentID()
                )
            );
        } catch (Exception $ex500) {
            // если есть событие и обработчики afterEngineError - то перенаправляем вывод
            if (Events::Get()->hasEvent('afterEngineException')) {
                $event = Events::Get()->generateEvent('afterEngineException');
                $event->setException($ex500);
                $event->notify();
            } else {
                // иначе все как обычно - fatal в экран
                throw $ex500;
            }
        }

        // после того как все отработалось, генерим событие final
        // после которого вся отработка уже в Engine::GetResponce()
        $event = Events::Get()->generateEvent('afterEngineFinal');
        $event->notify();

        return $this->getResponse();
    }

    /**
     * Получить драйвер контентов
     *
     * @static
     * @return Engine_ContentDriver
     */
    public static function GetContentDriver() {
        return Engine_ContentDriver::Get();
    }

    /**
     * Получить блок управления head-содержимым страницы
     *
     * @return Engine_HTMLHead
     */
    public static function GetHTMLHead() {
        return Engine_HTMLHead::Get();
    }

    /**
     * Получить встроенную систему кеширования Engine
     *
     * @return Engine_Cache
     */
    public static function GetCache() {
        return Engine_Cache::Get();
    }

    /**
     * Получить объект авторизации
     *
     * @return Engine_Auth
     * @static
     */
    public static function GetAuth() {
        // @todo: что-то это все не красиво ужасно
        // никому не советую пока-что юзать этот метод ибо концепт
        if (!self::$_Auth) {
            self::$_Auth = new Engine_Auth();
        }
        return self::$_Auth;
    }

    /**
     * Get Engine Request
     *
     * @return Engine_Request
     */
    public function getRequest() {
        if (!$this->_request) {
            $this->_request = new Engine_Request();
        }
        return $this->_request;
    }

    /**
     * Получить драйвер контентов
     *
     * @static
     * @return Engine_ContentDataSource
     */
    public static function GetContentDataSource() {
        return Engine_ContentDataSource::Get();
    }

    /**
     * Получить URL-парсер
     *
     * @return Engine_IURLParser
     */
    public static function GetURLParser() {
        $urlParser = self::Get()->_urlParser;
        if ($urlParser) {
            return $urlParser;
        }
        throw new Engine_Exception("No URLParser in Engine!");
    }

    /**
     * Задать собственный URLParser для Engine
     *
     * @param Engine_IURLParser $urlParser
     */
    public function setURLParser(Engine_IURLParser $urlParser) {
        $this->_urlParser = $urlParser;
    }

    /**
     * Получить LinkMaker Engine
     *
     * @return Engine_ILinkMaker
     */
    public static function GetLinkMaker() {
        $linkmaker = self::Get()->_linkmaker;
        if ($linkmaker) {
            return $linkmaker;
        }
        throw new Engine_Exception("No LinkMaker in Engine!");
    }

    /**
     * Получить шаблонизатор Smarty
     *
     * @return Engine_Smarty
     */
    public static function GetSmarty() {
        if (!self::Get()->_smarty) {
            self::Get()->_smarty = new Engine_Smarty();
        }
        return self::Get()->_smarty;
    }

    /**
     * Задать собственный LinkMaker для Engine
     *
     * @param Engine_ILinkMaker $linkmaker
     */
    public function setLinkMaker(Engine_ILinkMaker $linkmaker) {
        $this->_linkmaker = $linkmaker;
    }

    /**
     * Получить генератор Engine-данных
     *
     * @return Engine_Generator
     */
    public static function GetGenerator() {
        return Engine_Generator::Get();
    }

    /**
     * Получить систему ответа Engine.
     *
     * @return Engine_Response
     */
    public function getResponse() {
        if (!$this->_response) {
            $this->_response = new Engine_Response();
        }
        return $this->_response;
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
     * Установить mediaPATH
     *
     * @param string $path
     */
    public function setMediaPath($path) {
        $path = trim($path);
        $this->_mediaPath = $path;
    }

    /**
     * Установить mediaDIR
     *
     * @param string $dir
     */
    public function setMediaDirectory($dir) {
        $dir = trim($dir);
        $this->_mediaDirectory = $dir;
    }

    /**
     * Получить полный путь к media
     *
     * @param string $field
     * @param string $typing
     *
     * @return mixed
     */
    public function getMediaPath() {
        return $this->_mediaPath;
    }

    /**
     * Получить директорию media
     *
     * @param string $field
     * @param string $typing
     *
     * @return mixed
     */
    public function getMediaDirectory() {
        return $this->_mediaDirectory;
    }

    /**
     * Получить значение конфигурационного поля
     *
     * @param string $field
     * @param string $typing
     *
     * @return mixed
     *
     * @throws Engine_Exception
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
        throw new Engine_Exception("ConfigField '{$field}' not exists");
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
     * Получить host текущего проекта
     *
     * @return string
     */
    public function getProjectHost() {
        $host = Engine::GET()->getConfigFieldSecure('project-host');
        if (!$host) {
            $host = @$_SERVER['HTTP_HOST'];
        }
        if (!$host) {
            return false;
        }
        return $host;
    }

    /**
     * Получить URL на корень проекта.
     * Аналогичен методу getProjectHost(), но дописывает
     * protocol-wrapper на начало
     *
     * @see getProjectHost()
     *
     * @return string
     */
    public function getProjectURL() {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            return 'https://'.$this->getProjectHost();
        }
        return 'http://'.$this->getProjectHost();
    }

    /**
     * Установить хост проекта по умолчанию
     * (нужен для cron-скриптов)
     *
     * @param string $host
     */
    public function setProjectHost($host) {
        $host = trim($host);
        if (!$host) {
            throw new Engine_Exception("Incorrent hostname");
        }

        $this->setConfigField('project-host', $host);
    }

    /**
     * Установить язык системы
     *
     * @param string $language
     */
    public function setLanguage($language) {
        $this->setConfigField('language', $language);
    }

    /**
     * Узнать текущий язык системы
     *
     * @return string
     */
    public function getLanguage() {
        return $this->getConfigFieldSecure('language');
    }

    /**
     * Задать движку массив доступных языков
     *
     * @param array $array
     *
     * @throws Engine_Exception
     */
    public function setLanguagesArray($array) {
        if (!is_array($array)) {
            throw new Engine_Exception("Incorrect language array");
        }
        $this->setConfigField('engine-languages-array', $array);
    }

    /**
     * Запросить у движка массив доступных языков
     *
     * @todo скорее всего нет надобности в Engine
     *
     * @return array
     */
    public function getLanguagesArray() {
        $a = $this->getConfigFieldSecure('engine-languages-array');
        if (!$a) {
            $a = array();
        }
        return $a;
    }

    /**
     * Включить отображение ошибок в движке.
     * Включать можно ТОЛЬКО для localhost (для всех)
     * или ТОЛЬКО для заданного юзера или IP.
     *
     * @param string $loginOrIP
     */
    public function enableErrorReporting($loginOrIP = false) {
        $ip = @$_SERVER['REMOTE_ADDR'];
        $login = @$_COOKIE['authlogin'];

        // если ошибка - то посто выходим
        /*if ($ip && !$loginOrIP && $ip != '127.0.0.1') {
            return;
        }*/

        if (!$ip || $ip == $loginOrIP || $login == $loginOrIP || ($ip == '127.0.0.1' && $loginOrIP == false)) {
            ini_set('display_errors', 'On');
            ini_set('error_reporting', E_ALL);

            $this->_errorReporting = true;
        }
    }

    /**
     * Выключить отображение ошибок в движке
     */
    public function disableErrorReporting() {
        ini_set('display_errors', 'Off');
        ini_set('error_reporting', null);

        $this->_errorReporting = false;
    }

    /**
     * Получить состояние отображения ошибок
     *
     * @return bool
     */
    public function getErrorReporting() {
        return $this->_errorReporting;
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

    /**
     * Получить объект движка (Engine)
     *
     * @return Engine
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new Engine();
        }
        return self::$_Instance;
    }

    /**
     * Instance of Engine
     *
     * @var Engine
     */
    private static $_Instance = false;

    private static $_Auth = null;

    private $_request = null;

    private $_response = null;

    private $_urlParser = null;

    private $_linkmaker = null;

    private $_smarty = null;

    private $_configFieldArray = array();

    private $_mediaPath = '';

    private $_mediaDirectory = '';

    private $_errorReporting = false;

}