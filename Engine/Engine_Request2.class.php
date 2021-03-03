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
 * Реализация класса работающего с запросом
 * для древовидной структуры сайта
 *
 * @author DFox (idea)
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 *
 * @copyright WebProduction
 *
 * @package Engine
 */
class Engine_Request2 extends Engine_Request implements Engine_IRequest {

    public function __construct($url, $host) {
        $this->host = $host;
        $this->_setTotalUrl($url);
    }

    /**
     * @return Engine_Request
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    private static $_Instance = null;

}