<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2015 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Подсистема Engine, позволяющая управлять meta-тегами
 * и подключениями скриптов для блока <head>
 *
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 *
 * @copyright WebProduction
 *
 * @package Engine
 */
class Engine_HTMLHead {


    /**
     * Вернуть html-код контента, в котором будут все необходимые
     * Engine-include'ы.
     *
     * Если $jsAndCss == true. Метод вернет готовый html-код, в котором будет подключение
     * всех необходимых CSS/JS. Код нужно вставить в тег <head>
     *
     * @param bool $jsAndCss
     *
     * @return string
     */
    public function render($jsAndCss = true, $isshop = false) {
        if ($jsAndCss) {
            // загружаем дату ревизии
            $revFile = PackageLoader::Get()->getProjectPath().'rev.info';
            $rev = @file_get_contents($revFile);

            // js files
            $list = PackageLoader::Get()->getJSFiles();
            if ($rev) {
                foreach ($list as $key => $value) {
                    if (
                        $isshop &&
                        (preg_match('/^\/_js\/module\//ius', $value) || preg_match('/^\/_js\/tinymce\//ius', $value))
                    ) {
                        unset($list[$key]);
                        continue;
                    }
                    if (!substr_count($value, '?')) {
                        $list[$key] = $value.'?'.$rev;
                    } else {
                        $list[$key] = $value.'&'.$rev;
                    }
                }
            }
            $a['jsfiles'] = $list;

            // css files
            $list = PackageLoader::Get()->getCSSFiles();
            if ($rev) {
                foreach ($list as $key => $value) {
                    if (!substr_count($value, '?')) {
                        $list[$key] = $value.'?'.$rev;
                    } else {
                        $list[$key] = $value.'&'.$rev;
                    }
                }
            }

            // костыль для orange-shop
            // все дефолтные, глобальные стили должны быть вначале массива
            foreach ($list as $key => $value) {
                if (substr_count($value, '/default/') && substr_count($value, '_globalstyles.')) {
                    unset($list[$key]);

                    $list = array_merge(array($key => $value), $list);
                }
            }

            $a['cssfiles'] = $list;

            // @deprecated: не кошерный стиль :)
            $a['csscontent'] = PackageLoader::Get()->getCSSData();
            $a['jscontent'] = PackageLoader::Get()->getJSData();
        }

        $a['metaArray'] = $this->_metaArray;
        $a['linkArray'] = $this->_linkArray;
        $a['openGraphArray'] = $this->_openGraphArray;

        /*if ($title) {
        $a['title'] = $this->getTitle();
        }*/

        $content = Engine::GetContentDriver()->getContent('engine-include');
        $content->addValuesArray($a);
        return $content->render();
    }

    /**
     * Задать заголовок (title) для страницы.
     * Можно задать заголовок весь сразу или шаблон для заголовка
     *
     * @param string $title
     */
    public function setTitle($title) {
        $contentID = Engine::Get()->getRequest()->getContentID();
        Engine::GetContentDriver()->getContent($contentID)->setField('title', $title);
    }

    /**
     * Задать параметр для title.
     * Если ваш title имеет вид "{xxx} - next title"
     * то можно в title заменить только параметр xxx.
     *
     * @param string $param
     * @param string $value
     */
    public function setTitleParameter($param, $value) {
        $title = $this->getTitle();
        $title = str_replace('{'.$param.'}', $value, $title);
        $this->setTitle($title);
    }

    /**
     * Получить title или его шаблон
     *
     * @return string
     */
    public function getTitle() {
        $contentID = Engine::Get()->getRequest()->getContentID();
        return Engine::GetContentDriver()->getContent($contentID)->getField('title');
    }

    /**
     * Получить ключевые слова
     *
     * @return string
     */
    public function getMetaKeywords() {
        try {
            return $this->getMetaTag('keywords');
        } catch (Exception $e) {

        }
        return false;
    }

    /**
     * Получить описание
     *
     * @return string
     */
    public function getMetaDescription() {
        try {
            return $this->getMetaTag('description');
        } catch (Exception $e) {

        }
        return false;
    }

    /**
     * Задать ключевые слова.
     * Мета-тег keywords
     *
     * @param string $keywords
     */
    public function setMetaKeywords($keywords) {
        $this->setMetaTag('keywords', $keywords);
    }

    /**
     * Задать описание страницы.
     * meta-description
     *
     * @param string $description
     */
    public function setMetaDescription($description) {
        $this->setMetaTag('description', $description);
    }

    /**
     * Задать код верификации сайта для Яндекс-сервисов
     *
     * @param string $code
     */
    public function setMetaYandexVerification($code) {
        $this->setMetaTag('yandex-verification', $code);
    }

    /**
     * Задать код верификации для Google-сайтов
     *
     * @param string $code
     */
    public function setMetaGoogleVerification($code) {
        $this->setMetaTag('google-site-verification', $code);
    }

    /**
     * Задать мета-тег
     *
     * @param string $name
     * @param string $value
     */
    public function setMetaTag($name, $value) {
        $name = trim($name);

        // убираем все лишние символы
        $value = str_replace(array("\r", "\n", "\t"), '', $value);
        $value = str_replace('  ', ' ', $value);

        $value = trim($value);
        if (!$name) {
            throw new Engine_Exception("Meta-tag name is empty");
        }
        if ($value) {
            $this->_metaArray[$name] = $value;
        } else {
            // если значения нет - смысла в теге нет - убираем его
            unset($this->_metaArray[$name]);
        }
    }

    /**
     * Задать open graph тег
     *
     * @param string $name
     * @param string $value
     */
    public function setOpenGraphTag($name, $value) {
        $name = trim($name);

        // убираем все лишние символы
        $value = str_replace(array("\r", "\n", "\t"), '', $value);
        $value = str_replace('  ', ' ', $value);

        $value = trim($value);
        if (!$name) {
            throw new Engine_Exception("Meta-tag name is empty");
        }
        if ($value) {
            $this->_openGraphArray[$name] = $value;
        } else {
            // если значения нет - смысла в теге нет - убираем его
            unset($this->_openGraphArray[$name]);
        }
    }


    /**
     * Получить значения open graph тега
     *
     * @param string $name
     *
     * @return string
     *
     * @throws Engine_Exception
     */
    public function getOpenGraphTag($name) {
        $name = trim($name);
        if (!$name) {
            throw new Engine_Exception("Meta-tag name is empty");
        }
        if (isset($this->_openGraphArray[$name])) {
            return $this->_openGraphArray[$name];
        }
        throw new Engine_Exception("Meta-tag '{$name}' not found");
    }

    /**
     * Получить значения мета-тега
     *
     * @param string $name
     *
     * @return string
     *
     * @throws Engine_Exception
     */
    public function getMetaTag($name) {
        $name = trim($name);
        if (!$name) {
            throw new Engine_Exception("Meta-tag name is empty");
        }
        if (isset($this->_metaArray[$name])) {
            return $this->_metaArray[$name];
        }
        throw new Engine_Exception("Meta-tag '{$name}' not found");
    }

    /**
     * Добавить к странице RSS-ленту
     *
     * @param string $url
     * @param string $name
     */
    public function addFeedRSS($url, $name) {
        if (!$url) {
            throw new Engine_Exception("Empty url for RSS-feed");
        }

        $this->addLink('alternate', $url, $name, 'application/rss+xml');
    }

    /**
     * Добавить тег <link> в head
     *
     * @param string $rel
     * @param string $href
     * @param string $type
     */
    public function addLink($rel, $href, $title = false, $type = false) {
        $rel = trim($rel);
        $href = trim($href);
        $title = trim($title);
        $type = trim($type);

        if (!$rel) {
            throw new Engine_Exception('Invalid rel');
        }

        if (!$href) {
            throw new Engine_Exception('Invalid href');
        }

        $this->_linkArray[] = array(
        'rel' => $rel,
        'href' => $href,
        'type' => $type,
        'title' => $title,
        );
    }

    /**
     * Задать ссылку на favicon
     *
     * @param string $favicon
     */
    public function setFavicon($favicon) {
        $this->addLink('icon', $favicon, false, 'image/x-icon');
        $this->addLink('shortcut icon', $favicon, false, 'image/x-icon');
    }

    /**
     * Get
     *
     * @return Engine_HTMLHead
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    private function __construct() {

    }

    private function __clone() {

    }

    private static $_Instance = null;

    /**
     * Массив мета-тегов
     *
     * @var array
     */
    private $_metaArray = array();

    private $_linkArray = array();

    private $_openGraphArray = array();
}