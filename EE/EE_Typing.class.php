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
        switch (strtolower($typing)) {
            case self::TYPE_STRING:
                return (string) $value;
            case self::TYPE_INT:
                return (int) $value;
            case self::TYPE_BOOL:
                if ($value == 'true') {
                    return true;
                } elseif ($value == 'false') {
                    return false;
                } else {
                    return (bool) $value;
                }
            case self::TYPE_ARRAY:
                if (!$value) {
                    return [];
                } elseif (!is_array($value)) {
                    return (array) $value;
                } else {
                    return $value;
                }
            case self::TYPE_FLOAT:
                // @todo no preg_match
                $value = preg_replace("/[^0-9\.\,\-]/ius", '', $value);
                $value = str_replace(',', '.', $value);
                return (float) $value;
            case self::TYPE_DATE:
                $x = strtotime($value);
                if (!$x || $x < 0) {
                    return '';
                } else {
                    return date('Y-m-d', $x);
                }
            case self::TYPE_DATETIME:
                // @todo тут полная хуйня нужен timestamp
                $x = strtotime($value);
                if (!$x || $x < 0) {
                    return '';
                } else {
                    return date('Y-m-d H:i:s', $x);
                }
            default:
                throw new EE_Exception('Unknown typing');
        }
    }

    public const TYPE_STRING = 'string';
    public const TYPE_INT = 'int';
    public const TYPE_BOOL = 'bool';
    public const TYPE_FLOAT = 'float'; // @todo rename to double
    public const TYPE_DATE = 'date';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_ARRAY = 'array';

}