<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Interface of Storage Handler.
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package Storage
 */
interface Storage_IHandler {

    /**
     * Put data to storage.
     * TTL - time to life (if handler supported).
     *
     * Записать данные.
     * TTL - time-to-life, время жизни данных, если
     * хандлер поддерживает TTL
     *
     * @param string $key
     * @param string $parentKey
     * @param mixed $value
     * @param int $ttl
     */
    public function set($key, $value, $ttl = false, $parentKey = false);

    /**
     * Get data from storage.
     *
     * Получить данные по ключу
     *
     * @param string $key
     */
    public function get($key);

    /**
     * Is data exists?
     *
     * Узнать, есть ли такой ключ
     *
     * @param string $key
     */
    public function has($key);

    /**
     * Remove data by key
     *
     * Удалить данные
     *
     * @param string $key
     */
    public function remove($key);

    /**
     * Clean all data in handler
     *
     * Очистить
     */
    public function clean();

}