<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package Engine
 */
class Engine_Auth {

    /**
     * Получить текущего авторизированного юзера
     *
     * @throws Exception
     * @return Object
     */
    public function getUser() {
        if (class_exists('MainService')) {
            // старый стиль
            return MainService::GetFactory()->getAuthService()->getUser();
        } elseif (self::$_AuthService) {
            // новый стиль
            return self::$_AuthService->getUser();
        }
    }

    public static function SetAuthService($service) {
        self::$_AuthService = $service;
    }

    private static $_AuthService;

}