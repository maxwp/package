<?php
/**
 * Форматирование цены
 *
 * @package StringUtils
 */

class StringUtils_FormatterPrice {
    /**
     * @param $price
     * @return mixed|string
     */
    public static function format($price) {
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