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
 * Модификатор кеша, который меняет cache-key в зависимости от host'a.
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Engine
 */
class Engine_CacheModifierHost extends Engine_ACacheModifier {

    public function modifyKey($key) {
        return $key.'-'.Engine::GetURLParser()->getHost();
    }

}