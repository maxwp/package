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
 * Модификатор, который к ключу кеша добавляет логин пользователя,
 * то есть, строит персонализированный кеш
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Engine
 */
class Engine_CacheModifierAuthLogin extends Engine_ACacheModifier {

    public function modifyKey($key) {
        try {
            $user = Engine::GetAuth()->getUser();

            return $key.'-user'.$user->getLogin();
        } catch (Exception $e) {

        }

        return $key;
    }

}