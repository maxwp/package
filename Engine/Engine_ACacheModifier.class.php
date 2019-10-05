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
 * Template for cache modifiers
 *
 * @author     Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright  WebProduction
 * @package    Engine
 * @subpackage Cache
 */
abstract class Engine_ACacheModifier {

    /**
     * Модифицировать ключ
     * или остановить цепочку
     * через Exception
     *
     * @param string $key
     *
     * @return string
     */
    public function modifyKey($key) {
        return $key;
    }

    /**
     * Поменять способ хранения
     *
     * @param Storage $storage
     *
     * @return string
     */
    public function modifyStorage(Storage $storage) {
        return $storage;
    }

    /**
     * Поменять значение кеша во время сохранения
     *
     * @param string $value
     *
     * @return string
     */
    public function modifyValue($value) {
        return $value;
    }

}