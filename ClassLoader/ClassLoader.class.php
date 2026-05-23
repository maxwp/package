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
                include_once $this->_classArray[$className];
            } elseif (isset($this->_debugArray[$className])) {
                include_once $this->_classArray[$className];
            } else {
                // делаем компиляцию
                $file = $this->_classArray[$className];
                $fileCompiled = $file.'.compiled';

                //clearstatcache(true, $fileCompiled);

                if (@filemtime($file) > @filemtime($fileCompiled)) {
                    $data = file_get_contents($file);
                    $data = str_replace('# debug:start', '/* debug:start', $data);
                    $data = str_replace('# debug:end', 'debug:end */', $data);

                    $tmpFile = $fileCompiled . '.' . bin2hex(random_bytes(6)) . '.tmp';

                    file_put_contents($tmpFile, $data, LOCK_EX);

                    // atomic replace
                    rename($tmpFile, $fileCompiled);

                    // костыляка для opcache
                    if (function_exists('opcache_invalidate')) {
                        opcache_invalidate($fileCompiled, true);
                    }
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
    public function registerDirectory($dir, $allowCache = 0) {
        if ($allowCache > 0) {
            $cacheFile = $dir.'/ClassLoader.cache';
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
            if (!str_contains($x, '.compiled')) {
                if (str_contains($x, '.class.php')) {
                    $this->registerClass($x);
                } elseif (str_contains($x, '.interface.php')) {
                    $this->registerClass($x);
                } elseif (str_contains($x, '.trait.php')) {
                    $this->registerClass($x);
                }
            }
        }

        // записываем cache
        if ($allowCache > 0) {
            // тут переменная cacheFile уже есть точно
            $tmpFile = $dir.'/'.bin2hex(random_bytes(6)) . '.tmp';
            file_put_contents($cacheFile, implode("\n", $a), LOCK_EX);
            rename($tmpFile, $cacheFile);
        }
    }

    private function _scandir($dir) {
        $a = [];
        $d = opendir($dir);
        while ($name = readdir($d)) {
            if ($name == '.') {
                continue;
            } elseif ($name == '..') {
                continue;
            }

            if (str_contains($name, '.php')) { // так быстрее чем is_file
                $a[] = $dir.'/'.$name;
            } elseif (is_dir($dir.'/'.$name)) {
                $a = array_merge($a, $this->_scandir($dir.'/'.$name));
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
    private $_debugAll = false; // bool
    private $_debugArray = [];

}