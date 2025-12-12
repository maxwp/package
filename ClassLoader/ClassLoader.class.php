<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Загрузчик классов по требованию
 */
class ClassLoader extends Pattern_ASingleton {

    protected function __construct() {
        global $argv;

        spl_autoload_register(array($this, 'loadClass'));

        // автоматическое определение я режиме debug или нет?
        // (в debug==false будет выполняться компиляция классов)
        for ($j = 1; $j <= 100; $j++) {
            $arg = @$argv[$j];
            if (!$arg) {
                continue;
            }

            $arg = preg_replace('/^--/', '', $arg);

            // дебажим все
            if ($arg == 'debug') {
                $this->_debugAll = true;
                break;
            }

            // дебажим конкретные классы
            if (preg_match("/^debug:(.+?)$/ius", $arg, $r)) {
                $this->_debugArray[$r[1]] = true;
            }
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
        if (!empty($this->_classArray[$className])) {
            $file = $this->_classArray[$className];

            if ($this->_debugAll) {
                include_once $file;
            } elseif (isset($this->_debugArray[$className])) {
                include_once $file;
            } else {
                // делаем компиляцию
                $fileCompiled = $file.'.compiled';

                $mtimeOriginal = @filemtime($file);
                $mtimeCompiled = @filemtime($fileCompiled);
                if ($mtimeOriginal > $mtimeCompiled) {
                    $data = file_get_contents($file);
                    $data = str_replace('# debug:start', '/* debug:start', $data);
                    $data = str_replace('# debug:end', 'debug:end */', $data);
                    file_put_contents($fileCompiled, $data, LOCK_EX);
                }

                include_once $fileCompiled;
            }
        }
    }

    /**
     * Получить загруженные классы
     *
     * @return array
     */
    public function getClassArray() {
        return $this->_classArray;
    }

    /**
     * Зарегистрировать PHP-класс, чтобы ClassLoader
     * мог его загружать и знал где он физически находится
     *
     * @param string $file
     */
    public function registerClass($file) {
        // @todo internal registry array?

        $file = str_replace('//', '/', $file);

        $hash = basename($file);
        $hash = str_replace('.class.php', '', $hash);
        $hash = str_replace('.interface.php', '', $hash);
        $hash = str_replace('.php', '', $hash);
        $this->_classArray[$hash] = $file;
    }

    /**
     * Зарегистрировать директорию с php-классами.
     * Не рекомендуется к использованию, так как порядок
     * подключения может быть абсолютно рандомный.
     *
     * Так как не соблюдается порядок подключения, рекомендуется
     * использовать registerClass()
     *
     * $cache - сколько времени держать кеш (по умолчанию без кеша)
     *
     * @param string $dir
     * @param int $allowCache
     */
    public function registerDirectory($dir, $allowCache = false) {
        if ($allowCache > 0) {
            $cacheFile = dirname(__FILE__).'/cache/'.hash('fnv1a64', $dir);
            $mtime = @filemtime($cacheFile);
            if ($mtime && $mtime >= time() - $allowCache) {
                $a = file($cacheFile);
                if ($a) {
                    foreach ($a as $x) {
                        $this->registerClass(trim($x));
                    }
                }
                return;
            }
        }

        // сканируем директорию
        $a = $this->_scandir($dir);

        foreach ($a as $x) {
            if (str_contains($x, '.class.php')
            || str_contains($x, '.interface.php')) {
                if (!str_contains($x, '.compiled')) {
                    $this->registerClass($x);
                }
            }
        }

        // записываем cache
        if ($allowCache > 0) {
            $cacheFile = dirname(__FILE__).'/cache/'.hash('fnv1a64', $dir);
            file_put_contents($cacheFile, implode("\n", $a), LOCK_EX);
        }
    }

    private function _scandir($dir) {
        $a = [];
        $d = opendir($dir);
        while ($name = readdir($d)) {
            if ($name == '.') {
                continue;
            }
            if ($name == '..') {
                continue;
            }

            if (strpos($name, '.php')) {
                $a[] = $dir.'/'.$name;
            }

            if (is_dir($dir.'/'.$name)) {
                $tmp = $this->_scandir($dir.'/'.$name);
                $a = array_merge($a, $tmp);
                unset($tmp);
            }
        }
        closedir($d);

        return $a;
    }

    /**
     * Список зарегистрированный классов
     *
     * @var array
     */
    private array $_classArray = [];

    private $_debugAll = false;
    private $_debugArray = [];

}