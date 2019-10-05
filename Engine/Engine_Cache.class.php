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
 * Встроенная система кеширования в Engine,
 * основанная на Storage
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Engine
 */
class Engine_Cache {

    /**
     * Получить закешированные данные
     *
     * @param string $key
     * @param array $modifierArray
     *
     * @return string
     */
    public function getData($key, $modifierArray = false) {
        if (!$this->getCacheMode()) {
            throw new Engine_Exception("Cache disabled.");
        }

        $storage = $this->getStorage();

        if ($modifierArray) {
            foreach ($modifierArray as $m) {
                $h = $this->getModifier($m);
                $key = $h->modifyKey($key);
                $storage = $h->modifyStorage($storage);
            }
        }

        return $storage->getData($key);
    }

    /**
     * Закешировать данные
     *
     * @param string $key
     * @param string $value
     * @param array $modifierArray
     * @param int $ttl
     */
    public function setData($key, $value, $modifierArray = false, $ttl = false) {
        if (!$this->getCacheMode()) {
            throw new Engine_Exception("Cache disabled.");
        }

        $storage = $this->getStorage();

        if ($modifierArray) {
            foreach ($modifierArray as $m) {
                $h = $this->getModifier($m);
                $key = $h->modifyKey($key);
                $storage = $h->modifyStorage($storage);
                $value = $h->modifyValue($value);
            }
        }

        $storage->setData($key, $value, $ttl);
    }

    /**
     * Удалить данные из кеша по ключу $key
     *
     * @param string $key
     */
    public function removeData($key) {
        if (!$this->getCacheMode()) {
            throw new Engine_Exception("Cache disabled.");
        }

        $storage = $this->getStorage();
        $storage->removeData($key);
    }

    /**
     * Получить объект кеша
     *
     * @return Engine_Cache
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();

            // инициируем систему кеширования
            // (по умолчанию на файлах)
            $storage = Storage::Initialize(
                'engine-cache',
                new Storage_HandlerCacheFiles(__DIR__.'/cache/')
            );

            self::$_Instance->setStorage($storage);

            // регистрируем доступные модификаторы по умолчанию
            self::$_Instance->registerModifier('url', 'Engine_CacheModifierURL');
            self::$_Instance->registerModifier('user', 'Engine_CacheModifierUser');
            self::$_Instance->registerModifier('host', 'Engine_CacheModifierHost');
            self::$_Instance->registerModifier('language', 'Engine_CacheModifierLanguage');
            self::$_Instance->registerModifier('no-auth', 'Engine_CacheModifierNoAuth');
            self::$_Instance->registerModifier('auth-login', 'Engine_CacheModifierAuthLogin');
        }
        return self::$_Instance;
    }

    public function setCacheMode($enable = true) {
        $this->_cacheMode = $enable;
    }

    /**
     * Узнать состояние кеша.
     * true - включен.
     *
     * @return bool
     */
    public function getCacheMode() {
        return $this->_cacheMode;
    }

    /**
     * Включить кеширование
     */
    public function enableCache() {
        $this->setCacheMode(true);
    }

    /**
     * Выключить кеширование
     */
    public function disableCache() {
        $this->setCacheMode(false);
    }

    /**
     * Очистить кеш.
     *
     * Внимание! Это всего-лишь концепт-метод.
     * Если вы измените систему хранения кеша при помощи
     * модификатора - метод очистит не все.
     */
    public function clearCache() {
        $this->getStorage()->clearData();
    }

    /**
     * Получить хранилище
     *
     * @return Storage
     */
    public function getStorage() {
        return $this->_storage;
    }

    /**
     * Задать хранилище для работы Engine_Cache
     *
     * @param Storage $storage
     *
     * @see Storage package
     */
    public function setStorage(Storage $storage) {
        $this->_storage = $storage;
    }

    /**
     * Зарегистрировать правило модификаци кеш-ключа в системе
     *
     * @param string $key
     * @param string $modifierClass
     */
    public function registerModifier($key, $modifierClass) {
        $this->_modifierClassArray[$key] = $modifierClass;
    }

    /**
     * Получить модификатор
     *
     * @param string $key
     *
     * @return Engine_ACacheModifier
     */
    public function getModifier($key) {
        // если есть объект - то выдаем его
        if (isset($this->_modifierObjectArray[$key])) {
            return $this->_modifierObjectArray[$key];
        }

        // иначе проверяем класс,
        // создаем его и возвращаем объект
        if (!empty($this->_modifierClassArray[$key])) {
            $classname = $this->_modifierClassArray[$key];
            $this->_modifierObjectArray[$key] = new $classname();
            return $this->_modifierObjectArray[$key];
        }

        throw new Engine_Exception("Modifier with key '{$key}' not found");
    }

    private $_modifierClassArray = array();

    private $_modifierObjectArray = array();

    private $_cacheMode = true;

    /**
     * Instance singleton
     *
     * @var Engine_Cache
     */
    private static $_Instance = null;

    private $_storage = null;

}