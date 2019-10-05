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
 * Модификатор, который запрещает кеширование,
 * если пользователь авторизирован
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Engine
 */
class Engine_CacheModifierNoAuth extends Engine_ACacheModifier {

    public function modifyKey($key) {
        try {
            $user = Engine::GetAuth()->getUser();
        } catch (Exception $e) {

        }

        if (!empty($user)) {
            throw new Engine_Exception('No auth cache', 0);
        }
        return $key;
    }

}