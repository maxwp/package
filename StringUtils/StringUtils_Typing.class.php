<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Типизирование строк
 */
class StringUtils_Typing {

    /**
     * Привести строку к необходимому типу
     *
     * @param mixed $value
     * @param string $typing
     *
     * @return mixed
     */
    public static function TypeString($value, $typing) {
        if ($typing == 'string') {
            $value = (string) $value;
        }
        if ($typing == 'int') {
            $value = (int) $value;
        }
        if ($typing == 'bool') {
            if ($value == 'true') {
                $value = true;
            } elseif ($value == 'false') {
                $value = false;
            } else {
                $value = (bool) $value;
            }
        }
        if ($typing == 'array') {
            if (!$value) {
                $value = array();
            } elseif (!is_array($value)) {
                $value = (array) $value;
            }
        }
        if ($typing == 'float') {
            $value = preg_replace("/[^0-9\.\,]/ius", '', $value);
            $value = str_replace(',', '.', $value);
            $value = (float) $value;
        }
        if ($typing == 'date') {
            $x = strtotime($value);
            if (!$x || $x < 0) {
                $value = '';
            } else {
                $value = date('Y-m-d', $x);
            }
        }
        if ($typing == 'datetime') {
            $x = strtotime($value);
            if (!$x || $x < 0) {
                $value = '';
            } else {
                $value = date('Y-m-d H:i:s', $x);
            }
        }
        if ($typing == 'file') {
            if (isset($value['tmp_name'])) {
                $value = $value['tmp_name'];
            } else {
                $value = false;
            }
        }
        return $value;
    }

}