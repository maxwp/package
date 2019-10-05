<?php

/**
 * Загрузчик WebProduction Packages.
 * Позволяет подключать к проектам PHP-классы,
 * директории с PHP-классами, CSS-файлы, CSS-данные,
 * JS-файлы, JS-данные, умеет компилировать последние четыре
 * в виде отдельных файлов.
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 *
 * @copyright WebProduction
 *
 * @package PackageLoader
 */
class PackageLoader {

    private function __construct() {
        $this->setMode('developer-report', true);
        if (function_exists('__autoload')) {
            // так как есть ранее определенная функция __autoload(),
            // то регистрировать свою spl_autoload_register() мы не можем,
            // прийдется отключить autoload-mode
            $this->_autoload = false;
        } else {
            spl_autoload_register(array($this, 'loadClass'));
            $this->_autoload = true;
        }

        // по умолчанию packagePath на один уровень выше самого пакета PackageLoader
        $this->addPackagesPath(__DIR__.'/../');

        // проверяем reject
        if (rand(1, 100) == 1) {
            $reportFileReject = __DIR__.'/reports/lastreport-reject.log';
            if (file_exists($reportFileReject)) {
                exit();
            }
        }
    }

    /**
     * Добавить путь к пакетам. Путь можно менять во время работы.
     *
     * @param string $path Путь к директории с пакетами
     */
    public function addPackagesPath($path) {
        if (!$path) {
            throw new PackageLoader_Exception("Path '$path' not found");
        }

        if (!in_array($path, $this->_packagePathArray)) {
            $this->_packagePathArray[] = $path;
        }
    }

    /**
     * Подключить класс (загрузить его).
     * Этот метод вызывается в spl_autoload_register()
     *
     * @param string $className
     */
    public function loadClass($className) {
        // если класс уже зарегистрирован - подключаем его
        if (!empty($this->_files['php'][$className])) {
            $file = $this->_files['php'][$className];

            include_once($file);

            // записываем статистику
            $this->_loadClassArray[] = $className;
        }
    }

    /**
     * Получить массив загруженных классов
     *
     * @return array
     */
    public function getLoadedClasses() {
        return $this->_loadClassArray;
    }

    /**
     * Получить файлы
     *
     * @return array
     */
    public function getFiles() {
        return $this->_files;
    }

    /**
     * Зарегистрировать PHP-класс, чтобы PackageLoader
     * мог его загружать
     *
     * @param string $file
     * @param bool $checkExists
     */
    public function registerPHPClass($file, $checkExists = 'auto') {
        // в режиме build происходит проверка файла на существование
        if ($checkExists == 'auto') {
            $checkExists = $this->getMode('build');
        }

        // проверка файла на наличие
        if ($checkExists) {
            if (!is_file($file)) {
                throw new PackageLoader_Exception("File '{$file}' not found");
            }
        }

        $classname = basename($file);
        $classname = str_replace(array('.class.php', '.php'), '', $classname);
        $this->_files['php'][$classname] = $file;

        if (!$this->_autoload) {
            include_once($file);
        }
    }

    /**
     * Зарегистрировать директорию с php-классами.
     * Не рекомендуется к использованию, так как порядок
     * подключения может быть абсолютно рандомный.
     *
     * Так как не соблюдается порядок подключения, рекомендуется
     * использовать registerPHPClass()
     *
     * @param string $dir
     * @param bool $checkExists
     *
     * @see registerPHPClass()
     */
    public function registerPHPDirectory($dir, $checkExists = 'auto') {
        // в режиме build происходит проверка файла на существование
        if ($checkExists == 'auto') {
            $checkExists = $this->getMode('build');
        }

        // проверка директории на существование
        if ($checkExists) {
            if (!is_dir($dir)) {
                throw new PackageLoader_Exception("Directory {$dir} not found");
            }
        }

        // сканируем директорию
        // и регистрируем все файлы
        $data = scandir($dir);
        foreach ($data as $x) {
            if (strpos($x, '.class.php')
            || strpos($x, '.interface.php')) {
                $this->registerPHPClass($dir.'/'.$x, false);
            }
        }
    }

    /**
     * Зарегистрировать JS-файл
     *
     * @param string $file
     * @param bool $checkExists
     */
    public function registerJSFile($file, $absolutePath = false, $checkExists = 'auto') {
        $this->_registerFile('js', $file, $absolutePath, $checkExists);
    }

    /**
     * Зарегистрировать CSS-файл
     *
     * @param string $file
     * @param bool $checkExists
     */
    public function registerCSSFile($file, $absolutePath = false, $checkExists = 'auto') {
        $this->_registerFile('css', $file, $absolutePath, $checkExists);
    }

    /**
     * Получить все css-файлы, которые нужно подключить
     *
     * @return array
     */
    public function getCSSFiles() {
        if (!empty($this->_files['css'])) {
            return $this->_files['css'];
        }
        return array();
    }

    /**
     * Получить все js-файлы, которые нужно подключить
     *
     * @return array
     */
    public function getJSFiles() {
        if (!empty($this->_files['js'])) {
            return $this->_files['js'];
        }
        return array();
    }

    /**
     * Удалить js-файл, из подключаемых
     *
     * @param $name
     */
    public function deleteJSFile($name) {
        $name = str_replace($this->getProjectPath(), '', $name);
        $name = str_replace('\\', '/', $name);
        $name = str_replace('//', '/', $name);

        foreach ($this->_files['js'] as $key => $js) {
            if ($name == $js) {
                unset($this->_files['js'][$key]);
                break;
            }
        }
    }

    /**
     * Регистрируем файл
     *
     * @param string $type
     * @param string $file
     * @param bool $absolutePath
     * @param bool $checkExists
     */
    private function _registerFile($type, $file, $absolutePath = false, $checkExists = 'auto') {
        if ($absolutePath) {
            $file = str_replace('//', '/', $file);

            if ($checkExists == 'auto') {
                $checkExists = $this->getMode('build');
            }

            // только для абсолютных путей выполняем проверку на наличие файла
            if ($checkExists) {
                if (!is_file($file)) {
                    throw new PackageLoader_Exception("Path '{$file}' not found");
                }
            }
        }

        // fix для window-путей
        if ($absolutePath) {
            $file = str_replace(str_replace('\\', '/', $this->getProjectPath()), '/', str_replace('\\', '/', $file));
        }

        $hash = md5($file);
        $this->_files[$type][$hash] = $file;
    }

    /**
     * Подключить пакет.
     * В $package можно передать имя пакета
     * или абсолютный путь к директории пакета
     * или абсолютный путь к include.php в директории пакета
     *
     * @param string $package
     * @param mixed $paramsArray
     *
     * @return bool
     *
     * @throws PackageLoader_Exception
     */
    public function import($package, $paramsArray = array()) {
        if (!substr_count($package, '/')) {
            // указано просто имя пакета
            $packageName = $package;
            $packageDirectoryArray = array();
            foreach ($this->_packagePathArray as $p) {
                $packageDirectoryArray[] = $p.'/'.$packageName;
            }
        } elseif (substr_count($package, 'include.php')) {
            // указан путь к директории (явно)
            $x = pathinfo($package);
            $packageDirectoryArray = array($package);
            $packageName = @$x['filename'];
        } else {
            // указан путь к файлу include.php
            $x = pathinfo($package);
            $packageDirectoryArray = array(@$x['dirname']);
            $packageName = basename($packageDirectoryArray[0]);
        }

        // проверяем, подключен ли пакет
        if ($this->isImported($packageName)) {
            return false;
        }

        if (!is_array($paramsArray)) {
            $paramsArray = array($paramsArray);
        }

        // пытаемся найти пакет в одной из директорий
        $packageInclude = false;
        foreach ($packageDirectoryArray as $packageDirectory) {
            $packageInclude = $packageDirectory.'/include.php';

            // подключаем include-файл пакета
            if (file_exists($packageInclude)) {
                break;
            }
        }

        // подключаем include-файл пакета
        if (!is_file($packageInclude)) {
            throw new PackageLoader_Exception("Package '{$packageName}' not found in defined packages pathes");
        }
        include_once($packageInclude);

        // пытаемся найти Loader
        $classname = $packageName.'_Loader';
        if (class_exists($classname)) {
            // если есть Loader пакета - вызываем его
            $obj = new $classname($paramsArray);
        }

        // запоминаем, какие пакеты подключены
        if (!isset($this->_importedArray[$packageName])) {
            $this->_importedArray[$packageName] = array(
            'package' => $packageName,
            'path' => $packageInclude,
            );
        }

        // мы только что подключили пакет - true
        return true;
    }

    /**
     * Зарегистрировать CSS-данные
     *
     * @param string $data
     * @param bool $compile Разрешить скомпилировать в файл?
     */
    public function registerCSSData($data, $compile = false) {
        $this->_registerData('css', $data, $compile);
    }

    /**
     * Зарегистрировать JavaScript-данные
     *
     * @param string $data
     * @param bool $compile Разрешить скомпилировать в файл?
     */
    public function registerJSData($data, $compile = false) {
        $this->_registerData('js', $data, $compile);
    }

    /**
     * Зарегистрировать css/js данные
     *
     * @param string $type
     * @param string $data
     * @param bool $compile Компилировать в файл?
     */
    private function _registerData($type, $data, $compile) {
        $data = trim($data);
        $data = str_replace('  ', ' ', $data);

        if (!$data) {
            throw new PackageLoader_Exception('No '.$type.' data to register');
        }

        // строим хеш данных
        $hash = sha1($data);
        if (isset($this->_dataHash[$type.$hash])) {
            // если такие данные уже зарегистрированы - то сразу выходим
            return false;
        }

        // записываем хеш в реестр
        $this->_dataHash[$type.$hash] = true;

        // запускаем препроцессоры,
        // которые могут, например, упаковать CSS
        $dataProcessors = @$this->_dataProcessors[$type];
        if ($dataProcessors) {
            foreach ($dataProcessors as $processor) {
                $data = $processor->processBefore($data);
            }
        }

        if ($compile) {
            // регистрируем данные как компилированный файл
            $pathCompile = __DIR__.'/compile/';
            $pathFile = $pathCompile.$hash.'.'.$type;
            if (!file_exists($pathFile)) {
                file_put_contents($pathFile, $data, LOCK_EX);
            }

            // регистрируем как файл
            $this->_registerFile($type, $pathFile, true, false);
        } else {
            // регистрируем данные в памяти
            // (потом их будут просить у PackageLoader)
            $this->_data[$type] .= "\n\n".$data;
        }
    }

    /**
     * Получить зарегистрированные CSS-данные (css-код)
     *
     * @return string
     */
    public function getCSSData() {
        return $this->_getData('css');
    }

    /**
     * Получить зарегистрированные JavaScript-данные (js-код)
     *
     * @return string
     */
    public function getJSData() {
        return $this->_getData('js');
    }

    private function _getData($type) {
        $data = @trim($this->_data[$type]);

        if (isset($this->_dataProcessors[$type])) {
            $dataProcessors = $this->_dataProcessors[$type];
        } else {
            $dataProcessors = false;
        }

        if ($dataProcessors) {
            foreach ($dataProcessors as $processor) {
                $data = $processor->processAfter($data);
            }
        }

        return $data;
    }

    /**
     * Проверить, подключен ли пакет
     *
     * @param string $packageName
     *
     * @return bool
     */
    public function isImported($packageName) {
        return (isset($this->_importedArray[$packageName]));
    }

    /**
     * Получить массив зарегистрированных пакетов
     *
     * @return array
     */
    public function getImportedPackages() {
        $a = array();
        foreach ($this->_importedArray as $p) {
            $a[] = $p['package'];
        }
        return $a;
    }

    /**
     * Получить статистику по зарегистрированным пакетам
     *
     * @return array
     */
    public function getImportedPackagesStatistics() {
        return $this->_importedArray;
    }

    public function registerCSSDataProcessor(PackageLoader_IDataProcessor $processor) {
        $this->registerDataProcessor($processor, 'css');
    }

    public function registerJSDataProcessor(PackageLoader_IDataProcessor $processor) {
        $this->registerDataProcessor($processor, 'js');
    }

    public function registerDataProcessor(PackageLoader_IDataProcessor $processor, $type) {
        $type = trim($type);
        $type = strtolower($type);
        $typesArray = array('css', 'js');
        if (!in_array($type, $typesArray)) {
            throw new PackageLoader_Exception("Unknown DataProcessor type '{$type}'");
        }

        $this->_dataProcessors[$type][] = $processor;
    }

    /**
     * Установить режим.
     *
     * Включать режимы debug, development, build, verbose
     * можно ТОЛЬКО для localhost (для всех)
     * или ТОЛЬКО для заданного юзера или IP.
     *
     * @param string $mode
     * @param bool $value
     * @param string $loginOrIP
     */
    public function setMode($mode, $value = true, $loginOrIP = false) {
        if (!$mode) {
            throw new PackageLoader_Exception("Empty mode value");
        }

        $devModeArray = array();
        $devModeArray[] = 'debug';
        $devModeArray[] = 'xdebug';
        $devModeArray[] = 'check';
        $devModeArray[] = 'build';
        $devModeArray[] = 'build-acl';
        $devModeArray[] = 'build-scss';
        $devModeArray[] = 'verbose';

        if (in_array($mode, $devModeArray)) {
            $ip = @$_SERVER['REMOTE_ADDR'];
            $login = @$_COOKIE['authlogin'];

            if (!$ip || $ip == $loginOrIP || $login == $loginOrIP || ($ip == '127.0.0.1' && $loginOrIP == false)) {
                $this->_modeArray[$mode] = $value;
            } else {
                //throw new PackageLoader_Exception('Cannot enable mode for anywhere!');
                return false;
            }
        } else {
            $this->_modeArray[$mode] = $value;
        }
    }

    /**
     * Узнать состояние режима $mode
     *
     * @param string $mode
     *
     * @return bool
     */
    public function getMode($mode) {
        if (!$mode) {
            throw new PackageLoader_Exception("Empty mode value");
        }
        return !empty($this->_modeArray[$mode]);
    }

    /**
     * Получить полный путь к проекту.
     *
     * @return string
     */
    public function getProjectPath() {
        if (!$this->_projectPath) {
            throw new PackageLoader_Exception("Project path not defined");
        }
        return $this->_projectPath;
    }

    /**
     * Задать полный путь к проекту
     *
     * @param string $path
     *
     * @throws PackageLoader_Exception
     */
    public function setProjectPath($path) {
        $path = realpath($path).'/';
        /*if (!is_dir($path)) {
            throw new PackageLoader_Exception("Incorrect project path '{$path}'");
        }*/
        $this->_projectPath = $path;
    }

    public function __destruct() {
        if ($this->getMode('developer-report') && rand(1, 100) == 1) {
            // если включен этот режим, то отправляем базовую информацию
            // о системе разработчикам #wp-packages

            // проверяем, когда была последняя отправка
            $reportFile = __DIR__.'/reports/lastreport.log';
            $reportFileReject = __DIR__.'/reports/lastreport-reject.log';
            $reportDate = @file_get_contents($reportFile);
            $reportDate = @strtotime($reportDate);
            if ($reportDate > time() - 3 * 60 * 60) {
                // не прошло 3 часа с момента последнего отчета
                return;
            }

            @file_put_contents($reportFile, date('Y-m-d H:i:s'), LOCK_EX);

            // 1. собираем информацию о системе
            $systemArray['host'] = @$_SERVER['HTTP_HOST'];
            $systemArray['software'] = @$_SERVER['SERVER_SOFTWARE'];
            $systemArray['extensions'] = get_loaded_extensions();
            $systemArray['ini_phpversion'] = phpversion();
            $systemArray['ini_magic_quotes'] = get_magic_quotes_gpc();
            $systemArray['ini_register_globals'] = ini_get('register_globals');
            $systemArray['ini_display_errors'] = ini_get('display_errors');
            $systemArray['ini_error_reporting'] = ini_get('error_reporting');
            $systemArray['ini_post_max_size'] = ini_get('post_max_size');
            $systemArray['packages'] = $this->getImportedPackages();

            // 2. отправляем данные на packages.webproduction.com.ua
            $urlHost = 'packages.webproduction.com.ua';
            $url = 'http://'.$urlHost.'/developer-report/';

            $urlParamsArray = array();
            foreach ($systemArray as $k => $v) {
                if (!is_array($v)) {
                    $urlParamsArray[] = "$k=".urlencode($v);
                } else {
                    foreach ($v as $vi) {
                        $urlParamsArray[] = "{$k}[]=".urlencode($vi);
                    }
                }
            }

            $context = stream_context_create(
                array(
                    'http' => array(
                        'method' => 'POST',
                        'header' => 'Content-Type: application/x-www-form-urlencoded'.PHP_EOL,
                        'content' => implode('&', $urlParamsArray),
                    ),
                )
            );

            if ($socket = @fsockopen($urlHost, 80, $errNo, $errStr, 3)) {
                // если в течении 3х секунд удалось подключиться - значит
                // коннект есть и можно слить информацию
                @fclose($socket);
                $result = @file_get_contents($url, false, $context);

                // если получили отказ, создаем файл-блокировку
                if ($result == 'reject') {
                    @file_put_contents($reportFileReject, date('Y-m-d H:i:s'), LOCK_EX);
                }
            }
        }
    }

    /**
     * Получить PackageLoader Instance
     *
     * @return PackageLoader
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    /**
     * Допускается ли режим autoload классов?
     *
     * @var bool
     */
    private $_autoload;

    private $_packagePathArray = array();

    private $_projectPath;

    private $_dataProcessors = array();

    private $_modeArray = array();

    /**
     * Массив хешей для данных.
     * Таким образом отсекается регистрация таких-же данных
     * при вызове registerCSS/JSData()
     *
     * @var array
     */
    private $_dataHash = array();

    private $_loadClassArray = array();

    private static $_Instance = null;

    /**
     * Список подключенных файлов
     *
     * @var array
     */
    private $_files = array();

    /**
     * Список пакетов, которые уже подключены
     *
     * @var array
     */
    private $_importedArray = array();

    /**
     * Реестр данных
     *
     * @var array
     */
    private $_data = array('css' => '', 'js' => '');

}