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
 * Cache key modifier
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Engine
 */
class Engine_CacheModifierURL extends Engine_ACacheModifier {

    public function modifyKey($key) {
        return $key.'-'.md5(Engine::GetURLParser()->getHost().Engine::GetURLParser()->makeURLID());
    }

}