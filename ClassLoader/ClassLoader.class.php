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

        spl_autoload_register([$this, 'loadClass']);

        for ($j = 1; $j <= 100; $j++) {
            $arg = @$argv[$j];
            if (!$arg) {
                continue;
            }

            $arg = preg_replace('/^--/', '', $arg);

            // автоматическое определение я режиме debug или нет?
            // (в debug==false будет выполняться компиляция классов)
            // дебажим все
            if ($arg == 'debug') {
                $this->_debugAll = true;
            }

            // дебажим конкретные классы
            if (preg_match("/^debug:(.+?)$/ius", $arg, $r)) {
                $this->_debugArray[$r[1]] = true;
            }

            // установка custom memory limit
            if (preg_match("/^memory:(.+?)$/ius", $arg, $r)) {
                ini_set('memory_limit', $r[1]);
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
            if ($this->_debugAll) {
                // @todo lock read
                include_once $this->_classArray[$className];
            } elseif (isset($this->_debugArray[$className])) {
                // @todo lock read
                include_once $this->_classArray[$className];
            } else {
                // делаем компиляцию
                $file = $this->_classArray[$className];
                $fileCompiled = $file.'.compiled';

                if (@filemtime($file) > @filemtime($fileCompiled)) {
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
     * Зарегистрировать PHP-класс, чтобы ClassLoader
     * мог его загружать и знал где он физически находится
     *
     * @param string $file
     */
    public function registerClass($file) {
        $file = str_replace('//', '/', $file);

        // @todo low optimizations
        $hash = basename($file);
        $hash = str_replace('.class.php', '', $hash);
        $hash = str_replace('.interface.php', '', $hash);
        $hash = str_replace('.trait.php', '', $hash);
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
            || str_contains($x, '.interface.php')
            || str_contains($x, '.trait.php')
            ) {
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
     * @var array<string>
     */
    private $_classArray = [];
    private $_debugAll = false;
    private $_debugArray = [];

}