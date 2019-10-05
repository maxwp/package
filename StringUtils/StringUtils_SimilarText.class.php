<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2015 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Сравнение строк и текстов
 *
 * @copyright WebProduction
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @package StringUtils
 */
class StringUtils_SimilarText {

    /**
     * Определить схожесть двух текстов (результат - float 0..1)
     * Алгоротм ебановротный ужасный.
     *
     * @author Maxim Miroshnichenko <max@webproduction.com.ua>
     * @param string $originalText
     * @param string $similarText
     * @return float
     */
    public static function CalculateSimilarText($originalText, $similarText, $strict = false) {
        $original = self::_GetMatchWords($originalText, $strict ? 1: false);
        if (!$original) {
            return 0;
        }

        $name = self::_GetMatchWords($similarText, $strict ? 1: false);
        if (!$name) {
            return 0;
        }

        /*if ($similarText == '225/55R18 98 H Continental CrossContact UHP') {
            print_r($original);
            print_r($name);
            exit();
        }*/

        $c = 0;
        $lf = 0;
        foreach ($name as $x) {
            // print "$x:\n";
            foreach ($original as $y) {
                // print "$x - $y\n";
                if (preg_match("/^{$y}/uis", $x) || preg_match("/{$y}$/uis", $x)) {
                    $c++;
                    $lf += mb_strlen($x);
                    // print "0: $x - $y\n";
                    break;
                } elseif (preg_match("/^{$x}/uis", $y) || preg_match("/{$x}$/uis", $y)) {
                    $c++;
                    $lf += mb_strlen($y);
                    // print "1: $x - $y\n";
                    break;
                }
            }
        }
        // print count($original)."\n";
        // print count($name)."\n";
        // $cnt = min(count($original), count($name));
        // if ($cnt <= 0) return 0;
        // var_dump($cnt);
        // var_dump($c);

        // строим суммарный массив всех слов
        $summary = array();
        foreach ($original as $x) {
            $summary[$x] = mb_strlen($x);
        }
        foreach ($name as $x) {
            $summary[$x] = mb_strlen($x);
        }
        $cf = 0;
        foreach ($summary as $x) {
            $cf += $x;
        }

        return $lf / $cf;

        //print "found=$lf\n";
        // print "size=$cf\n\n";

        // с - количество совпадений
        // cnt - количество слов

        /*if ($c >= $cnt) {
        return 1;
        } else {
        return ($c / $cnt);
        }*/
    }

    /**
     * Посчитать процент вхождения текста в текст.
     * Если включен режим strict, то проверять чтобы все цифры входили точно.
     *
     * @param string $originalText
     * @param string $occurrenceText
     * @param bool $strict
     * @return float
     */
    public static function CalculateOccurrenceText($originalText, $occurrenceText, $strict = false) {
        $a = self::_GetMatchWords($originalText, $strict ? 1 : false);
        if (!$a) {
            return 0;
        }

        $b = self::_GetMatchWords($occurrenceText, $strict ? 1 : false);
        if (!$b) {
            return 0;
        }

        // идем по всем словам, вхождение которых надо проверить
        $w1 = 0;
        $w2 = 0;
        foreach ($b as $x) {
            $w2 += mb_strlen($x);

            if (is_numeric($x)) {
                foreach ($a as $y) {
                    if ($x == $y) {
                        $w1 += mb_strlen($x);
                        break;
                    }
                }
            } else {
                foreach ($a as $y) {
                    if (preg_match("/^{$x}/ius", $y)) {
                        $w1 += mb_strlen($x);
                        break;
                    } elseif (preg_match("/^{$y}/ius", $x)) {
                        $w1 += mb_strlen($y);
                        break;
                    }
                }
            }
        }

        $result = round($w1 / $w2, 2);
        /*if ($result == 1) {
            print_r($a);
            print_r($b);
        }*/
        return $result;
    }

    private static function _GetMatchWords($string, $strict = 3) {
        $string = preg_replace("/(\d+)([a-zа-я]+)/ius", '$1 $2', $string);

        preg_match_all('/([\pL\d]+)/uis', $string, $r);

        $a = array();
        foreach ($r[0] as $i => $x) {
            $x = mb_strtolower($x);
            $l = mb_strlen($x);

            if (!is_numeric($x)) {
                if ($l < $strict) {
                    continue;
                } elseif ($l >= 4 && $l <= 6) {
                    if ($strict > 1) {
                        $x = mb_substr($x, 0, $l-1);
                    }
                } elseif ($l > 6) {
                    if ($strict > 1) {
                        $x = mb_substr($x, 0, $l-2);
                    }
                }
            }

            $x = StringUtils_Converter::Transcription($x);
            $a[] = $x;
        }

        return $a;
    }

}