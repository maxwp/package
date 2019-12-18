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
     * @param string $dir
     */
    public function registerDirectory($dir) {
        // сканируем директорию
        // и регистрируем все файлы
        $data = scandir($dir);
        foreach ($data as $x) {
            if (strpos($x, '.class.php')
            || strpos($x, '.interface.php')) {
                $this->registerClass($dir.'/'.$x);
            }
        }
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