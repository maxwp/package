<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2011 WebProduction <webproduction.com.ua>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU LESSER GENERAL PUBLIC LICENSE
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

/**
 * Класс для работы с датами, форматирование, вычисления.
 *
 * Design-patter: Value Object
 *
 * @copyright WebProduction
 * @author DFox
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @package DateTime
 */
class DateTime_Object {

    // @todo strtotime_ex

    public function __construct($timestamp) {
        $this->_timestamp = $timestamp;
        $this->_classformat = new DateTime_ClassFormatDefault();
    }

    /**
     * @param string $format
     * @return DateTime_Object
     */
    public function setFormat($format) {
        $this->_format = $format;
        return $this;
    }

    /**
     * @param DateTime_IClassFormat $classformat
     * @return DateTime_Object
     */
    public function setClassFormat(DateTime_IClassFormat $classformat) {
        $this->_classformat = $classformat;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString() {
        $this->_classformat->setDate($this->_timestamp);
        $this->_classformat->setFormat($this->_format);
        return $this->_classformat->__toString();
    }

    public function setDate($date) {
        $this->_timestamp = strtotime($date);
    }

    /**
     * Добавить месяц
     *
     * @param int $number_of_month
     * @return DateTime_Object
     */
    public function addMonth($number_of_month) {
        return $this->addSomething('mon', $number_of_month);
    }

    /**
     * Добавить день
     *
     * @param int $number_of_day
     * @return DateTime_Object
     */
    public function addDay($number_of_day) {
        return $this->addSomething('mday', $number_of_day);
    }

    /**
     * Добавить минут
     *
     * @param int $minutes
     * @return DateTime_Object
     */
    public function addMinute($minutes) {
        return $this->addSomething('minutes', $minutes);
    }

    /**
     * Добавить часов
     *
     * @param int $hours
     * @return DateTime_Object
     */
    public function addHour($hours) {
        return $this->addSomething('hours', $hours);
    }

    /**
     * Добавить секунд
     *
     * @param int $seconds
     * @return DateTime_Object
     */
    public function addSecond($seconds) {
        return $this->addSomething('seconds', $seconds);
    }

    /**
     * Добавить год
     *
     * @param int $number_of_year
     * @return DateTime_Object
     */
    public function addYear($number_of_year) {
        return $this->addSomething('year', $number_of_year);
    }

    /**
     * Дописать к текущей дате что-либо
     *
     * @param string $what "seconds" Секунды От 0 до 59
     *                      "minutes" Минуты От 0 до 59
     *                      "hours" Часы От 0 до 23
     *                      "mday" Порядковый номер дня месяца От 1 до 31
     *                      "wday" Порядковый номер дня  От 0 (воскресенье) до 6 (суббота)
     *                      "mon" Порядковый номер месяца От 1 до 12
     *                      "year" Порядковый номер года, 4 цифры Примеры: 1999, 2003
     *                      "yday" Порядковый номер дня в году (нумерация с 0) От 0 до 365
     *                      "weekday" Полное наименование дня недели От Sunday до Saturday
     *                      "month" Полное наименование месяца, например January или March от January до December
     * @param int $count
     * @return DateTime_Object
     */
    public function addSomething($what = 'mday', $count) {
        $array = getdate($this->_timestamp);
        $array[$what] += $count;
        $this->_timestamp = mktime($array['hours'],$array['minutes'],$array['seconds'], $array['mon'],$array['mday'],$array['year']);
        return $this;
    }

    /**
     * Привести дату в штамп времени
     *
     * @return int
     */
    public function getTimestamp() {
        return $this->_timestamp;
    }

    public function preview($format = 'Y-m-d H:i:s') {
        return date($format, $this->_timestamp);
    }

    /**
     * @param mixed $datetime
     * @return DateTime_Object
     */
    public static function Create($datetime = false) {
        if (!$datetime) {
            return new DateTime_Object(time());
        } elseif (is_int($datetime) || is_float($datetime)) {
            return new DateTime_Object($datetime);
        } else {
            return new DateTime_Object(strtotime($datetime));
        }
    }

    /**
     * Создать объект на основе текущего времени
     *
     * @return DateTime_Object
     */
    public static function Now() {
        return new DateTime_Object(time());
    }

    /**
     * Создать объект на основе unix timestamp
     *
     * @return DateTime_Object
     */
    public static function FromTimeStamp($timestamp) {
        return new DateTime_Object($timestamp);
    }

    /**
     * Создать объект на основе времени, заданного строкой
     *
     * @return DateTime_Object
     */
    public static function FromString($strtime) {
        return new DateTime_Object(strtotime($strtime));
    }

    private $_timestamp;

    private $_format = 'Y-m-d H:i:s';

    /**
     * @var DateTime_IClassFormat
     */
    private $_classformat;

}