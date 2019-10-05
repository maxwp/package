<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Абстрактный класс LinkMaker'a,
 * который упрощает некоторые методы Engine_LinkMaker'a
 *
 * @copyright WebProduction
 * @package Engine
 * @author Maxim Miroshnochenko <max@webproduction.com.ua>
 * @subpackage LinkMaker
 */
abstract class Engine_ALinkMaker implements Engine_ILinkMaker {

    public function makeURLByContentID($contentID) {
        return $this->makeURLByContentIDParams($contentID, array());
    }

    public function makeURLByContentIDParam($contentID, $value, $key = 'id') {
        return $this->makeURLByContentIDParams($contentID, array($key => $value));
    }

    public function makeURLCurrentByReplaceParams($paramsArray) {
        // @todo
        $url = Engine::GetURLParser()->getTotalURL().'?'.Engine::GetURLParser()->getGETString();
        return $this->makeURLByReplaceParams($url, $paramsArray);
    }

}