<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Орфографические утилиты для текстов: замена тире, кавычек и т.п.
 *
 * @author Max
 * @author Kate
 * @package StringUtils
 * @copyright WebProduction
 */
class StringUtils_BadLanguageDetector {

    /**
     * Убрать "плохие слова" из текста
     *
     * @param string $text
     * @return string
     */
    public static function ReplaceBadWords($text) {
        return preg_replace('/([\pL-]+)/uise', 'self::_ReplaceCallback("$1");', $text);
    }


    private static function _ReplaceCallback($text) {
        $a = self::DetectBadWords($text);
        $text_original = $text;

        $mb = function_exists('mb_strlen');
        foreach ($a as $word => $matsArray) {
            $w = $word;
            foreach ($matsArray as $m) {
                if ($mb) {
                    $matLength = mb_strlen($m);
                    $pattern = '';
                    for ($j = 1; $j <= $matLength; $j++) {
                        $pattern .= '*';
                    }
                } else {
                    $pattern = '***';
                }

                $w = str_replace($m, $pattern, $w);
            }
            $text = str_replace($word, $w, $text);
            if ($matsArray && $text == $text_original) {
            	$text = '***';
            }
        }

        return $text;
    }


    /**
     * Получить массив "плохих слов" в тексте $text
     *
     * @param string $text
     * @return array
     */
    public static function DetectBadWords($text) {
        $result = array();

        $wordsArray = array();
        if (substr_count($text, ' ')) {
            if (preg_match_all('/([\pL-]+)/uis', $text, $r)) {
                $wordsArray = $r[1];
            }
        } else {
            $wordsArray[] = $text;
        }

        if ($wordsArray) {
            $matsArray = self::_GetDictionary();
            $mb = function_exists('mb_strlen');

            foreach ($wordsArray as $word) {
                foreach ($matsArray as $mat) {
                    if ($mb) {
                        if (preg_match("/{$mat}/ui", $word) && mb_strlen($mat)/mb_strlen($word) > 0.5) {
                            $result[$word][] = $mat;
                        } elseif (preg_match("/^{$mat}/ui", $word) || preg_match("/{$mat}$/ui", $word)) {
                            $result[$word][] = $mat;
                        }
                    } else {
                        if (preg_match("/{$mat}/ui", $word) && strlen($mat)/strlen($word) > 0.5) {
                            $result[$word][] = $mat;
                        } elseif (preg_match("/^{$mat}/ui", $word) || preg_match("/{$mat}$/ui", $word)) {
                            $result[$word][] = $mat;
                        }
                    }
                }
            }
        }

        return $result;
    }

    private static $_dictionaryArray = array();

    /**
     * @return array
     */
    private static function _GetDictionary() {
        if (!self::$_dictionaryArray) {
            $mats = file(__DIR__.'/badwords_dictionary.txt');
            foreach ($mats as $mat) {
                $mat = trim($mat);
                if (!$mat) {
                    continue;
                }
                self::$_dictionaryArray[] = $mat;
            }
        }
        return self::$_dictionaryArray;
    }

}