<?php
/**
 * WebProduction Packages
 * 
 * @copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * LinkMaker - генератор ссылок
 *
 * @copyright WebProduction
 * @author Ramm
 * @author Max
 * @author DFox
 * @package Engine
 * @subpackage LinkMaker
 */
class Engine_LinkMaker extends Engine_ALinkMaker implements Engine_ILinkMaker {

    /**
     * Построить ссылку на контент с заданными параметрами
     *
     * @param mixed $contentID
     * @param array $paramsArray
     *
     * @return string
     */
    public function makeURLByContentIDParams($contentID, $paramsArray) {
        // строим хеш
        $hash = 'url-'.md5($contentID.serialize($paramsArray));

        // проверяем, есть ли URL в кеше
        try {
            return $this->getCache()->getData($hash);
        } catch (Exception $e) {

        }

        $data = Engine::GetContentDataSource()->getDataByID($contentID);
        if (!$data) {
            throw new Engine_Exception("Content #{$contentID} not found in ContentDataSource", 0);
        }

        $urlArray = $data['url'];

        if (is_array($urlArray)) {
            // подбираем URL, который точно подходит под передаваемые параметры
            // иначе просто берем первый
            $url = '';
            foreach ($urlArray as $xurl) {
                $ok = true;
                if (preg_match_all("/\{(.*?)\}/is", $xurl, $r)) {
                    if (count($paramsArray) != count($r[1])) {
                        // количество параметров не совпадает
                        continue;
                    }

                    foreach ($paramsArray as $key=>$rx) {
                        if (!in_array($key, $r[1])) {
                            $ok = false;
                            break;
                        }
                    }

                    if ($ok) {
                        $url = $xurl;
                        break;
                    }
                }
            }

            if (!$url) {
                $url = $urlArray[0];
            }

        } else {
            $url = $urlArray;
        }

        // текущий язык движка
        $lang = Engine::Get()->getLanguage();
        if ($lang || !isset($paramsArray['engine-language'])) {
            $paramsArray['engine-language'] = $lang;
        }

        // по найденному URLу строим что надо
        if (count($paramsArray)) {
            $a = array();
            foreach ($paramsArray as $key => $value) {
                if (strpos($url, '{'.$key.'}') !== false) {
                    $url = str_replace('{'.$key.'}', $value, $url);
                    continue;
                }
                if ($key == 'engine-language') {
                    continue;
                }
                if (is_array($value)) {
                    foreach ($value as $val) $a[] = $key.'[]='.$val;
                } else $a[] = $key.'='.$value;
            }
            if ($a) {
                $url .= '?'.implode(self::_GetAmp(), $a);
            }
        }

        // записываем URL в кеш
        try {
            $this->getCache()->setData($hash, $url);
        } catch (Exception $e) {

        }

        return $url;
    }

    /**
     * Построить ссылку на основе URL, дописав/переписав у него параметры
     *
     * @param string $url
     * @param array $paramsArray
     *
     * @return string
     */
    public function makeURLByReplaceParams($url, $paramsArray) {
        // строим хеш
        $hash = 'replace-'.md5($url.serialize($paramsArray));

        // проверяем данные в кеше
        try {
            return $this->getCache()->getData($hash);
        } catch (Exception $e) {

        }

        $urlArray = explode('?', $url, 2);
        $gets = @$urlArray[1];
        parse_str($gets, $gets);

        $params = $paramsArray;

        if ($params) {
            foreach ($params as $k => $v) {
                $gets[$k] = $v;
            }
        }

        $url = array();
        if ($gets) {
            foreach ($gets as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $av) {
                        if (get_magic_quotes_gpc()) {
                            $av = stripslashes($av);
                        }
                        $av = urlencode($av);
                        $url[] = "{$k}[]={$av}";
                    }
                } else {
                    if (get_magic_quotes_gpc()) {
                        $v = stripslashes($v);
                    }
                    $v = urlencode($v);
                    $url[] = "$k=$v";
                }
            }
        }

        if ($url) {
            $result = $urlArray[0].'?'.implode(self::_GetAmp(), $url);
        } else {
            $result = $urlArray[0];
        }

        // записываем данные в кеш
        try {
            $this->getCache()->setData($hash, $result);
        } catch (Exception $e) {

        }

        return $result;
    }

    private static function _GetAmp() {
        // @todo: возможно спрятать внутрь
        if (self::$_XHTML) {
            return '&amp;';
        } else {
            return '&';
        }
    }

    /**
     * Построить URL на основе готовой части URL'a.
     * Параметры однозначно дописываются в конец.
     * Все части {...} удаляются.
     *
     * @param string $contentURL
     * @param mixed $value
     * @param mixed $key
     *
     * @author Max
     *
     * @return string
     */
    public static function GetURLByContentURL($contentURL, $value = '', $key = 'id') {
        $contentURL = preg_replace("/\{(.*?)\}/is", '', $contentURL);
        $contentURL = str_replace('//', '/', $contentURL);
        if ($value) {
            $contentURL .= "?$key=$value";
        }
        return $contentURL;
    }

    /**
     * Получить LinkMaker
     *
     * @return Engine_LinkMaker
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new Engine_LinkMaker();
        }
        return self::$_Instance;
    }

    /**
     * Получить систему кеширования LinkMaker'a.
     * Ее можно переопределить на memcache, db, etc.
     *
     * @return Storage
     */
    public function getCache() {
        return $this->_cache;
    }

    /**
     * Установить режим формирования ссылок в формате XHTML
     *
     * @param bool $mode
     */
    public static function SetModeXHTML($mode = true) {
        self::$_XHTML = $mode;
    }

    private function __construct() {
        // инициируем систему кеширования для LinkMaker'a,
        // по умолчанию обычный массв
        $this->_cache = Storage::Initialize(
            'engine-linkmaker',
            new Storage_HandlerArray()
        );
    }

    private static $_Instance = null;

    private static $_XHTML = true;

    /**
     * Система кеширования Engine
     *
     * @var Storage
     */
    private $_cache;

}