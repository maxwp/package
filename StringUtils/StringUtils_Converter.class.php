<?php

/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * StringUtils_Converter
 *
 * @author DFox
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 *
 * @copyright WebProduction
 *
 * @package StringUtils
 */
class StringUtils_Converter {

    /**
     * Return csv array
     *
     * @param string $instring
     * @param string $encoding
     * 
     * @deprecated
     * 
     * @see CSV package
     * 
     * @todo delete this method
     * 
     * @return array in UTF encoding!
     */
    public static function CSV2Array($instring) {
        $strings = explode("\n", $instring);
        $matrix = array();
        foreach ($strings as $string) {
            $curstr = $string;
            $array = array();
            while (trim($curstr)) {
                $res = preg_match('/^\"(([^\"]|\"{2})*)\";?/', $curstr, $subpatt);
                if ($res) {
                    $part = str_replace('""', '"', $subpatt[1]);
                    $curstr = substr($curstr, strlen($subpatt[0]));
                    $array[] = $part;
                } else {
                    if (preg_match('/^([^;]*);?/', $curstr, $subpatt)) {
                        $curstr = substr($curstr, strlen($subpatt[0]));
                        $array[] = $subpatt[1];
                    } else {
                        return false;
                    }
                }
            }
            if (count($array)) {
                $matrix[] = $array;
            }
        }
        return $matrix;
    }

    /**
     * Convert array from inCharset to outCharset
     *
     * @param string $inCharset
     * @param string $outCharset
     * @param array $strarray
     * 
     * @deprecated
     */
    public static function ArrayStrIconv($inCharset, $outCharset, &$strarray) {
        foreach ($strarray as $k => $val) {
            if (is_array($val)) {
                self::ArrayStrIconv($inCharset, $outCharset, $strarray[$k]);
            } else {
                $strarray[$k] = iconv($inCharset, $outCharset, $val);
            }
        }
    }

    /**
     * Make trabscription of text
     * if $incharset is not UTF-8 it will be convertrs to UTF-8
     * than trancript than converts in previous charset(may be unnecessary)
     * 
     * @param string $text
     * @param string $incharset
     * 
     * @deprecated
     * 
     * @see StringUtils_Transliterate
     *
     * @author DFox
     * 
     * @return string
     */
    public static function Transcription($text, $incharset = 'UTF-8') {
        if ($incharset != 'UTF-8') {
            $text = iconv($incharset, 'UTF-8', $text);
        }

        $text = StringUtils_Transliterate::TransliterateRuToEn($text);

        if ($incharset != 'UTF-8') {
            $text = iconv('UTF-8', $incharset, $text);
        }
        return $text;
    }

    /**
     * Multybyte variant of function strtr
     *
     * @param string $text
     * 
     * @return string
     */
    public static function MBStrTr($text) {
        $tr = array();
        if (func_num_args() == 3) {
            $input = func_get_arg(1);
            $output = func_get_arg(2);
            $inplen = mb_strlen($input, 'UTF-8');
            $outlen = mb_strlen($output, 'UTF-8');

            if ($inplen == $outlen) {
                for ($i = 0; $i < $inplen; $i++) {
                    $tr[mb_substr($input, $i, 1, 'UTF-8')] = mb_substr($output, $i, 1, 'UTF-8');
                }
            }
        } elseif (func_num_args() == 2) {
            $tr = func_get_arg(1);
        }
        return str_replace(array_keys($tr), array_values($tr), $text);
    }

    /**
     * Replace $search on $replace until string consist $search
     *
     * @param string $search
     * @param string $replace
     * @param string $string
     * 
     * @return string
     */
    public static function TotalReplace($search, $replace, $string) {
        while (strpos($string, $search) !== false) {
            $string = str_replace($search, $replace, $string);
        }
        return $string;
    }

    /**
     * Clear currency 
     *
     * @param $string
     * 
     * @todo move to StringUtils_Money* ?
     *
     * @return string
     */
    public static function ClearCurrency($string) {
        $string = preg_replace('/бел.+/ius', '', $string);
        $string = preg_replace('/руб.+/ius', '', $string);
        $string = preg_replace('/гри.+/ius', '', $string);
        $result = trim($string);
        return $result;
    }

    /**
     * Translate float number to money string format
     *
     * @param float $numeric
     * @param string $lang only ua are present
     * 
     * @todo move to StringUtils_Money* ?
     * 
     * @return string
     */
    public static function FloatToMoney($numeric, $lang = 'ua', $penny = true, $currency = false, $money = true) {
        self::_InitMoneyWords($lang, $currency);
        if (!$money) {
            self::$_Money[$lang][$currency]['nomoney'] = true;
            self::$_Money[$lang][$currency]['null'] = '';
            self::$_Money[$lang][$currency]['nullcop'] = '';
            self::$_Money[$lang][$currency]['teensnames'][0] = '';
            self::$_Money[$lang][$currency]['teensnamesCop'][0] = '';
            foreach (self::$_Money[$lang][$currency]['nameMoney'][0] as &$item) {
                $pos = strpos($item, ' ');
                if ($pos !== false) {
                    $item = substr($item, 0, $pos);
                }
            }
            self::$_Money[$lang][$currency]['nameMoneyCop'][0] = array();
            self::$_Money[$lang][$currency]['nameCop'] = '';
        }
        $pos = strrpos($numeric, ",");
        $pos = $pos !== false ? $pos : strrpos($numeric, ".");
        if ($pos === false) {
            $grn = $numeric;
            $grn = ltrim($grn, '0');
            $result = self::_GetPaperMoney($grn, $lang, $currency);
            if ($penny && self::$_Money[$lang][$currency]['nameCop']) {
                $result .= ' ' . self::$_Money[$lang][$currency]['nullcop'];
            } else {
                $result .='' . self::$_Money[$lang][$currency]['nameCop'];
            }
        } else {
            if (($pos + 1) == strlen($numeric))
                $numeric .= '00';
            if (($pos + 2) == strlen($numeric))
                $numeric .= '0';
            $grn = substr($numeric, 0, $pos);
            $grn = ltrim($grn, '0');
            $cop = substr($numeric, $pos + 1);
            $result =
                self::_GetPaperMoney($grn, $lang, $currency) . ' ' . self::_GetMonetaryMoney($cop, $lang, $currency);
        }
        return $result;
    }

    private static function _GetPaperMoney($num, $lang, $currency) {
        $len = strlen($num);
        if ($len == 0) {
            return self::$_Money[$lang][$currency]['null'];
        }
        $res = '';
        for ($i = 0; $i < $len; $i++) {
            $a = ($i) % 3;
            if ($a == 0) {
                if (($len - $i - 2) > -1) {
                    if ($num[$len - $i - 2] == '1') {
                        $res = self::$_Money[$lang][$currency]['teens'][$num[$len - $i - 1]] 
                        . ' ' . self::$_Money[$lang][$currency]['teensnames'][$i / 3] . ' ' . $res;
                    } else if (($len - $i - 3) > -1) {
                        if (($num[$len - $i - 2] == '0') 
                            && ($num[$len - $i - 1] == '0') 
                            && ($num[$len - $i - 3] == '0') 
                            && ($i / 3 != 0)) {
                            $i; // sniffer commit
                        } else
                            $res =
                                self::$_Money[$lang][$currency]['nameMoney'][$i / 3][$num[$len - $i - 1]] . ' ' . $res;
                    } else
                        $res = self::$_Money[$lang][$currency]['nameMoney'][$i / 3][$num[$len - $i - 1]] . ' ' . $res;
                } else
                    $res = self::$_Money[$lang][$currency]['nameMoney'][$i / 3][$num[$len - $i - 1]] . ' ' . $res;
            } else if ($a == 1) {
                if ($num[$len - $i - 1] != '1')
                    $res = self::$_Money[$lang][$currency]['tens'][$num[$len - $i - 1]] . ' ' . $res;
            } else if ($a == 2) {
                $res = self::$_Money[$lang][$currency]['hundreds'][$num[$len - $i - 1]] . ' ' . $res;
            }
            $res = trim($res);
        }
        return mb_strtoupper(mb_substr($res, 0, 1, 'UTF-8'), 'UTF-8') 
            . mb_substr($res, 1, mb_strlen($res, 'UTF-8'), 'UTF-8');
    }

    private static function _GetMonetaryMoney($num, $lang, $currency) {
        $len = strlen($num);
        if ($len == 0) {
            return self::$_Money[$lang][$currency]['nullcop'];
        }
        $res = '';
        for ($i = 0; $i < $len; $i++) {
            $a = ($i) % 3;
            if ($a == 0) {
                if (($len - $i - 2) > -1) {
                    if ($num[$len - $i - 2] == '1') {
                        $res = self::$_Money[$lang][$currency]['teens'][$num[$len - $i - 1]]
                            . ' ' . self::$_Money[$lang][$currency]['teensnamesCop'][$i / 3] . ' ' . $res;
                    } else if (($len - $i - 3) > -1) {
                        if (($num[$len - $i - 2] == '0')
                            && ($num[$len - $i - 1] == '0')
                            && ($num[$len - $i - 3] == '0')
                            && ($i / 3 != 0)) {
                            $i; // sniffer commit
                        } else
                            $res = self::$_Money[$lang][$currency]['nameMoneyCop'][$i / 3][$num[$len - $i - 1]]
                                . ' ' . $res;
                    } else
                        $res = self::$_Money[$lang][$currency]['nameMoneyCop'][$i / 3][$num[$len - $i - 1]]
                            . ' ' . $res;
                } else
                    $res = self::$_Money[$lang][$currency]['nameMoneyCop'][$i / 3][$num[$len - $i - 1]] . ' ' . $res;
            } else if ($a == 1) {
                if ($num[$len - $i - 1] != '1')
                    $res = self::$_Money[$lang][$currency]['tens'][$num[$len - $i - 1]] . ' ' . $res;
            } else if ($a == 2) {
                $res = self::$_Money[$lang][$currency]['hundreds'][$num[$len - $i - 1]] . ' ' . $res;
            }
            $res = trim($res);
        }
        return mb_strtolower(mb_substr($res, 0, 1, 'UTF-8'), 'UTF-8')
        . mb_substr($res, 1, mb_strlen($res, 'UTF-8'), 'UTF-8');
    }

    private static function _InitMoneyWords($lang, $currency) {

        if (
            $currency == 'EUR'
            && (!isset(self::$_Money[$lang][$currency]) || isset(self::$_Money[$lang][$currency]['nomoney']))
        ) {
            if ($lang == 'ua') {
                self::$_Money['ua'][$currency] = array();
                self::$_Money[$lang][$currency]['null'] = 'Нуль євро';
                self::$_Money[$lang][$currency]['nullcop'] = 'нуль євроцентів.';
                self::$_Money[$lang][$currency]['upperdiff'] = 0;
                $cop[0] = 'євроцентів.';
                $cop[1] = 'один євроцент.';
                $cop[2] = 'два євроценти.';
                $cop[3] = 'три євроценти.';
                $cop[4] = 'чотири євроценти.';
                $cop[5] = 'п’ять євроцентів.';
                $cop[6] = 'шість євроцентів.';
                $cop[7] = 'сім євроцентів';
                $cop[8] = 'вісім євроцентів.';
                $cop[9] = 'дев’ять євроцентів.';
                $eur[0] = 'євро';
                $eur[1] = 'один євро';
                $eur[2] = 'два євро';
                $eur[3] = 'три євро';
                $eur[4] = 'чотири євро';
                $eur[5] = 'п’ять євро';
                $eur[6] = 'шість євро';
                $eur[7] = 'сім євро';
                $eur[8] = 'вісім євро';
                $eur[9] = 'дев’ять євро';
                self::$_Money[$lang][$currency]['tens'][0] = '';
                self::$_Money[$lang][$currency]['tens'][1] = 'десять';
                self::$_Money[$lang][$currency]['tens'][2] = 'двадцять';
                self::$_Money[$lang][$currency]['tens'][3] = 'тридцять';
                self::$_Money[$lang][$currency]['tens'][4] = 'сорок';
                self::$_Money[$lang][$currency]['tens'][5] = 'п’ятдесят';
                self::$_Money[$lang][$currency]['tens'][6] = 'шістдесят';
                self::$_Money[$lang][$currency]['tens'][7] = 'сімдесят';
                self::$_Money[$lang][$currency]['tens'][8] = 'вісімдесят';
                self::$_Money[$lang][$currency]['tens'][9] = 'дев’яносто';
                self::$_Money[$lang][$currency]['hundreds'][0] = '';
                self::$_Money[$lang][$currency]['hundreds'][1] = 'сто';
                self::$_Money[$lang][$currency]['hundreds'][2] = 'двісті';
                self::$_Money[$lang][$currency]['hundreds'][3] = 'триста';
                self::$_Money[$lang][$currency]['hundreds'][4] = 'чотириcта';
                self::$_Money[$lang][$currency]['hundreds'][5] = 'п’ятсот';
                self::$_Money[$lang][$currency]['hundreds'][6] = 'шістсот';
                self::$_Money[$lang][$currency]['hundreds'][7] = 'сімсот';
                self::$_Money[$lang][$currency]['hundreds'][8] = 'вісімсот';
                self::$_Money[$lang][$currency]['hundreds'][9] = 'дев’ятсот';
                self::$_Money[$lang][$currency]['teens'][0] = 'десять';
                self::$_Money[$lang][$currency]['teens'][1] = 'одинадцять';
                self::$_Money[$lang][$currency]['teens'][2] = 'дванадцять';
                self::$_Money[$lang][$currency]['teens'][3] = 'тринадцять';
                self::$_Money[$lang][$currency]['teens'][4] = 'чотирнадцять';
                self::$_Money[$lang][$currency]['teens'][5] = 'п’ятнадцять';
                self::$_Money[$lang][$currency]['teens'][6] = 'шістнадцять';
                self::$_Money[$lang][$currency]['teens'][7] = 'сімнадцять';
                self::$_Money[$lang][$currency]['teens'][8] = 'вісімнадцять';
                self::$_Money[$lang][$currency]['teens'][9] = 'дев’ятнадцять';
                $thous[0] = 'тисяч';
                $thous[1] = 'одна тисяча';
                $thous[2] = 'дві тисячі';
                $thous[3] = 'три тисячі';
                $thous[4] = 'чотири тисячі';
                $thous[5] = 'п’ять тисяч';
                $thous[6] = 'шість тисяч';
                $thous[7] = 'сім тисяч';
                $thous[8] = 'вісім тисяч';
                $thous[9] = 'дев’ять тисяч';
                $million[0] = 'мільйонів';
                $million[1] = 'один мільйон';
                $million[2] = 'два мільйони';
                $million[3] = 'три мільйони';
                $million[4] = 'чотири мільйони';
                $million[5] = 'п’ять мільйонів';
                $million[6] = 'шість мільйонів';
                $million[7] = 'сім мільйонів';
                $million[8] = 'вісім мільйонів';
                $million[9] = 'дев’ять мільйонів';
                $milliard[0] = 'мільярд';
                $milliard[1] = 'один мільярд';
                $milliard[2] = 'два мільярди';
                $milliard[3] = 'три мільярди';
                $milliard[4] = 'чотири мільярди';
                $milliard[5] = 'п’ять мільярдів';
                $milliard[6] = 'шість мільярдів';
                $milliard[7] = 'сім мільярдів';
                $milliard[8] = 'вісім мільярдів';
                $milliard[9] = 'дев’ять мільярдів';
                $trillion[0] = 'трильйонів';
                $trillion[1] = 'один трильйон';
                $trillion[2] = 'два трильйони';
                $trillion[3] = 'три трильйони';
                $trillion[4] = 'чотири трильйони';
                $trillion[5] = 'п’ять трильйонів';
                $trillion[6] = 'шість трильйонів';
                $trillion[7] = 'сім трильйонів';
                $trillion[8] = 'вісім трильйонів';
                $trillion[9] = 'дев’ять трильйонів';
                $quadrillion[0] = 'квадрильйонів';
                $quadrillion[1] = 'один квадрильйон';
                $quadrillion[2] = 'два квадрильйони';
                $quadrillion[3] = 'три квадрильйони';
                $quadrillion[4] = 'чотири квадрильйони';
                $quadrillion[5] = 'п’ять квадрильйонів';
                $quadrillion[6] = 'шість квадрильйонів';
                $quadrillion[7] = 'сім квадрильйонів';
                $quadrillion[8] = 'вісім квадрильйонів';
                $quadrillion[9] = 'дев’ять квадрильйонів';
                self::$_Money[$lang][$currency]['teensnames'][0] = 'євро';
                self::$_Money[$lang][$currency]['teensnamesCop'][0] = 'євроцентів.';
                self::$_Money[$lang][$currency]['teensnames'][1] = 'тисяч';
                self::$_Money[$lang][$currency]['teensnames'][2] = 'мільйонів';
                self::$_Money[$lang][$currency]['teensnames'][3] = 'мільярдів';
                self::$_Money[$lang][$currency]['teensnames'][4] = 'трильйонів';
                self::$_Money[$lang][$currency]['teensnames'][5] = 'квадрильйонів';
                self::$_Money[$lang][$currency]['nameMoney'][] = $eur;
                self::$_Money[$lang][$currency]['nameMoneyCop'][] = $cop;
                self::$_Money[$lang][$currency]['nameMoney'][] = $thous;
                self::$_Money[$lang][$currency]['nameMoney'][] = $million;
                self::$_Money[$lang][$currency]['nameMoney'][] = $milliard;
                self::$_Money[$lang][$currency]['nameMoney'][] = $trillion;
                self::$_Money[$lang][$currency]['nameMoney'][] = $quadrillion;
                self::$_Money[$lang][$currency]['nameCop'] = 'євроцент.';
            } elseif ($lang == 'ru') {
                self::$_Money['ru'][$currency] = array();
                self::$_Money[$lang][$currency]['null'] = 'Ноль евро';
                self::$_Money[$lang][$currency]['nullcop'] = 'ноль евроцентов.';
                self::$_Money[$lang][$currency]['upperdiff'] = 0;
                $cop[0] = '';
                $cop[1] = 'один евроцент.';
                $cop[2] = 'два евроцента.';
                $cop[3] = 'три евроцента.';
                $cop[4] = 'четыре евроцента.';
                $cop[5] = 'пять евроцентов.';
                $cop[6] = 'шесть евроцентов.';
                $cop[7] = 'семь евроцентов.';
                $cop[8] = 'восемь евроцентов.';
                $cop[9] = 'девять евроцентов.';
                $eur[0] = 'евро';
                $eur[1] = 'один евро';
                $eur[2] = 'два евро';
                $eur[3] = 'три евро';
                $eur[4] = 'четыре евро';
                $eur[5] = 'пять евро';
                $eur[6] = 'шесть евро';
                $eur[7] = 'семь евро';
                $eur[8] = 'восемь евро';
                $eur[9] = 'девять евро';
                self::$_Money[$lang][$currency]['tens'][0] = '';
                self::$_Money[$lang][$currency]['tens'][1] = 'десять';
                self::$_Money[$lang][$currency]['tens'][2] = 'двадцать';
                self::$_Money[$lang][$currency]['tens'][3] = 'тридцать';
                self::$_Money[$lang][$currency]['tens'][4] = 'сорок';
                self::$_Money[$lang][$currency]['tens'][5] = 'пятьдесят';
                self::$_Money[$lang][$currency]['tens'][6] = 'шестьдесят';
                self::$_Money[$lang][$currency]['tens'][7] = 'семьдесят';
                self::$_Money[$lang][$currency]['tens'][8] = 'восемьдесят';
                self::$_Money[$lang][$currency]['tens'][9] = 'девяносто';
                self::$_Money[$lang][$currency]['hundreds'][0] = '';
                self::$_Money[$lang][$currency]['hundreds'][1] = 'сто';
                self::$_Money[$lang][$currency]['hundreds'][2] = 'двести';
                self::$_Money[$lang][$currency]['hundreds'][3] = 'триста';
                self::$_Money[$lang][$currency]['hundreds'][4] = 'четыреста';
                self::$_Money[$lang][$currency]['hundreds'][5] = 'пятьсот';
                self::$_Money[$lang][$currency]['hundreds'][6] = 'шестьсот';
                self::$_Money[$lang][$currency]['hundreds'][7] = 'семьсот';
                self::$_Money[$lang][$currency]['hundreds'][8] = 'восемьсот';
                self::$_Money[$lang][$currency]['hundreds'][9] = 'девятьсот';
                self::$_Money[$lang][$currency]['teens'][0] = 'десять';
                self::$_Money[$lang][$currency]['teens'][1] = 'одиннадцать';
                self::$_Money[$lang][$currency]['teens'][2] = 'двенадцать';
                self::$_Money[$lang][$currency]['teens'][3] = 'тринадцать';
                self::$_Money[$lang][$currency]['teens'][4] = 'четырнадцать';
                self::$_Money[$lang][$currency]['teens'][5] = 'пятнадцать';
                self::$_Money[$lang][$currency]['teens'][6] = 'шестнадцать';
                self::$_Money[$lang][$currency]['teens'][7] = 'семнадцать';
                self::$_Money[$lang][$currency]['teens'][8] = 'восемнадцать';
                self::$_Money[$lang][$currency]['teens'][9] = 'девятнадцать';
                $thous[0] = 'тысяч';
                $thous[1] = 'одна тысяча';
                $thous[2] = 'две тысячи';
                $thous[3] = 'три тысячи';
                $thous[4] = 'четыре тысячи';
                $thous[5] = 'пять тысяч';
                $thous[6] = 'шесть тысяч';
                $thous[7] = 'семь тысяч';
                $thous[8] = 'восемь тысяч';
                $thous[9] = 'девять тысяч';
                $million[0] = 'миллионов';
                $million[1] = 'один миллион';
                $million[2] = 'два миллиона';
                $million[3] = 'три миллиона';
                $million[4] = 'четыре миллиона';
                $million[5] = 'пять миллионов';
                $million[6] = 'шесть миллионов';
                $million[7] = 'семь миллионов';
                $million[8] = 'восемь миллионов';
                $million[9] = 'девять миллионов';
                $milliard[0] = 'миллиард';
                $milliard[1] = 'один миллиард';
                $milliard[2] = 'два миллиарда';
                $milliard[3] = 'три миллиарда';
                $milliard[4] = 'четыре миллиарда';
                $milliard[5] = 'пять миллиардов';
                $milliard[6] = 'шесть миллиардов';
                $milliard[7] = 'семь миллиардов';
                $milliard[8] = 'восемь миллиардов';
                $milliard[9] = 'девять миллиардов';
                self::$_Money[$lang][$currency]['teensnames'][0] = 'евро';
                self::$_Money[$lang][$currency]['teensnamesCop'][0] = 'евроцентов.';
                self::$_Money[$lang][$currency]['teensnames'][1] = 'тысяч';
                self::$_Money[$lang][$currency]['teensnames'][2] = 'миллионов';
                self::$_Money[$lang][$currency]['teensnames'][3] = 'миллиардов';
                self::$_Money[$lang][$currency]['nameMoney'][] = $eur;
                self::$_Money[$lang][$currency]['nameMoneyCop'][] = $cop;
                self::$_Money[$lang][$currency]['nameMoney'][] = $thous;
                self::$_Money[$lang][$currency]['nameMoney'][] = $million;
                self::$_Money[$lang][$currency]['nameMoney'][] = $milliard;
                self::$_Money[$lang][$currency]['nameCop'] = 'евроцент.';
            }
        } elseif (
            $currency == 'USD'
            && (!isset(self::$_Money[$lang][$currency]) || isset(self::$_Money[$lang][$currency]['nomoney']))
        ) {
            if ($lang == 'ua') {
                self::$_Money['ua'][$currency] = array();
                self::$_Money[$lang][$currency]['null'] = 'Нуль доларів';
                self::$_Money[$lang][$currency]['nullcop'] = 'нуль центів.';
                self::$_Money[$lang][$currency]['upperdiff'] = 0;
                $cop[0] = 'центів.';
                $cop[1] = 'один цент.';
                $cop[2] = 'два центи.';
                $cop[3] = 'три центи.';
                $cop[4] = 'чотири центи.';
                $cop[5] = 'п’ять центів.';
                $cop[6] = 'шість центів.';
                $cop[7] = 'сім центів';
                $cop[8] = 'вісім центів.';
                $cop[9] = 'дев’ять центів.';
                $grn[0] = 'доларів';
                $grn[1] = 'один долар';
                $grn[2] = 'два долари';
                $grn[3] = 'три долари';
                $grn[4] = 'чотири долари';
                $grn[5] = 'п’ять доларів';
                $grn[6] = 'шість доларів';
                $grn[7] = 'сім доларів';
                $grn[8] = 'вісім доларів';
                $grn[9] = 'дев’ять доларів';
                self::$_Money[$lang][$currency]['tens'][0] = '';
                self::$_Money[$lang][$currency]['tens'][1] = 'десять';
                self::$_Money[$lang][$currency]['tens'][2] = 'двадцять';
                self::$_Money[$lang][$currency]['tens'][3] = 'тридцять';
                self::$_Money[$lang][$currency]['tens'][4] = 'сорок';
                self::$_Money[$lang][$currency]['tens'][5] = 'п’ятдесят';
                self::$_Money[$lang][$currency]['tens'][6] = 'шістдесят';
                self::$_Money[$lang][$currency]['tens'][7] = 'сімдесят';
                self::$_Money[$lang][$currency]['tens'][8] = 'вісімдесят';
                self::$_Money[$lang][$currency]['tens'][9] = 'дев’яносто';
                self::$_Money[$lang][$currency]['hundreds'][0] = '';
                self::$_Money[$lang][$currency]['hundreds'][1] = 'сто';
                self::$_Money[$lang][$currency]['hundreds'][2] = 'двісті';
                self::$_Money[$lang][$currency]['hundreds'][3] = 'триста';
                self::$_Money[$lang][$currency]['hundreds'][4] = 'чотириcта';
                self::$_Money[$lang][$currency]['hundreds'][5] = 'п’ятсот';
                self::$_Money[$lang][$currency]['hundreds'][6] = 'шістсот';
                self::$_Money[$lang][$currency]['hundreds'][7] = 'сімсот';
                self::$_Money[$lang][$currency]['hundreds'][8] = 'вісімсот';
                self::$_Money[$lang][$currency]['hundreds'][9] = 'дев’ятсот';
                self::$_Money[$lang][$currency]['teens'][0] = 'десять';
                self::$_Money[$lang][$currency]['teens'][1] = 'одинадцять';
                self::$_Money[$lang][$currency]['teens'][2] = 'дванадцять';
                self::$_Money[$lang][$currency]['teens'][3] = 'тринадцять';
                self::$_Money[$lang][$currency]['teens'][4] = 'чотирнадцять';
                self::$_Money[$lang][$currency]['teens'][5] = 'п’ятнадцять';
                self::$_Money[$lang][$currency]['teens'][6] = 'шістнадцять';
                self::$_Money[$lang][$currency]['teens'][7] = 'сімнадцять';
                self::$_Money[$lang][$currency]['teens'][8] = 'вісімнадцять';
                self::$_Money[$lang][$currency]['teens'][9] = 'дев’ятнадцять';
                $thous[0] = 'тисяч';
                $thous[1] = 'одна тисяча';
                $thous[2] = 'дві тисячі';
                $thous[3] = 'три тисячі';
                $thous[4] = 'чотири тисячі';
                $thous[5] = 'п’ять тисяч';
                $thous[6] = 'шість тисяч';
                $thous[7] = 'сім тисяч';
                $thous[8] = 'вісім тисяч';
                $thous[9] = 'дев’ять тисяч';
                $million[0] = 'мільйонів';
                $million[1] = 'один мільйон';
                $million[2] = 'два мільйони';
                $million[3] = 'три мільйони';
                $million[4] = 'чотири мільйони';
                $million[5] = 'п’ять мільйонів';
                $million[6] = 'шість мільйонів';
                $million[7] = 'сім мільйонів';
                $million[8] = 'вісім мільйонів';
                $million[9] = 'дев’ять мільйонів';
                $milliard[0] = 'мільярд';
                $milliard[1] = 'один мільярд';
                $milliard[2] = 'два мільярди';
                $milliard[3] = 'три мільярди';
                $milliard[4] = 'чотири мільярди';
                $milliard[5] = 'п’ять мільярдів';
                $milliard[6] = 'шість мільярдів';
                $milliard[7] = 'сім мільярдів';
                $milliard[8] = 'вісім мільярдів';
                $milliard[9] = 'дев’ять мільярдів';
                $trillion[0] = 'трильйонів';
                $trillion[1] = 'один трильйон';
                $trillion[2] = 'два трильйони';
                $trillion[3] = 'три трильйони';
                $trillion[4] = 'чотири трильйони';
                $trillion[5] = 'п’ять трильйонів';
                $trillion[6] = 'шість трильйонів';
                $trillion[7] = 'сім трильйонів';
                $trillion[8] = 'вісім трильйонів';
                $trillion[9] = 'дев’ять трильйонів';
                $quadrillion[0] = 'квадрильйонів';
                $quadrillion[1] = 'один квадрильйон';
                $quadrillion[2] = 'два квадрильйони';
                $quadrillion[3] = 'три квадрильйони';
                $quadrillion[4] = 'чотири квадрильйони';
                $quadrillion[5] = 'п’ять квадрильйонів';
                $quadrillion[6] = 'шість квадрильйонів';
                $quadrillion[7] = 'сім квадрильйонів';
                $quadrillion[8] = 'вісім квадрильйонів';
                $quadrillion[9] = 'дев’ять квадрильйонів';
                self::$_Money[$lang][$currency]['teensnames'][0] = 'доларів';
                self::$_Money[$lang][$currency]['teensnamesCop'][0] = 'центів.';
                self::$_Money[$lang][$currency]['teensnames'][1] = 'тисяч';
                self::$_Money[$lang][$currency]['teensnames'][2] = 'мільйонів';
                self::$_Money[$lang][$currency]['teensnames'][3] = 'мільярдів';
                self::$_Money[$lang][$currency]['teensnames'][4] = 'трильйонів';
                self::$_Money[$lang][$currency]['teensnames'][5] = 'квадрильйонів';
                self::$_Money[$lang][$currency]['nameMoney'][] = $grn;
                self::$_Money[$lang][$currency]['nameMoneyCop'][] = $cop;
                self::$_Money[$lang][$currency]['nameMoney'][] = $thous;
                self::$_Money[$lang][$currency]['nameMoney'][] = $million;
                self::$_Money[$lang][$currency]['nameMoney'][] = $milliard;
                self::$_Money[$lang][$currency]['nameMoney'][] = $trillion;
                self::$_Money[$lang][$currency]['nameMoney'][] = $quadrillion;
                self::$_Money[$lang][$currency]['nameCop'] = 'цент.';
            } elseif ($lang == 'ru') {
                self::$_Money['ru'][$currency] = array();
                self::$_Money[$lang][$currency]['null'] = 'Ноль долларов';
                self::$_Money[$lang][$currency]['nullcop'] = 'ноль центов.';
                self::$_Money[$lang][$currency]['upperdiff'] = 0;
                $cop[0] = '';
                $cop[1] = 'один цент.';
                $cop[2] = 'два цента.';
                $cop[3] = 'три цента.';
                $cop[4] = 'четыре цента.';
                $cop[5] = 'пять центов.';
                $cop[6] = 'шесть центов.';
                $cop[7] = 'семь центов.';
                $cop[8] = 'восемь центов.';
                $cop[9] = 'девять центов.';
                $grn[0] = 'долларов';
                $grn[1] = 'один доллар';
                $grn[2] = 'два доллара';
                $grn[3] = 'три доллара';
                $grn[4] = 'четыре доллара';
                $grn[5] = 'пять долларов';
                $grn[6] = 'шесть долларов';
                $grn[7] = 'семь долларов';
                $grn[8] = 'восемь долларов';
                $grn[9] = 'девять долларов';
                self::$_Money[$lang][$currency]['tens'][0] = '';
                self::$_Money[$lang][$currency]['tens'][1] = 'десять';
                self::$_Money[$lang][$currency]['tens'][2] = 'двадцать';
                self::$_Money[$lang][$currency]['tens'][3] = 'тридцать';
                self::$_Money[$lang][$currency]['tens'][4] = 'сорок';
                self::$_Money[$lang][$currency]['tens'][5] = 'пятьдесят';
                self::$_Money[$lang][$currency]['tens'][6] = 'шестьдесят';
                self::$_Money[$lang][$currency]['tens'][7] = 'семьдесят';
                self::$_Money[$lang][$currency]['tens'][8] = 'восемьдесят';
                self::$_Money[$lang][$currency]['tens'][9] = 'девяносто';
                self::$_Money[$lang][$currency]['hundreds'][0] = '';
                self::$_Money[$lang][$currency]['hundreds'][1] = 'сто';
                self::$_Money[$lang][$currency]['hundreds'][2] = 'двести';
                self::$_Money[$lang][$currency]['hundreds'][3] = 'триста';
                self::$_Money[$lang][$currency]['hundreds'][4] = 'четыреста';
                self::$_Money[$lang][$currency]['hundreds'][5] = 'пятьсот';
                self::$_Money[$lang][$currency]['hundreds'][6] = 'шестьсот';
                self::$_Money[$lang][$currency]['hundreds'][7] = 'семьсот';
                self::$_Money[$lang][$currency]['hundreds'][8] = 'восемьсот';
                self::$_Money[$lang][$currency]['hundreds'][9] = 'девятьсот';
                self::$_Money[$lang][$currency]['teens'][0] = 'десять';
                self::$_Money[$lang][$currency]['teens'][1] = 'одиннадцать';
                self::$_Money[$lang][$currency]['teens'][2] = 'двенадцать';
                self::$_Money[$lang][$currency]['teens'][3] = 'тринадцать';
                self::$_Money[$lang][$currency]['teens'][4] = 'четырнадцать';
                self::$_Money[$lang][$currency]['teens'][5] = 'пятнадцать';
                self::$_Money[$lang][$currency]['teens'][6] = 'шестнадцать';
                self::$_Money[$lang][$currency]['teens'][7] = 'семнадцать';
                self::$_Money[$lang][$currency]['teens'][8] = 'восемнадцать';
                self::$_Money[$lang][$currency]['teens'][9] = 'девятнадцать';
                $thous[0] = 'тысяч';
                $thous[1] = 'одна тысяча';
                $thous[2] = 'две тысячи';
                $thous[3] = 'три тысячи';
                $thous[4] = 'четыре тысячи';
                $thous[5] = 'пять тысяч';
                $thous[6] = 'шесть тысяч';
                $thous[7] = 'семь тысяч';
                $thous[8] = 'восемь тысяч';
                $thous[9] = 'девять тысяч';
                $million[0] = 'миллионов';
                $million[1] = 'один миллион';
                $million[2] = 'два миллиона';
                $million[3] = 'три миллиона';
                $million[4] = 'четыре миллиона';
                $million[5] = 'пять миллионов';
                $million[6] = 'шесть миллионов';
                $million[7] = 'семь миллионов';
                $million[8] = 'восемь миллионов';
                $million[9] = 'девять миллионов';
                $milliard[0] = 'миллиард';
                $milliard[1] = 'один миллиард';
                $milliard[2] = 'два миллиарда';
                $milliard[3] = 'три миллиарда';
                $milliard[4] = 'четыре миллиарда';
                $milliard[5] = 'пять миллиардов';
                $milliard[6] = 'шесть миллиардов';
                $milliard[7] = 'семь миллиардов';
                $milliard[8] = 'восемь миллиардов';
                $milliard[9] = 'девять миллиардов';
                self::$_Money[$lang][$currency]['teensnames'][0] = 'долларов';
                self::$_Money[$lang][$currency]['teensnamesCop'][0] = 'центов.';
                self::$_Money[$lang][$currency]['teensnames'][1] = 'тысяч';
                self::$_Money[$lang][$currency]['teensnames'][2] = 'миллионов';
                self::$_Money[$lang][$currency]['teensnames'][3] = 'миллиардов';
                self::$_Money[$lang][$currency]['nameMoney'][] = $grn;
                self::$_Money[$lang][$currency]['nameMoneyCop'][] = $cop;
                self::$_Money[$lang][$currency]['nameMoney'][] = $thous;
                self::$_Money[$lang][$currency]['nameMoney'][] = $million;
                self::$_Money[$lang][$currency]['nameMoney'][] = $milliard;
                self::$_Money[$lang][$currency]['nameCop'] = 'цент.';
            }
        } elseif (
            $currency == 'UAH' && $lang == 'ua'
            && (!isset(self::$_Money[$lang][$currency]) || isset(self::$_Money[$lang][$currency]['nomoney']))
        ) {
            self::$_Money['ua'] = array();
            self::$_Money[$lang][$currency]['null'] = 'Нуль гривень';
            self::$_Money[$lang][$currency]['nullcop'] = 'нуль копійок.';
            self::$_Money[$lang][$currency]['upperdiff'] = 0;
            $cop[0] = 'копійок.';
            $cop[1] = 'одна копійка.';
            $cop[2] = 'дві копійки.';
            $cop[3] = 'три копійки.';
            $cop[4] = 'чотири копійки.';
            $cop[5] = 'п’ять копійок.';
            $cop[6] = 'шість копійок.';
            $cop[7] = 'сім копійок.';
            $cop[8] = 'вісім копійок.';
            $cop[9] = 'дев’ять копійок.';
            $grn[0] = 'гривень';
            $grn[1] = 'одна гривня';
            $grn[2] = 'дві гривні';
            $grn[3] = 'три гривні';
            $grn[4] = 'чотири гривні';
            $grn[5] = 'п’ять гривень';
            $grn[6] = 'шість гривень';
            $grn[7] = 'сім гривень';
            $grn[8] = 'вісім гривень';
            $grn[9] = 'дев’ять гривень';
            self::$_Money[$lang][$currency]['tens'][0] = '';
            self::$_Money[$lang][$currency]['tens'][1] = 'десять';
            self::$_Money[$lang][$currency]['tens'][2] = 'двадцять';
            self::$_Money[$lang][$currency]['tens'][3] = 'тридцять';
            self::$_Money[$lang][$currency]['tens'][4] = 'сорок';
            self::$_Money[$lang][$currency]['tens'][5] = 'п’ятдесят';
            self::$_Money[$lang][$currency]['tens'][6] = 'шістдесят';
            self::$_Money[$lang][$currency]['tens'][7] = 'сімдесят';
            self::$_Money[$lang][$currency]['tens'][8] = 'вісімдесят';
            self::$_Money[$lang][$currency]['tens'][9] = 'дев’яносто';
            self::$_Money[$lang][$currency]['hundreds'][0] = '';
            self::$_Money[$lang][$currency]['hundreds'][1] = 'сто';
            self::$_Money[$lang][$currency]['hundreds'][2] = 'двісті';
            self::$_Money[$lang][$currency]['hundreds'][3] = 'триста';
            self::$_Money[$lang][$currency]['hundreds'][4] = 'чотириcта';
            self::$_Money[$lang][$currency]['hundreds'][5] = 'п’ятсот';
            self::$_Money[$lang][$currency]['hundreds'][6] = 'шістсот';
            self::$_Money[$lang][$currency]['hundreds'][7] = 'сімсот';
            self::$_Money[$lang][$currency]['hundreds'][8] = 'вісімсот';
            self::$_Money[$lang][$currency]['hundreds'][9] = 'дев’ятсот';
            self::$_Money[$lang][$currency]['teens'][0] = 'десять';
            self::$_Money[$lang][$currency]['teens'][1] = 'одинадцять';
            self::$_Money[$lang][$currency]['teens'][2] = 'дванадцять';
            self::$_Money[$lang][$currency]['teens'][3] = 'тринадцять';
            self::$_Money[$lang][$currency]['teens'][4] = 'чотирнадцять';
            self::$_Money[$lang][$currency]['teens'][5] = 'п’ятнадцять';
            self::$_Money[$lang][$currency]['teens'][6] = 'шістнадцять';
            self::$_Money[$lang][$currency]['teens'][7] = 'сімнадцять';
            self::$_Money[$lang][$currency]['teens'][8] = 'вісімнадцять';
            self::$_Money[$lang][$currency]['teens'][9] = 'дев’ятнадцять';
            $thous[0] = 'тисяч';
            $thous[1] = 'одна тисяча';
            $thous[2] = 'дві тисячі';
            $thous[3] = 'три тисячі';
            $thous[4] = 'чотири тисячі';
            $thous[5] = 'п’ять тисяч';
            $thous[6] = 'шість тисяч';
            $thous[7] = 'сім тисяч';
            $thous[8] = 'вісім тисяч';
            $thous[9] = 'дев’ять тисяч';
            $million[0] = 'мільйонів';
            $million[1] = 'один мільйон';
            $million[2] = 'два мільйони';
            $million[3] = 'три мільйони';
            $million[4] = 'чотири мільйони';
            $million[5] = 'п’ять мільйонів';
            $million[6] = 'шість мільйонів';
            $million[7] = 'сім мільйонів';
            $million[8] = 'вісім мільйонів';
            $million[9] = 'дев’ять мільйонів';
            $milliard[0] = 'мільярд';
            $milliard[1] = 'один мільярд';
            $milliard[2] = 'два мільярди';
            $milliard[3] = 'три мільярди';
            $milliard[4] = 'чотири мільярди';
            $milliard[5] = 'п’ять мільярдів';
            $milliard[6] = 'шість мільярдів';
            $milliard[7] = 'сім мільярдів';
            $milliard[8] = 'вісім мільярдів';
            $milliard[9] = 'дев’ять мільярдів';
            $trillion[0] = 'трильйонів';
            $trillion[1] = 'один трильйон';
            $trillion[2] = 'два трильйони';
            $trillion[3] = 'три трильйони';
            $trillion[4] = 'чотири трильйони';
            $trillion[5] = 'п’ять трильйонів';
            $trillion[6] = 'шість трильйонів';
            $trillion[7] = 'сім трильйонів';
            $trillion[8] = 'вісім трильйонів';
            $trillion[9] = 'дев’ять трильйонів';
            $quadrillion[0] = 'квадрильйонів';
            $quadrillion[1] = 'один квадрильйон';
            $quadrillion[2] = 'два квадрильйони';
            $quadrillion[3] = 'три квадрильйони';
            $quadrillion[4] = 'чотири квадрильйони';
            $quadrillion[5] = 'п’ять квадрильйонів';
            $quadrillion[6] = 'шість квадрильйонів';
            $quadrillion[7] = 'сім квадрильйонів';
            $quadrillion[8] = 'вісім квадрильйонів';
            $quadrillion[9] = 'дев’ять квадрильйонів';
            self::$_Money[$lang][$currency]['teensnames'][0] = 'гривень';
            self::$_Money[$lang][$currency]['teensnamesCop'][0] = 'копійок.';
            self::$_Money[$lang][$currency]['teensnames'][1] = 'тисяч';
            self::$_Money[$lang][$currency]['teensnames'][2] = 'мільйонів';
            self::$_Money[$lang][$currency]['teensnames'][3] = 'мільярдів';
            self::$_Money[$lang][$currency]['teensnames'][4] = 'трильйонів';
            self::$_Money[$lang][$currency]['teensnames'][5] = 'квадрильйонів';
            self::$_Money[$lang][$currency]['nameMoney'][] = $grn;
            self::$_Money[$lang][$currency]['nameMoneyCop'][] = $cop;
            self::$_Money[$lang][$currency]['nameMoney'][] = $thous;
            self::$_Money[$lang][$currency]['nameMoney'][] = $million;
            self::$_Money[$lang][$currency]['nameMoney'][] = $milliard;
            self::$_Money[$lang][$currency]['nameMoney'][] = $trillion;
            self::$_Money[$lang][$currency]['nameMoney'][] = $quadrillion;
            self::$_Money[$lang][$currency]['nameCop'] = 'коп.';
        } elseif (
            $currency == 'UAH' && $lang == 'ru' &&
            (!isset(self::$_Money[$lang][$currency]) || isset(self::$_Money[$lang][$currency]['nomoney']))
        ) {
            self::$_Money['ru'] = array();
            self::$_Money[$lang][$currency]['null'] = 'Ноль гривен';
            self::$_Money[$lang][$currency]['nullcop'] = 'ноль копеек.';
            self::$_Money[$lang][$currency]['upperdiff'] = 0;
            $cop[0] = 'копеек.';
            $cop[1] = 'одна копейка.';
            $cop[2] = 'две копейки.';
            $cop[3] = 'три копейки.';
            $cop[4] = 'четыре копейки.';
            $cop[5] = 'пять копеек.';
            $cop[6] = 'шесть копеек.';
            $cop[7] = 'семь копеек.';
            $cop[8] = 'восемь копеек.';
            $cop[9] = 'девять копеек.';
            $grn[0] = 'гривен';
            $grn[1] = 'один гривен';
            $grn[2] = 'два гривны';
            $grn[3] = 'три гривны';
            $grn[4] = 'четыре гривны';
            $grn[5] = 'пять гривен';
            $grn[6] = 'шесть гривен';
            $grn[7] = 'семь гривен';
            $grn[8] = 'восемь гривен';
            $grn[9] = 'девять гривен';
            self::$_Money[$lang][$currency]['tens'][0] = '';
            self::$_Money[$lang][$currency]['tens'][1] = 'десять';
            self::$_Money[$lang][$currency]['tens'][2] = 'двадцать';
            self::$_Money[$lang][$currency]['tens'][3] = 'тридцать';
            self::$_Money[$lang][$currency]['tens'][4] = 'сорок';
            self::$_Money[$lang][$currency]['tens'][5] = 'пятьдесят';
            self::$_Money[$lang][$currency]['tens'][6] = 'шестьдесят';
            self::$_Money[$lang][$currency]['tens'][7] = 'семьдесят';
            self::$_Money[$lang][$currency]['tens'][8] = 'восемьдесят';
            self::$_Money[$lang][$currency]['tens'][9] = 'девяносто';
            self::$_Money[$lang][$currency]['hundreds'][0] = '';
            self::$_Money[$lang][$currency]['hundreds'][1] = 'сто';
            self::$_Money[$lang][$currency]['hundreds'][2] = 'двести';
            self::$_Money[$lang][$currency]['hundreds'][3] = 'триста';
            self::$_Money[$lang][$currency]['hundreds'][4] = 'четыреста';
            self::$_Money[$lang][$currency]['hundreds'][5] = 'пятьсот';
            self::$_Money[$lang][$currency]['hundreds'][6] = 'шестьсот';
            self::$_Money[$lang][$currency]['hundreds'][7] = 'семьсот';
            self::$_Money[$lang][$currency]['hundreds'][8] = 'восемьсот';
            self::$_Money[$lang][$currency]['hundreds'][9] = 'девятьсот';
            self::$_Money[$lang][$currency]['teens'][0] = 'десять';
            self::$_Money[$lang][$currency]['teens'][1] = 'одиннадцать';
            self::$_Money[$lang][$currency]['teens'][2] = 'двенадцать';
            self::$_Money[$lang][$currency]['teens'][3] = 'тринадцать';
            self::$_Money[$lang][$currency]['teens'][4] = 'четырнадцать';
            self::$_Money[$lang][$currency]['teens'][5] = 'пятнадцать';
            self::$_Money[$lang][$currency]['teens'][6] = 'шестнадцать';
            self::$_Money[$lang][$currency]['teens'][7] = 'семнадцать';
            self::$_Money[$lang][$currency]['teens'][8] = 'восемнадцать';
            self::$_Money[$lang][$currency]['teens'][9] = 'девятнадцать';
            $thous[0] = 'тысяч';
            $thous[1] = 'одна тысяча';
            $thous[2] = 'две тысячи';
            $thous[3] = 'три тысячи';
            $thous[4] = 'четыре тысячи';
            $thous[5] = 'пять тысяч';
            $thous[6] = 'шесть тысяч';
            $thous[7] = 'семь тысяч';
            $thous[8] = 'восемь тысяч';
            $thous[9] = 'девять тысяч';
            $million[0] = 'миллионов';
            $million[1] = 'один миллион';
            $million[2] = 'два миллиона';
            $million[3] = 'три миллиона';
            $million[4] = 'четыре миллиона';
            $million[5] = 'пять миллионов';
            $million[6] = 'шесть миллионов';
            $million[7] = 'семь миллионов';
            $million[8] = 'восемь миллионов';
            $million[9] = 'девять миллионов';
            $milliard[0] = 'миллиард';
            $milliard[1] = 'один миллиард';
            $milliard[2] = 'два миллиарда';
            $milliard[3] = 'три миллиарда';
            $milliard[4] = 'четыре миллиарда';
            $milliard[5] = 'пять миллиардов';
            $milliard[6] = 'шесть миллиардов';
            $milliard[7] = 'семь миллиардов';
            $milliard[8] = 'восемь миллиардов';
            $milliard[9] = 'девять миллиардов';
            self::$_Money[$lang][$currency]['teensnames'][0] = 'гривен';
            self::$_Money[$lang][$currency]['teensnamesCop'][0] = 'копеек.';
            self::$_Money[$lang][$currency]['teensnames'][1] = 'тысяч';
            self::$_Money[$lang][$currency]['teensnames'][2] = 'миллионов';
            self::$_Money[$lang][$currency]['teensnames'][3] = 'миллиардов';
            self::$_Money[$lang][$currency]['nameMoney'][] = $grn;
            self::$_Money[$lang][$currency]['nameMoneyCop'][] = $cop;
            self::$_Money[$lang][$currency]['nameMoney'][] = $thous;
            self::$_Money[$lang][$currency]['nameMoney'][] = $million;
            self::$_Money[$lang][$currency]['nameMoney'][] = $milliard;
            self::$_Money[$lang][$currency]['nameCop'] = 'коп.';
        } elseif (
            ($currency == 'RUB' || $lang == 'ru') &&
            (!isset(self::$_Money[$lang][$currency]) || isset(self::$_Money[$lang][$currency]['nomoney']))
        ) {
            self::$_Money['ru'] = array();
            self::$_Money[$lang][$currency]['null'] = 'Ноль рублей';
            self::$_Money[$lang][$currency]['nullcop'] = 'ноль копеек.';
            self::$_Money[$lang][$currency]['upperdiff'] = 0;
            $cop[0] = 'ноль копеек';
            $cop[1] = 'одна копейка.';
            $cop[2] = 'две копейки.';
            $cop[3] = 'три копейки.';
            $cop[4] = 'четыре копейки.';
            $cop[5] = 'пять копеек.';
            $cop[6] = 'шесть копеек.';
            $cop[7] = 'семь копеек.';
            $cop[8] = 'восемь копеек.';
            $cop[9] = 'девять копеек.';
            $grn[0] = 'рублей';
            $grn[1] = 'один рубль.';
            $grn[2] = 'два рубля.';
            $grn[3] = 'три рубля.';
            $grn[4] = 'четыре рубля.';
            $grn[5] = 'пять рублей.';
            $grn[6] = 'шесть рублей.';
            $grn[7] = 'семь рублей.';
            $grn[8] = 'восемь рублей.';
            $grn[9] = 'девять рублей.';
            self::$_Money[$lang][$currency]['tens'][0] = '';
            self::$_Money[$lang][$currency]['tens'][1] = 'десять';
            self::$_Money[$lang][$currency]['tens'][2] = 'двадцать';
            self::$_Money[$lang][$currency]['tens'][3] = 'тридцать';
            self::$_Money[$lang][$currency]['tens'][4] = 'сорок';
            self::$_Money[$lang][$currency]['tens'][5] = 'пятьдесят';
            self::$_Money[$lang][$currency]['tens'][6] = 'шестьдесят';
            self::$_Money[$lang][$currency]['tens'][7] = 'семьдесят';
            self::$_Money[$lang][$currency]['tens'][8] = 'восемьдесят';
            self::$_Money[$lang][$currency]['tens'][9] = 'девяносто';
            self::$_Money[$lang][$currency]['hundreds'][0] = '';
            self::$_Money[$lang][$currency]['hundreds'][1] = 'сто';
            self::$_Money[$lang][$currency]['hundreds'][2] = 'двести';
            self::$_Money[$lang][$currency]['hundreds'][3] = 'триста';
            self::$_Money[$lang][$currency]['hundreds'][4] = 'четыреста';
            self::$_Money[$lang][$currency]['hundreds'][5] = 'пятьсот';
            self::$_Money[$lang][$currency]['hundreds'][6] = 'шестьсот';
            self::$_Money[$lang][$currency]['hundreds'][7] = 'семьсот';
            self::$_Money[$lang][$currency]['hundreds'][8] = 'восемьсот';
            self::$_Money[$lang][$currency]['hundreds'][9] = 'девятьсот';
            self::$_Money[$lang][$currency]['teens'][0] = 'десять';
            self::$_Money[$lang][$currency]['teens'][1] = 'одиннадцать';
            self::$_Money[$lang][$currency]['teens'][2] = 'двенадцать';
            self::$_Money[$lang][$currency]['teens'][3] = 'тринадцать';
            self::$_Money[$lang][$currency]['teens'][4] = 'четырнадцать';
            self::$_Money[$lang][$currency]['teens'][5] = 'пятнадцать';
            self::$_Money[$lang][$currency]['teens'][6] = 'шестнадцать';
            self::$_Money[$lang][$currency]['teens'][7] = 'семнадцать';
            self::$_Money[$lang][$currency]['teens'][8] = 'восемнадцать';
            self::$_Money[$lang][$currency]['teens'][9] = 'девятнадцать';
            $thous[0] = 'тысяч';
            $thous[1] = 'одна тысяча';
            $thous[2] = 'две тысячи';
            $thous[3] = 'три тысячи';
            $thous[4] = 'четыре тысячи';
            $thous[5] = 'пять тысяч';
            $thous[6] = 'шесть тысяч';
            $thous[7] = 'семь тысяч';
            $thous[8] = 'восемь тысяч';
            $thous[9] = 'девять тысяч';
            $million[0] = 'миллионов';
            $million[1] = 'один миллион';
            $million[2] = 'два миллиона';
            $million[3] = 'три миллиона';
            $million[4] = 'четыре миллиона';
            $million[5] = 'пять миллионов';
            $million[6] = 'шесть миллионов';
            $million[7] = 'семь миллионов';
            $million[8] = 'восемь миллионов';
            $million[9] = 'девять миллионов';
            $milliard[0] = 'миллиард';
            $milliard[1] = 'один миллиард';
            $milliard[2] = 'два миллиарда';
            $milliard[3] = 'три миллиарда';
            $milliard[4] = 'четыре миллиарда';
            $milliard[5] = 'пять миллиардов';
            $milliard[6] = 'шесть миллиардов';
            $milliard[7] = 'семь миллиардов';
            $milliard[8] = 'восемь миллиардов';
            $milliard[9] = 'девять миллиардов';
            self::$_Money[$lang][$currency]['teensnames'][0] = 'рублей.';
            self::$_Money[$lang][$currency]['teensnamesCop'][0] = 'копеек.';
            self::$_Money[$lang][$currency]['teensnames'][1] = 'тысяч';
            self::$_Money[$lang][$currency]['teensnames'][2] = 'миллионов';
            self::$_Money[$lang][$currency]['teensnames'][3] = 'миллиардов';
            self::$_Money[$lang][$currency]['nameMoney'][] = $grn;
            self::$_Money[$lang][$currency]['nameMoneyCop'][] = $cop;
            self::$_Money[$lang][$currency]['nameMoney'][] = $thous;
            self::$_Money[$lang][$currency]['nameMoney'][] = $million;
            self::$_Money[$lang][$currency]['nameMoney'][] = $milliard;
            self::$_Money[$lang][$currency]['nameCop'] = 'коп.';
        } elseif (
            $lang == 'by' &&
            (!isset(self::$_Money[$lang][$currency]) || isset(self::$_Money[$lang][$currency]['nomoney']))
        ) {
            self::$_Money['by'] = array();
            self::$_Money[$lang][$currency]['null'] = 'Ноль бел. рублей';
            self::$_Money[$lang][$currency]['nullcop'] = 'ноль копеек.';
            self::$_Money[$lang][$currency]['upperdiff'] = 0;
            $cop[0] = 'ноль копеек';
            $cop[1] = 'одна копейка.';
            $cop[2] = 'две копейки.';
            $cop[3] = 'три копейки.';
            $cop[4] = 'четыре копейки.';
            $cop[5] = 'пять копеек.';
            $cop[6] = 'шесть копеек.';
            $cop[7] = 'семь копеек.';
            $cop[8] = 'восемь копеек.';
            $cop[9] = 'девять копеек.';
            $grn[0] = 'бел. рублей';
            $grn[1] = 'один бел. рубль';
            $grn[2] = 'два бел. рубля';
            $grn[3] = 'три бел. рубля';
            $grn[4] = 'четыре бел. рубля';
            $grn[5] = 'пять бел. рублей';
            $grn[6] = 'шесть бел. рублей';
            $grn[7] = 'семь бел. рублей';
            $grn[8] = 'восемь бел. рублей';
            $grn[9] = 'девять бел. рублей';
            self::$_Money[$lang][$currency]['tens'][0] = '';
            self::$_Money[$lang][$currency]['tens'][1] = 'десять';
            self::$_Money[$lang][$currency]['tens'][2] = 'двадцать';
            self::$_Money[$lang][$currency]['tens'][3] = 'тридцать';
            self::$_Money[$lang][$currency]['tens'][4] = 'сорок';
            self::$_Money[$lang][$currency]['tens'][5] = 'пятьдесят';
            self::$_Money[$lang][$currency]['tens'][6] = 'шестьдесят';
            self::$_Money[$lang][$currency]['tens'][7] = 'семьдесят';
            self::$_Money[$lang][$currency]['tens'][8] = 'восемьдесят';
            self::$_Money[$lang][$currency]['tens'][9] = 'девяносто';
            self::$_Money[$lang][$currency]['hundreds'][0] = '';
            self::$_Money[$lang][$currency]['hundreds'][1] = 'сто';
            self::$_Money[$lang][$currency]['hundreds'][2] = 'двести';
            self::$_Money[$lang][$currency]['hundreds'][3] = 'триста';
            self::$_Money[$lang][$currency]['hundreds'][4] = 'четыреста';
            self::$_Money[$lang][$currency]['hundreds'][5] = 'пятьсот';
            self::$_Money[$lang][$currency]['hundreds'][6] = 'шестьсот';
            self::$_Money[$lang][$currency]['hundreds'][7] = 'семьсот';
            self::$_Money[$lang][$currency]['hundreds'][8] = 'восемьсот';
            self::$_Money[$lang][$currency]['hundreds'][9] = 'девятьсот';
            self::$_Money[$lang][$currency]['teens'][0] = 'десять';
            self::$_Money[$lang][$currency]['teens'][1] = 'одиннадцать';
            self::$_Money[$lang][$currency]['teens'][2] = 'двенадцать';
            self::$_Money[$lang][$currency]['teens'][3] = 'тринадцать';
            self::$_Money[$lang][$currency]['teens'][4] = 'четырнадцать';
            self::$_Money[$lang][$currency]['teens'][5] = 'пятнадцать';
            self::$_Money[$lang][$currency]['teens'][6] = 'шестнадцать';
            self::$_Money[$lang][$currency]['teens'][7] = 'семнадцать';
            self::$_Money[$lang][$currency]['teens'][8] = 'восемнадцать';
            self::$_Money[$lang][$currency]['teens'][9] = 'девятнадцать';
            $thous[0] = 'тысяч';
            $thous[1] = 'одна тысяча';
            $thous[2] = 'две тысячи';
            $thous[3] = 'три тысячи';
            $thous[4] = 'четыре тысячи';
            $thous[5] = 'пять тысяч';
            $thous[6] = 'шесть тысяч';
            $thous[7] = 'семь тысяч';
            $thous[8] = 'восемь тысяч';
            $thous[9] = 'девять тысяч';
            $million[0] = 'миллионов';
            $million[1] = 'один миллион';
            $million[2] = 'два миллиона';
            $million[3] = 'три миллиона';
            $million[4] = 'четыре миллиона';
            $million[5] = 'пять миллионов';
            $million[6] = 'шесть миллионов';
            $million[7] = 'семь миллионов';
            $million[8] = 'восемь миллионов';
            $million[9] = 'девять миллионов';
            $milliard[0] = 'миллиард';
            $milliard[1] = 'один миллиард';
            $milliard[2] = 'два миллиарда';
            $milliard[3] = 'три миллиарда';
            $milliard[4] = 'четыре миллиарда';
            $milliard[5] = 'пять миллиардов';
            $milliard[6] = 'шесть миллиардов';
            $milliard[7] = 'семь миллиардов';
            $milliard[8] = 'восемь миллиардов';
            $milliard[9] = 'девять миллиардов';
            self::$_Money[$lang][$currency]['teensnames'][0] = 'бел. рублей.';
            self::$_Money[$lang][$currency]['teensnamesCop'][0] = 'копеек.';
            self::$_Money[$lang][$currency]['teensnames'][1] = 'тысяч';
            self::$_Money[$lang][$currency]['teensnames'][2] = 'миллионов';
            self::$_Money[$lang][$currency]['teensnames'][3] = 'миллиардов';
            self::$_Money[$lang][$currency]['nameMoney'][] = $grn;
            self::$_Money[$lang][$currency]['nameMoneyCop'][] = $cop;
            self::$_Money[$lang][$currency]['nameMoney'][] = $thous;
            self::$_Money[$lang][$currency]['nameMoney'][] = $million;
            self::$_Money[$lang][$currency]['nameMoney'][] = $milliard;
            self::$_Money[$lang][$currency]['nameCop'] = 'коп.';
        }
    }

    /**
     *  Получает ссылку и если нет впереди "http://" дописывает
     *  или если есть "hTtP://" но в верхнем регистре переводит в нижний
     *  на выходе получается "http://..."
     *
     * @param $url
     * 
     * @static
     * 
     * @return mixed|string
     */
    public static function NormalizeURL($url) {
        if (empty($url))
            return $url;

        // если нет протокала - добавить
        if (!strstr($url, "://"))
            $url = "http://" . $url;
        // заменить протокол на нижний регистр: hTtP -> http
        $url = preg_replace("~^[a-z]+~ie", "strtolower('\\0')", $url);

        return $url;
    }

    private static $_Money = array();

}