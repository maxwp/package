<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Storage handler: any files in directory.
 *
 * Хранилище любых файлов в виде списка в заданной директории.
 * Наиболее вероятное применение: для картинок к товарам,
 * для файлов-вложений к каким-либо объектам.
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package Storage
 */
class Storage_HandlerFiles implements Storage_IHandler {

    /**
     * Create handler. Data stores in /media/<storage>/ by default.
     *
     * Инициировать handler хранилища на основе стандартной
     * media-директории и ее поддиректории.
     * Например, если передать images, то хранилище будет
     * /...путь к пакету Storage.../media/images/
     *
     * @param string $subdirectory
     * @return Storage_HandlerMediaFiles
     */
    public static function CreateInternal($subdirectory) {
        if (!$subdirectory || substr_count($subdirectory, '..')) {
            throw new Storage_Exception("Invalid subdirectory name '{$subdirectory}'");
        }

        $path = __DIR__.'/media/'.$subdirectory.'/';
        @mkdir($path);
        return new self($path);
    }

    /**
     * Создать файловое хранилище в указанной директории
     *
     * @param string $dirPath
     */
    public function __construct($dirPath = false) {
        if ($dirPath) {
            if (is_dir($dirPath)) {
                $this->_dirPath = $dirPath.'/';
            } else {
                throw new Storage_Exception("Path '{$dirPath}' not found or is not directory");
            }
        } else {
            $this->_dirPath = __DIR__.'/media/';
        }
        $this->_dirPath = str_replace('//', '/', $this->_dirPath);
    }

    /**
     * Обработать ключ и вернуть скорректированный
     *
     * @param string $key
     * @return string
     */
    private function _processKey($key) {
        // @todo: кеширование повторов
        if (preg_match_all("/[\w\d-_\.]+/is", $key, $r)) {
            $key = implode('', $r[0]);
            if ($key) {
                return $key;
            }
        }
        throw new Storage_Exception("Invalid key '{$key}'");
    }

    /**
     * Записать файл в хранилище,
     * TTL не поддерживается.
     *
     * @param string $key Ключ
     * @param string $parentKey Родительский ключ (к чему привязывать файл)
     * @param mixed $value Полный путь к файлу
     * @param int $ttl
     */
    public function set($key, $value, $ttl = false, $parentKey = false) {
        $key_p = $this->_processKey($key);

        if ($ttl) {
            // @todo
            throw new Storage_Exception("TTL not supported for files");
        }
        if (!is_file($value)) {
            throw new Storage_Exception("File '{$value}' not exists or is not file.");
        }

        if ($parentKey !== false && !$this->has($parentKey)) {
            throw new Storage_Exception("Parent-key '{$parentKey}' not found.");
        }

        // копируем файл в директорию хранилища
        copy($value, $this->_dirPath.$key_p);

        if ($parentKey) {
            $parentKey_p = $this->_processKey($parentKey);

            // обновляем файл parentKey
            $serFile = $this->_dirPath.$parentKey_p.'.ser';
            $data = @unserialize(file_get_contents($serFile));
            if (!$data) {
                $data = array();
                $data['childsArray'] = array();
            }
            if (!in_array($key, $data['childsArray'])) {
                $data['childsArray'][] = $key;
                file_put_contents($serFile, serialize($data), LOCK_EX);
            }

        }
    }

    /**
     * Получить полный путь к файлу
     * по его ключу
     *
     * @param string $key
     */
    public function get($key) {
        if ($this->has($key)) {
            return $this->_dirPath.$this->_processKey($key);
        }
        throw new Storage_Exception("File not found in storage '{$this->_dirPath}' by key '{$key}'");
    }

    /**
     * Узнать, есть ли такой ключ
     *
     * @param string $key
     */
    public function has($key) {
        return is_file($this->_dirPath.$this->_processKey($key));
    }

    /**
     * Удалить файлы и все его подфайлы тоже.
     *
     * @param string $key
     */
    public function remove($key) {
        $path = $this->get($key);
        unlink($path);

        // убиваем ser-файл и все его подфайлы
        $serFile = $this->_dirPath.$this->_processKey($key).'.ser';
        if (file_exists($serFile)) {
            $data = @unserialize(file_get_contents($serFile));
            if (!empty($data['childsArray'])) {
                foreach ($data['childsArray'] as $x) {
                    try {
                        $this->remove($x);
                    } catch (Exception $e) {

                    }
                }
            }
            unlink($serFile);
        }
    }

    /**
     * Очистить все хранилище.
     * Формально - стереть все файлы из директории хранилища.
     */
    public function clean() {
        $d = opendir($this->_dirPath);
        while ($x = readdir($d)) {
            if (is_file($this->_dirPath.$x)) {
                // сносим всю директорию
                unlink($this->_dirPath.$x);
            }
        }
        closedir($d);
    }

    private $_dirPath = false;

}