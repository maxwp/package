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
    public function execute($request = false) {
        if (!$request) {
            $request = self::GetRequest();
        }

        $routing = self::GetRouting();
        $responce = self::GetResponse();

        try {
            $className = $routing->matchClassName($request);
            // в этой точке мы нашли класс который надо запустить
        } catch (Exception $e) {
            // сюда мы прилетаем если не нашли класс который запустить (ошибка 404)
            $className = 'error404'; // @todo надо смотреть на url 404
            Engine::Get()->getResponse()->setHTTPStatus404();
        }

        // после того, как query определил точку входа
        // выбрасываем событие
        $event = Events::Get()->generateEvent('afterQueryDefine');
        $event->notify();

        // формируем ответ
        $responce->setContentType('text/html; charset=utf-8');

        try {
            $html = $this->_run($className);

            $responce->setBody($html);

        } catch (Exception $ex500) {
            // если есть событие и обработчики afterEngineError - то перенаправляем вывод
            if (Events::Get()->hasEvent('afterEngineException')) {
                $event = Events::Get()->generateEvent('afterEngineException');
                $event->setException($ex500);
                $event->notify();
            } else {
                // иначе все как обычно - fatal в экран
                Engine::Get()->getResponse()->setHTTPStatus('500 Internal Server Error');
                throw $ex500;
            }
        }

        // после того как все отработалось, генерим событие final
        // после которого вся отработка уже в Engine::GetResponce()
        $event = Events::Get()->generateEvent('afterEngineFinal');
        $event->notify();

        return $responce;
    }

    private function _run($className) {
        $content = self::GetContentDriver()->getContent($className);

        // записываем ссылку на контент в Engine
        $this->setContentCurrent($content);

        $html = self::GetContentDriver()->renderTree($content);

        // если в процессе обработки контента поменялся указатель contentID,
        // значит повторяем вызов рекурсивно, пока не будет изменений
        $newClassName = get_class($this->getContentCurrent());
        if ($newClassName != $className) {
            return $this->_run($newClassName);
        }

        return $html;
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
     * Get Engine Request
     *
     * @return Engine_Request
     */
    public static function GetRequest() {
        return Engine_Request::Get();
    }

    /**
     * @return Engine_Routing
     */
    public static function GetRouting() {
        return Engine_Routing::Get();
    }

    /**
     * Получить систему ответа Engine.
     *
     * @return Engine_Response
     */
    public function getResponse() {
        return Engine_Response::Get();
    }

    /**
     * Получить шаблонизатор Smarty
     * @todo
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

    // @todo может в систему роутинга перенести?
    public function setContentCurrent($content) {
        if ($content instanceof Engine_Content) {
            $this->_content = $content;
        } else {
            $content = Engine::GetContentDriver()->getContent($content);
            $this->_content = $content;
        }
    }

    /**
     * @return Engine_Content
     */
    public function getContentCurrent() {
        return $this->_content;
    }

    private $_content;

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

    private $_smarty = null;

    private $_configFieldArray = array();

    private $_mediaPath = '';

    private $_mediaDirectory = '';

    private $_errorReporting = false;

}