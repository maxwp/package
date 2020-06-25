<?php

/**
 * Загрузчик классов по требованию
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 * @package ClassLoader
 */
class ClassLoader {

    private function __construct() {
        spl_autoload_register(array($this, 'loadClass'));
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

            include_once($file);
        }
    }

    /**
     * Получить файлы
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
     * $cache - сколько времени держать кеш (по умолчанию 2 секунды)
     *
     * @param string $dir
     * @param int $cache
     */
    public function registerDirectory($dir, $cache = 2) {
        if ($cache > 0) {
            $cacheFile = dirname(__FILE__).'/cache/'.md5($dir);
            $mtime = @filemtime($cacheFile);
            if ($mtime && $mtime >= time() - $cache) {
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
            $this->registerClass($x);
        }

        // записываем cache
        if ($cache > 0) {
            $cacheFile = dirname(__FILE__).'/cache/'.md5($dir);
            file_put_contents($cacheFile, implode("\n", $a));
        }
    }

    private function _scandir($dir) {
        $a = array();
        $d = opendir($dir);
        while ($x = readdir($d)) {
            if ($x == '.') {
                continue;
            }
            if ($x == '..') {
                continue;
            }

            if (strpos($x, '.php')) {
                $a[] = $dir.'/'.$x;
            }

            if (is_dir($dir.'/'.$x)) {
                $tmp = $this->_scandir($dir.'/'.$x);
                $a = array_merge($a, $tmp);
            }
        }
        closedir($d);

        return $a;
    }

    /**
     * @return ClassLoader
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    private static $_Instance = null;

    /**
     * Список зарегистрированный классов
     *
     * @var array
     */
    private $_classArray = array();

}