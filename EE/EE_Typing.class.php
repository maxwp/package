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
class EE_Typing {

    // @todo ultra fast typing via pack()

    // @todo type mixed by default

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

        if ($typing == self::TYPE_STRING) {
            $value = (string) $value;
        } elseif ($typing == self::TYPE_INT) {
            $value = (int) $value;
        } elseif ($typing == self::TYPE_BOOL) {
            if ($value == 'true') {
                $value = true;
            } elseif ($value == 'false') {
                $value = false;
            } else {
                $value = (bool) $value;
            }
        } elseif ($typing == self::TYPE_ARRAY) {
            if (!$value) {
                $value = array();
            } elseif (!is_array($value)) {
                $value = (array) $value;
            }
        } elseif ($typing == self::TYPE_FLOAT) {
            // @todo no preg_match
            $value = preg_replace("/[^0-9\.\,\-]/ius", '', $value);
            $value = str_replace(',', '.', $value);
            $value = (float) $value;
        } elseif ($typing == self::TYPE_DATE) {
            $x = strtotime($value);
            if (!$x || $x < 0) {
                $value = '';
            } else {
                $value = date('Y-m-d', $x);
            }
        } elseif ($typing == self::TYPE_DATETIME) {
            // @todo тут полная хуйня нужен timestamp
            $x = strtotime($value);
            if (!$x || $x < 0) {
                $value = '';
            } else {
                $value = date('Y-m-d H:i:s', $x);
            }
        }

        return $value;
    }

    public const TYPE_STRING = 'string';
    public const TYPE_INT = 'int';
    public const TYPE_BOOL = 'bool';
    public const TYPE_FLOAT = 'float'; // @todo rename to double
    public const TYPE_DATE = 'date';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_ARRAY = 'array';

}