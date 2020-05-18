<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2014 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Источник контент-данных для Engine.
 * Хранит в себе набор всех конентов, с которыми работает
 * Engine_ContentDriver
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Engine
 */
class Engine_ContentDataSource {

    /**
     * Получить все данные
     *
     * @return array
     */
    public function getData() {
        $this->_loadContents();

        return $this->_data;
    }

    /**
     * Получить все поля контента по его ID.
     * ID - это строковый contentID.
     *
     * @param string $id
     *
     * @return Engine_ContentDataArray
     */
    public function getDataByID($id, $init = false) {
        $this->_loadContents($init);

        if (!empty($this->_data[$id])) {
            return $this->_data[$id];
        }
        return false;
    }

    /**
     * Возвращает зачение поля контента
     *
     * @param string $id
     * @param string $key
     *
     * @return mixed
     */
    public function getDataValueByID($id, $key) {
        $data = $this->getDataByID($id);
        return @$data[$key];
    }

    /**
     * Зарегистрировать контент в системе.
     * Метод вернет заполненные поля контента.
     *
     * @param int $id
     * @param array $fieldsArray Набор полей
     *
     * @return Engine_ContentDataArray
     */
    public function registerContent($id, $fieldsArray, $registerMethod = 'override') {
        $level = @trim($fieldsArray['level'].'');

        $argumentsArray = @$fieldsArray['arguments'];
        if (!$argumentsArray) {
            $argumentsArray = array();
        }

        $filePHP = trim(@$fieldsArray['filephp'].'');
        $fileHTML = trim(@$fieldsArray['filehtml'].'');

        $data = array(
            'id' => $id,
            'title' => trim(@$fieldsArray['title'].''), // заголовок страницы
            'url' => @$fieldsArray['url'], // URLы контента
            'filehtml' => $fileHTML, // html-отображение
            'filephp' => $filePHP, // php-файл
            'moveto' => trim(@$fieldsArray['moveto'].''), // в какой контент отправлять
            'moveas' => trim(@$fieldsArray['moveas'].''), // в какую переменную контента отправлять
            'level' => $level, // уровень доступа (минимальный)
            'role' => @$fieldsArray['role'], // ролевая привелегия
            'arguments' => $argumentsArray, // массив обязательных аргументов
            'cache' => @$fieldsArray['cache'], // настройки кеширования
        );

        if ($registerMethod == 'override' || ($registerMethod == 'extend' && !@$this->_data[$id])) {
            $this->_data[$id] = $data;
        } elseif ($registerMethod == 'extend') {
            // расширение свойств объекта
            $currentData = @$this->_data[$id];

            if ($data['filephp']) {
                $currentData['filephp'] = $data['filephp'];
            }
            if ($data['filehtml']) {
                $currentData['filehtml'] = $data['filehtml'];
            }
            if ($data['moveas']) {
                $currentData['moveas'] = $data['moveas'];
            }
            if ($data['moveto']) {
                $currentData['moveto'] = $data['moveto'];
            }
            if ($data['url']) {
                $currentData['url'] = $data['url'];
            }
            if ($data['title']) {
                $currentData['title'] = $data['title'];
            }
            if ($data['level']) {
                $currentData['level'] = $data['level'];
            }
            if ($data['arguments']) {
                $currentData['arguments'] = $data['arguments'];
            }
            if (!empty($data['argument'])) {
                foreach ($data['argument'] as $x) {
                    $currentData['argument'][] = $x;
                }
            }
            if ($data['cache']) {
                $currentData['cache'] = $data['cache'];
            }

            $this->_data[$id] = $currentData;
        } else {
            throw new Engine_Exception('Unknown register content method "'.$registerMethod.'"');
        }
        return $this->_data[$id];
    }

    /**
     * Подгрузить все контенты.
     * Метод срабатывает один раз, подгружая контенты через события
     * beforeContentLoad и afterContentLoad
     */
    private function _loadContents($init = false) {
        if (!$this->_loaded || $init) {

            // регистрируем автоматический контент движка
            $path = __DIR__.'/contents/';
            $this->registerContent(
                'engine-include',
                array(
                    'filehtml' => $path.'/engine_include.html',
                    'filephp' => $path.'/engine_include.php',
                ),
                'override'
            );

            // бросам событие для подключения контентов
            $event = Events::Get()->generateEvent('beforeContentLoad');
            $event->notify();

            $event = Events::Get()->generateEvent('afterContentLoad');
            $event->notify();

            $this->_loaded = true;

            // записываем данные в кеш
            try {
                Engine::GetCache()->setData(
                    Engine::Get()->getProjectHost().'contents-data',
                    serialize($this->_data),
                    false,
                    3600
                );
            } catch (Exception $e) {

            }
        }
    }

    /**
     * Получить объект ContentDataSource'ф
     *
     * @return Engine_ContentDataSource
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    private function __construct() {

    }

    private function __clone() {

    }

    private $_data = array();

    private $_loaded = false;

    /**
     * Объект-хранилище (Instance)
     *
     * @var Engine_ContentDataSource
     */
    private static $_Instance = false;

}