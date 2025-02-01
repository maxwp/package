<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Менеджер соединений.
 * MySQL, SMTP, POP, IMAP, memcache, APC, memcached, SOAPs, etc.
 */
class Connection extends Pattern_ARegistrySingleton {

    // @todo как возвращать типизированные коннекторы?
    // @todo как инициализировать типизированные коннкторы?

    /**
     * @param $key
     * @return Connection_IConnection
     * @throws Connection_Exception
     */
    public static function Get(string $key) {
        return self::_Get($key);
    }

    public static function Register(string $key, $object) {
        self::_Register($key, $object, 'Connection_IConnection');
    }

}