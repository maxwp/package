<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
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
     * Put data to storage handler.
     **
     * @param string $key
     * @param mixed $value
     * @return bool @todo
     */
    public function set($key, $value);

    /**
     * Put data to storage handler.
     * TTL - time to life (if handler supported).
     **
     * @param string $key
     * @param mixed $value
     * @return bool @todo
     */
    public function setEx($key, $value, $ttl);

    /**
     * @param $key
     * @return bool @todo
     */
    public function has($key);

    /**
     * Get data from storage.
     *
     * Получить данные по ключу
     *
     * @param string $key
     * @throws Exception @todo
     */
    public function get($key);

    /**
     * Remove data by key
     *
     * Удалить данные
     *
     * @param string $key
     * @return bool @todo
     */
    public function remove($key);

    /**
     * Clean all data in handler
     */
    public function clean();

}