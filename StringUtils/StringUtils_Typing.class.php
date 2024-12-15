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
        $typing = strtolower($typing);

        if ($typing == 'string') {
            $value = (string) $value;
        } elseif ($typing == 'int') {
            $value = (int) $value;
        } elseif ($typing == 'bool') {
            if ($value == 'true') {
                $value = true;
            } elseif ($value == 'false') {
                $value = false;
            } else {
                $value = (bool) $value;
            }
        } elseif ($typing == 'array') {
            if (!$value) {
                $value = array();
            } elseif (!is_array($value)) {
                $value = (array) $value;
            }
        } elseif ($typing == 'float') {
            $value = preg_replace("/[^0-9\.\,]/ius", '', $value);
            $value = str_replace(',', '.', $value);
            $value = (float) $value;
        } elseif ($typing == 'date') {
            $x = strtotime($value);
            if (!$x || $x < 0) {
                $value = '';
            } else {
                $value = date('Y-m-d', $x);
            }
        } elseif ($typing == 'datetime') {
            $x = strtotime($value);
            if (!$x || $x < 0) {
                $value = '';
            } else {
                $value = date('Y-m-d H:i:s', $x);
            }
        }

        return $value;
    }

}