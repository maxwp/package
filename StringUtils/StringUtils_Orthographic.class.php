<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Орфографические утилиты для текстов: замена тире, кавычек и т.п.
 *
 * @author Max
 * @copyright WebProduction
 * @package StringUtils
 */
class StringUtils_Orthographic {

    /**
     * Отформатировать тире в тексте:
     * заменить дефис на тире где это возможно
     *
     * @param string $text
     * @return string
     */
    public static function FormatDash($text) {
        $text = preg_replace("/([\s]+)\-([\s]+)/is", '$1&mdash;$2', $text);
        $text = preg_replace("/^\-([\s]+)/is", '&mdash;$1', $text);
        $text = preg_replace("/([\s]+)\-$/is", '$1&mdash;', $text);

        return $text;
    }

    /**
     * Отформатировать кавычки в тексте:
     *
     * @param string $text
     * @param string $type Тип кавычек
     * @return string
     */
    public static function FormatQuotes($text, $type = 'french') {
        $quotesArray = array('"', "&quot;");

        foreach ($quotesArray as $x) {
            // пробел+кавычка+не_пробел
            $text = preg_replace('/([\s]+)'.$x.'([^\s]+)/is', '$1&laquo;$2', $text);
            // не_пробел+кавычка+пробел
            $text = preg_replace('/([^\s]+)'.$x.'([\s\<]+)/is', '$1&raquo;$2', $text);
            // начало_текста+кавычка+не_пробел
            $text = preg_replace('/^'.$x.'([^\s]+)/is', '&laquo;$1', $text);
            // не_пробел+кавычка+конец_текста
            $text = preg_replace('/([^\s]+)'.$x.'$/is', '$1&raquo;', $text);
            // не_пробел+кавычка+точка/запятая/тсзпт/вопрос
            $text = preg_replace('/([^\s]+)'.$x.'([\.,;:\?\)]+)/is', '$1&raquo;$2', $text);
        }

        return $text;
    }

    /**
     * Форматировать значки (c), (r), (tm)
     *
     * @param string $text
     * @return string
     */
    public static function FormatCopyrights($text) {
        $text = str_replace('(c)', '&copy;', $text); // english
        $text = str_replace('(C)', '&copy;', $text); // english
        $text = str_replace('(с)', '&copy;', $text); // russian
        $text = str_replace('(С)', '&copy;', $text); // russian
        $text = str_replace('(r)', '&reg;', $text);
        $text = str_replace('(R)', '&reg;', $text);
        $text = str_replace('(tm)', '&trade;', $text);
        $text = str_replace('(TM)', '&trade;', $text); // english
        $text = str_replace('(тм)', '&trade;', $text);
        $text = str_replace('(ТМ)', '&trade;', $text); // russian
        return $text;
    }

    /**
     * Форматировать троеточие
     *
     * @param string $text
     * @return string
     */
    public static function FormatHellip($text) {
        $text = str_replace('...', '&hellip;', $text);
        return $text;
    }

    /**
     * Форматировать знаки восклицания.
     * Более четырех знаков восклицания подряд заменяются на один.
     *
     * @param string $text
     * @return string
     */
    public static function FormatExclamation($text) {
        $text = preg_replace('/([\!]{4,})/is', '!', $text);
        return $text;
    }

    /**
     * Первая буква должна быть всегда в верхнем регистре
     *
     * @param string $text
     * @return string
     */
    public static function FormatFirstSymbolUppercase($text) {
        $text = preg_replace("/^([^\s]{1,1}+)/uise", "self::_FormatFirstSymbolUppercase('$1')", $text);
        return $text;
    }

    private function _FormatFirstSymbolUppercase($symbol) {
        if (function_exists('mb_strtoupper')) {
            $symbol = mb_strtoupper($symbol);
        } else {
            $symbol = strtoupper($symbol);
        }
        return $symbol;
    }

    /**
     * Последний символ должен быть знаком препинания
     *
     * @param string $text
     * @return string
     */
    public static function FormatLastSymbolEnd($text) {
        $text = preg_replace("/([^\s]{1,1}+)$/uise", "self::_FormatLastSymbolEnd('$1')", $text);
        return $text;
    }

    private function _FormatLastSymbolEnd($symbol) {
        if (preg_match("/([a-z0-9а-яђѓєїљњћўџӂ]+)/uis", $symbol)) {
            return $symbol.'.';
        }
        return $symbol;
    }

    /**
     * Выполнить полное форматирование текста
     *
     * @param string $text
     * @return string
     */
    public static function FormatAll($text) {
        $text = self::FormatDash($text);
        $text = self::FormatQuotes($text);
        $text = self::FormatCopyrights($text);
        $text = self::FormatHellip($text);
        $text = self::FormatExclamation($text);
        $text = self::FormatFirstSymbolUppercase($text);
        $text = self::FormatLastSymbolEnd($text);
        return $text;
    }

}