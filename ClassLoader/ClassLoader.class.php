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
        // @todo если отказаться от суффиксов .class/.interface/.trait то станет 58 ns и это x2 по скорости
        $this->_classArray[str_replace(['.class', '.interface', '.trait'], '', basename($file, '.php'))] = $file;
    }

    /**
     * За один вызов зарегистрировать кучу классов.
     * Это экономит ::Get()->call на каждом пуке.
     *
     * @param array<string> $fileArray
     * @return void
     */
    public function registerClassArray($fileArray) {
        // я использую массив для того чтобы можно было дернуть этот метод из registerDirectory & cache
        foreach ($fileArray as $file) {
            // @todo если отказаться от суффиксов .class/.interface/.trait то станет 58 ns и это x2 по скорости
            $this->_classArray[str_replace(['.class', '.interface', '.trait'], '', basename($file, '.php'))] = $file;
        }
    }

    /**
     * Зарегистрировать директорию с php-классами.
     * Не рекомендуется к использованию, так как порядок
     * подключения может быть абсолютно рандомный.
     *
     * Так как не соблюдается порядок подключения, рекомендуется
     * использовать registerClass()
     *
     * $cacheTTL - сколько времени держать кеш (по умолчанию без кеша)
     *
     * @param string $dir
     * @param int $cacheTTL
     */
    public function registerDirectory($dir, $cacheTTL = 0) {
        if ($cacheTTL > 0) {
            // cachefile один и он в корне этой директории
            $cacheFile = $dir.'/ClassLoader.cache';
            if (@filemtime($cacheFile) >= time() - $cacheTTL) {
                $a = file($cacheFile);
                // даже если cache-файл пустой, то там не должно быть битых путей, поэтому никаких trim & if не надо
                foreach ($a as $x) {
                    $this->registerClass($x);
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

        // @todo надо в cache оставлять только подходящие файлы, а не все.
        // @todo и фильтровать прямо на этапе _scandir

        // записываем cache
        if ($cacheTTL > 0) {
            // тут переменная cacheFile уже есть точно
            // @todo универсальный atomic write
            $tmpFile = $dir.'/'.bin2hex(random_bytes(6)) . '.tmp';
            file_put_contents($cacheFile, implode("\n", $a), LOCK_EX);
            rename($tmpFile, $cacheFile);
        }
    }

    // @todo maybe File pkg Dir?
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