<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Вычислятор разниц между датами
 */
class DateTime_Differ {

    public const INTERVAL_YEAR = 'y';
    public const INTERVAL_MONTH = 'm';
    public const INTERVAL_WEEK = 'w';
    public const INTERVAL_DAY = 'd';
    public const INTERVAL_HOUR = 'h';
    public const INTERVAL_MINUTE = 'n';
    public const INTERVAL_SECOND = 's';

    public static function DiffDate($interval, $date1, $date2) {
        if (!($date1 instanceof DateTime_Object)) {
            $date1 = DateTime_Object::FromString($date1);
        }
        if (!($date2 instanceof DateTime_Object)) {
            $date2 = DateTime_Object::FromString($date2);
        }

        $difference = $date1->getTimestamp() - $date2->getTimestamp();

        switch ($interval) {
            case self::INTERVAL_YEAR:
                $arr1 = getdate($date1->getTimestamp());
                $arr2 = getdate($date2->getTimestamp());
                $retval = ($arr1['year'] - $arr2['year']);
                if ($arr1['yday'] < $arr2['yday']) {
                    $retval -= 1;
                }
                return $retval;
            case self::INTERVAL_MONTH:
                $arr1 = getdate($date1->getTimestamp());
                $arr2 = getdate($date2->getTimestamp());
                return ($arr1['year']*12+$arr1['mon'] - $arr2['year']*12-$arr2['mon']);
            case self::INTERVAL_WEEK:
                return (int)($difference/604800);
            case self::INTERVAL_DAY:
                return (int)($difference/86400);
            case self::INTERVAL_HOUR:
                return (int)($difference/3600);
            case self::INTERVAL_MINUTE:
                return (int)($difference/60);
            case self::INTERVAL_SECOND:
                return $difference;
            default:
                throw new Exception("Unknown interval: ".$interval);
        }
    }

    /**
     * Вычислить разницу двух дат в днях
     *
     * @param mixed $date1
     * @param mixed $date2
     * @return int
     */
    public static function DiffDay($date1, $date2) {
        return self::DiffDate(self::INTERVAL_DAY, $date1, $date2);
    }

    /**
     * Вычислить разницу двух дат в месяцах
     *
     * @param mixed $date1
     * @param mixed $date2
     * @return int
     */
    public static function DiffMonth($date1, $date2, $returnInt = true) {
        if ($returnInt) {
            return self::DiffDate(self::INTERVAL_MONTH, $date1, $date2);
        } else {
            $from = $date1;
            $to = $date2;

            $result = 0;

            $d = DateTime_Object::FromString($from)->setFormat('Y-m-d');
            $to = DateTime_Object::FromString($to)->setFormat('Y-m-d')->__toString();

            $fromMonth = DateTime_Object::FromString($from)->setFormat('Y-m')->__toString();
            $toMonth = DateTime_Object::FromString($to)->setFormat('Y-m')->__toString();

            if ($fromMonth == $toMonth) {
                // даты в одном месяце, считаем по дням
                $diff = DateTime_Differ::DiffDay($to, $from) + 1;
                $t = DateTime_Object::FromString($from)->setFormat('t')->__toString();

                $result = round($diff / $t, 2);
            } else {
                // даты в разных месяцах, идем по месяцам, затем по дням

                while ($d->__toString() < $to) {
                    $result ++;

                    $d->addMonth(+1);
                }

                if ($d->__toString() > $to) {
                    // перепрыгнули
                    $diff = DateTime_Differ::DiffDay($d, $to) - 1;
                    $t = DateTime_Object::FromString($to)->setFormat('t')->__toString();

                    $result -= round($diff / $t, 2);

                } else {
                    // недопрыгнули
                    $diff = DateTime_Differ::DiffDay($to, $d) + 1;
                    $t = DateTime_Object::FromString($to)->setFormat('t')->__toString();

                    $result += round($diff / $t, 2);
                }
            }

            return $result;
        }
    }

    /**
     * Вычислить разницу двух дат в минутах
     *
     * @param mixed $date1
     * @param mixed $date2
     * @return int
     */
    public static function DiffMinute($date1, $date2) {
        return self::DiffDate(self::INTERVAL_MINUTE, $date1, $date2);
    }

    /**
     * Вычислить разницу двух дат в секундах
     *
     * @param mixed $date1
     * @param mixed $date2
     * @return int
     */
    public static function DiffSecond($date1, $date2) {
        return self::DiffDate(self::INTERVAL_SECOND, $date1, $date2);
    }

    /**
     * Вычислить разницу двух дат в часах
     *
     * @param mixed $date1
     * @param mixed $date2
     * @return int
     */
    public static function DiffHour($date1, $date2) {
        return self::DiffDate(self::INTERVAL_HOUR, $date1, $date2);
    }

    /**
     * Вычислить разницу двух дат в годах
     *
     * @param mixed $date1
     * @param mixed $date2
     * @return int
     */
    public static function DiffYear($date1, $date2) {
        return self::DiffDate(self::INTERVAL_YEAR, $date1, $date2);
    }

}