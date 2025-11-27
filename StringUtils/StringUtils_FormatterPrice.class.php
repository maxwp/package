<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class StringUtils_FormatterPrice {

    public static function FormatPricePrecisionPowered($value, $precision, $precision_power10) {
        // важно: отрицательные оно не хавает
        // важно: без round не сработает $value = 0.059999999999999; $p = 3;, а во float может быть такая хуйня

        //$n = (int) ($value * $precision_power10);

        // ручное округление вместо round() дало -20ns
        //$n = (int) round($value * $precision_power10);
        $x = $value * $precision_power10;
        if ($x >= 0) {
            $n = (int) ($x + 0.5);
        } else {
            $n = (int) ($x - 0.5);
        }

        if ($precision == 0) {
            // upd: даже форматировать в string не надо, это -2 ns
            return (string) $n;
        }

        // тут только сравнение по int, без strlen
        if ($n < $precision_power10) {
            return '0.' . str_pad((string) $n, $precision, '0', STR_PAD_LEFT);
        }

        // сюда попадаем только когда len > precision → тут уже нужен strlen
        $s = (string) $n;
        return substr_replace($s, '.', strlen($s) - $precision, 0);
    }

    public static function FormatPricePrecision($value, $precision) {
        // важно: не лепить в один метод, это даст +10 ns/call, я проверял
        // важно: отрицательные оно не хавает
        // важно: без round не сработает $value = 0.059999999999999; $p = 3;, а во float может быть такая хуйня

        // ручное округление вместо round() дало -20ns
        //$n = (int) round($value * $precision_power10);
        $pw = (10 ** $precision);
        $x = $value * $pw;
        if ($x >= 0) {
            $n = (int) ($x + 0.5);
        } else {
            $n = (int) ($x - 0.5);
        }

        if ($precision == 0) {
            // upd: даже форматировать в string не надо, это -2 ns
            return (string) $n;
        }

        // тут только сравнение по int, без strlen
        if ($n < $pw) {
            return '0.' . str_pad((string) $n, $precision, '0', STR_PAD_LEFT);
        }

        // сюда попадаем только когда len > precision → тут уже нужен strlen
        $s = (string) $n;
        return substr_replace($s, '.', strlen($s) - $precision, 0);
    }

    /**
     * @deprecated old fuck
     * @param $price
     * @return mixed|string
     */
    public static function Format($price) {
        if (is_bool($price)) {
            return 0;
        }
        $price = preg_replace('/[^\d\.\,]*/ius', '', $price);
        $price = str_replace(',', '.', (string)$price);
        $array = explode('.', $price);
        $lastKey = (count($array)-1);

        // после последней точки было 2 цифры
        if ( strlen($array[$lastKey]) == 2 && (strlen($price) > 2) ) {
            // 2 последних цифры отделяем точкой
            $array[$lastKey] = '.'.$array[$lastKey];
            if ($lastKey === 1 && !$array[$lastKey-1]) {
                $array[$lastKey] = '0'.$array[$lastKey];
            }
        } elseif (strlen($array[$lastKey]) == 1 && (strlen($price) > 1)) {
            // после последней точки была 1 цифра
            $array[$lastKey] = '.'.$array[$lastKey];
            if ($lastKey === 1 && !$array[$lastKey-1]) {
                $array[$lastKey] = '0'.$array[$lastKey];
            }
        }

        $price = implode($array);
        //$price = number_format($price, 2);

        return $price;
    }

}