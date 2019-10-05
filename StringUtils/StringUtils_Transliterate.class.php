<?php
/**
 * WebProduction Packages
 *
 * Copyright (C) 2007-2015 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Конвертация или транслитерация строк
 *
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 *
 * @copyright WebProduction
 *
 * @package StringUtils
 *
 * @todo use or merge with TextProcessor
 */
class StringUtils_Transliterate {

    /**
     * Транслитерировать текст из русского в английский
     *
     * @param string $text
     *
     * @return string
     */
    public static function TransliterateRuToEn($text) {
        // @todo: константы вынести
        $text = StringUtils_Converter::MBStrTr($text, "абвгдеёзийклмнопрстуфхъыэі", "abvgdeeziyklmnoprstufh'iei");
        $text = StringUtils_Converter::MBStrTr($text, "АБВГДЕЁЗИЙКЛМНОПРСТУФХЪЫЭІ", "ABVGDEEZIYKLMNOPRSTUFH'IEI");
        $text = StringUtils_Converter::MBStrTr(
            $text,
            array(
                "ж"=>"zh", "ц"=>"ts", "ч"=>"ch", "ш"=>"sh",
                "щ"=>"shch","ь"=>"", "ю"=>"yu", "я"=>"ya",
                "Ж"=>"ZH", "Ц"=>"TS", "Ч"=>"CH", "Ш"=>"SH",
                "Щ"=>"SHCH","Ь"=>"", "Ю"=>"YU", "Я"=>"YA",
                "ї"=>"i", "Ї"=>"Yi", "є"=>"ie", "Є"=>"Ye"
            )
        );

        return $text;
    }

    /**
     * Транслитерировать текст из английского в русский
     *
     * @param string $text
     *
     * @return string
     */
    public static function TransliterateEnToRu($text) {
        // @todo: константы вынести

        $text = StringUtils_Converter::MBStrTr($text, "abvgdeeziyklmnoprstufh'iei", "абвгдеёзийклмнопрстуфхъыеи");
        $text = StringUtils_Converter::MBStrTr($text, "ABVGDEEZIYKLMNOPRSTUFH'IEI", "АБВГДЕЁЗИЙКЛМНОПРСТУФХЪЫЕИ");
        $text = StringUtils_Converter::MBStrTr(
            $text,
            array(
                "zh"=>"ж", "ts"=>"ц", "ch"=>"ч", "sh"=>"ш",
                "shch"=>"щ", "yu"=>"ю", "ya"=>"я",
                "ZH"=>"Ж", "TS"=>"Ц", "CH"=>"Ч", "SH"=>"Ш",
                "SHCH"=>"Щ", "YU"=>"Ю", "YA"=>"Я",
                "ie"=>"є", "Ye"=>"Є",
                'w' => 'в'
            )
        );

        return $text;
    }

    /**
     * В случае ошибочного ввода получить строку с русскими символами.
     * Язык ввода определяется автоматически.
     * Например: (en) ghbdtn => привет
     *
     * @param string $text
     *
     * @return string
     * @throws StringUtils_Exception
     */
    public static function TransliterateCorrectTo($language, $text) {
        if (!$language || empty(self::$_CorrectArray[$language])) {
            throw new StringUtils_Exception('language');
        }

        // нет текста - нечего корректировать
        if (!$text) {
            return $text;
        }

        // определяем позицию в словарях
        foreach (self::$_CorrectArray as $languageFrom => $dictionaryString) {
            if ($languageFrom == $language) {
                continue; // skip ru->ru, en->en, ...
            }

            // пытаемся сделать коррекцию для выбранного from-языка
            try {
                $result = '';

                $len = mb_strlen($text);
                for ($j = 0; $j < $len; $j++) {
                    // символ строки
                    $symbol = mb_substr($text, $j, 1);

                    $pos = mb_strpos($dictionaryString, $symbol); // from

                    if ($pos === false) {
                        throw new StringUtils_Exception();
                    }

                    $result .= mb_substr(self::$_CorrectArray[$language], $pos, 1);
                }

                return $result;
            } catch (Exception $e) {

            }
        }

        if (!$result) {
            // не удалось сделать коррекцию по словарям
            throw new StringUtils_Exception();
        }

        return $result;
    }

    /**
     * Превратить строку в ошибочный ввод.
     * Например: (ru -> en) привет => ghbdtn
     *
     * @param string $languageFrom
     * @param string $languageTo
     * @param string $text
     *
     * @return string
     * @throws StringUtils_Exception
     */
    public static function TransliterateUncorrectTo($languageFrom, $languageTo, $text) {
        if (!$languageFrom || empty(self::$_CorrectArray[$languageFrom])) {
            throw new StringUtils_Exception('language');
        }

        if (!$languageTo || empty(self::$_CorrectArray[$languageTo])) {
            throw new StringUtils_Exception('language');
        }

        // нет текста - нечего корректировать
        if (!$text) {
            return $text;
        }

        // пытаемся сделать анти-коррекцию для выбранного языка
        try {
            $result = '';

            $len = mb_strlen($text);
            for ($j = 0; $j < $len; $j++) {
                // символ строки
                $symbol = mb_substr($text, $j, 1);

                $pos = mb_strpos(self::$_CorrectArray[$languageFrom], $symbol); // from

                if ($pos !== false) {
                    $result .= mb_substr(self::$_CorrectArray[$languageTo], $pos, 1);
                }
            }

            return $result;
        } catch (Exception $e) {

        }

        if (!$result) {
            // не удалось сделать коррекцию по словарям
            throw new StringUtils_Exception();
        }

        return $result;
    }

    /**
     * Массив ошибочных наборов для коррекций
     * (пробел и цифры добавил для того чтоб не летел фатал когда встречается в тексте пробел или цыфра)
     *
     * @var array
     */
    private static $_CorrectArray = array(
    'en' => "qwertyuiop[]asdfghjkl;'zxcvbnm,.QWERTYUIOP[]ASDFGHJKL;'ZXCVBNM,. 1234567890/?",
    'ru' => "йцукенгшщзхъфывапролджэячсмитьбюЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮ 1234567890.,",
    'ua' => "йцукенгшщзхїфівапролджєячсмитьбюЙЦУКЕНГШЩЗХЪЇФІВАПРОЛДЖЄЯЧСМИТЬБЮ 1234567890.,",
    );

}