<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2014 WebProduction <webproduction.ua>
 */

/**
 * Вычислятор разниц между датами
 *
 * @copyright WebProduction
 * @author Max
 * @package DateTime
 */
class DateTime_Differ {

    public static function DiffDate($interval, $date1, $date2) {
        if (!($date1 instanceof DateTime_Object)) {
            $date1 = DateTime_Object::FromString($date1);
        }
        if (!($date2 instanceof DateTime_Object)) {
            $date2 = DateTime_Object::FromString($date2);
        }

        $timedifference = $date1->getTimestamp() - $date2->getTimestamp();
        switch ($interval) {
            case 'y':
                $arr1 = getdate($date1->getTimestamp());
                $arr2 = getdate($date2->getTimestamp());
                $retval = ($arr1['year'] - $arr2['year']);
                if ($arr1['yday'] < $arr2['yday']) {
                    $retval -= 1;
                }
                break;
            case 'm':
                $arr1 = getdate($date1->getTimestamp());
                $arr2 = getdate($date2->getTimestamp());
                $retval = ($arr1['year']*12+$arr1['mon'] - $arr2['year']*12-$arr2['mon']);
                break;
            case 'w':
                $retval = floor($timedifference/604800);
                break;
            case 'd':
                $retval = floor($timedifference/86400);
                break;
            case 'h':
                $retval = floor($timedifference/3600);
                break;
            case 'n':
                $retval = floor($timedifference/60);
                break;
            case 's':
                $retval = $timedifference;
                break;
        }
        if (!$retval) {
            $retval = 0;
        }
        return $retval;
    }

    /**
     * Вычислить разницу двух дат в днях
     *
     * @param mixed $date1
     * @param mixed $date2
     * @return int
     */
    public static function DiffDay($date1, $date2) {
        return self::DiffDate('d', $date1, $date2);
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
            return self::DiffDate('m', $date1, $date2);
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
        return self::DiffDate('n', $date1, $date2);
    }

    /**
     * Вычислить разницу двух дат в секундах
     *
     * @param mixed $date1
     * @param mixed $date2
     * @return int
     */
    public static function DiffSecond($date1, $date2) {
        return self::DiffDate('s', $date1, $date2);
    }

    /**
     * Вычислить разницу двух дат в часах
     *
     * @param mixed $date1
     * @param mixed $date2
     * @return int
     */
    public static function DiffHour($date1, $date2) {
        return self::DiffDate('h', $date1, $date2);
    }

    /**
     * Вычислить разницу двух дат в годах
     *
     * @param mixed $date1
     * @param mixed $date2
     * @return int
     */
    public static function DiffYear($date1, $date2) {
        return self::DiffDate('y', $date1, $date2);
    }

}