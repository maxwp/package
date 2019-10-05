<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Утилита (Formatter) для быстрого форматирования URL/URI.
 * Позволяет отформатировать в абсолютный путь лююбой URL
 *
 * @author Max
 * @copyright WebProduction
 * @package StringUtils
 * @subpackage FormatterURL
 */
class StringUtils_FormatterURL extends StringUtils_AFormatter {

    /**
     * @param string $url
     * @return StringUtils_FormatterURL
     */
    public static function Create($url) {
        return new self($url);
    }

    /**
     * Форматировать URL в абсолютный путь.
     * URL будет начинаться c http://
     *
     * @return string
     */
    public function format() {
        $url = $this->getData();
        if (strpos($url, 'http://') !== 0) {
            $url = 'http://'.$url;
        }
        return $url;
    }

    /**
     * Форматировать URL в короткий путь
     * URL будет без http:// и без слеша в конце @todo
     *
     * @return string
     */
    public function formatShort() {
        $url = $this->getData();
        $url = preg_replace("/^http:\/\//", '', $url);
        return $url;
    }

    /**
     * Получить сводный массив по всему форматированию
     *
     * @return array
     */
    public function makeInfoArray() {
        $a = array();
        $a['url'] = $this->format();
        $a['short'] = $this->formatShort();
        return $a;
    }

}