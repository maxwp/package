<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Storage handler: data in directory.
 *
 * Обработчик кеша "хранение кеша в директории"
 *
 * @author    Maxim Miroshnichenko
 * @copyright WebProduction
 * @package   Storage
 */
class Storage_HandlerCacheFiles implements Storage_IHandler {

    /**
     * Create handler.
     * By default data stores in /cache/
     *
     * Создать хандлер.
     * Можно указать путь к директории с кешом по умолчанию.
     *
     * @param string $directoryPath
     */
    public function __construct($directoryPath = false) {
        if (!$directoryPath) {
            // если директория не задана - то по умолчанию юзаем
            // внутренюю
            $directoryPath = __DIR__.'/cache/';
        }

        $this->_directoryPath = $directoryPath.'/';
        $this->_directoryPath = str_replace('//', '/', $this->_directoryPath);
    }

    /**
     * Записать данные в кеш.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value, $ttl = false, $parentKey = false) {
        if ($parentKey) {
            throw new Storage_Exception('Parent keys is not supported for cache-files yet.');
        }
        if ($ttl && $ttl < 0) {
            throw new Storage_Exception("Incorrect TTL '{$ttl}'");
        }
        $key = md5($key);
        file_put_contents($this->_directoryPath.$key, $value, LOCK_EX);

        if ($ttl) {
            $edate = date('Y-m-d H:i:s', time() + $ttl);
            file_put_contents($this->_directoryPath.$key.'.ttl', $edate, LOCK_EX);
        }
    }

    /**
     * Получить данные по ключу
     *
     * @param string $key
     */
    public function get($key) {
        if ($this->has($key)) {
            return file_get_contents($this->_directoryPath.md5($key));
        }
        throw new Storage_Exception("Cache by key '{$key}' not found");
    }

    /**
     * Узнать, есть ли такой ключ
     *
     * @param string $key
     */
    public function has($key) {
        $r = file_exists($this->_directoryPath.md5($key));
        if ($r) {
            // проверяем ttl
            $edate = @file_get_contents($this->_directoryPath.md5($key).'.ttl');
            if ($edate && $edate <= date('Y-m-d H:i:s')) {
                @unlink($this->_directoryPath.md5($key).'.ttl');
                @unlink($this->_directoryPath.md5($key));
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Удалить данные
     *
     * @param string $key
     */
    public function remove($key) {
        if ($this->has($key)) {
            unlink($this->_directoryPath.md5($key));
            @unlink($this->_directoryPath.md5($key).'.ttl');
        }
    }

    /**
     * Очистить кеш
     */
    public function clean() {
        $d = opendir($this->_directoryPath);
        while ($x = readdir($d)) {
            if (is_file($this->_directoryPath.$x)) {
                unlink($this->_directoryPath.$x);
            }
        }
        closedir($d);
    }

    private $_directoryPath = null;

}