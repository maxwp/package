<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package StringUtils
 */
class StringUtils_Limiter {

    /**
     * Ограничить текст по словам.
     * Limit a string to only first $count words
     *
     * @param string $text
     * @param int $count
     * @return string
     */
    public static function LimitWords($text, $count) {
        if (!$text) {
            return $text;
        }
        if (preg_match('/^([^.!?\s]*[\.!?\s]+){0,'.$count.'}/', $text, $abstract)) {
            return $abstract[0];
        }
        return false;
    }

    /**
     * Ограничить строку по длинне.
     * В конец добавить троеточие (...)
     *
     * @param string $text
     * @param int $length
     * @return string
     */
    public static function LimitLength($text, $length) {
        if (!$text) {
            return $text;
        }

        $x = mb_substr($text, 0, $length);
        if (mb_strlen($x) != mb_strlen($text)) {
        	$x .= '...';
        }
        return $x;
    }

    /**
     * Ограничить текст по словам.
     *
     * В случаи превышения одного из первых $count слов длины $maxwordlength
     * текст обрежется до $charscount символов.
     *
     * При отсутствии необходимого количества символов в $text $sufix
     * конкетироваться к результату не будет.
     *
     * @author Ramm
     * @param string $text
     * @param int $count
     * @param int $maxwordlength
     * @param int $charscount
     * @param string $sufix
     * @return string
     */
    public static function LimitWordsSmart($text, $count, $maxwordlength = 30, $charscount = 200, $sufix = '...') {
        if (!$text) return $text;
        return preg_replace("/^((?:(?:[^;\:,\.!?\-\s]{1,$maxwordlength})[;\:,\.!?\-\s]+){{$count}}|(?:.{{$charscount}})).*$/us","$1$sufix", $text);
    }

    /**
     * Ограничить текст по предложениям.
     * Limit a string to only first $count sentences
     *
     * @param string $text
     * @param int $count
     * @return string
     */
    public static function LimitSentences($text, $count) {
        if (!$text) {
            return $text;
        }
        if (preg_match('/^([^.!?]*[\.!?]+){0,'.$count.'}/', $text, $abstract)) {
            return $abstract[0];
        }
        return false;
    }

    /**
     * Разбить текст на строки, максимальный размер строки равен $warp
     *
     * @param string $text
     * @param int $warp
     * @return string
     */
    public function wrapText($text, $warp) {
        $text = explode(' ', $text);
        $i = 0;
        $length = 0;
        while ($i <= count($text)) {
            $length += strlen($text[$i]);
            if ($length <= $warp) {
                $output .= $text[$i].' ';
                $i++;
            } else {
                $output .= "\n";
                $length = 0;
            }
        }
        return $output;
    }


}