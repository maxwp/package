<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2010  WebProduction <webproduction.com.ua>
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Фонетическое форматирование дат
 *
 * @author Max
 * @copyright WebProduction
 * @package DateTime
 */
class DateTime_ClassFormatPhonetic implements DateTime_IClassFormat {

    public function __construct($language = 'ru') {
        $this->_language = $language;
    }

    public function setFormat($format) {
        $this->_format = $format;
    }

    public function setDate($timestamp) {
        $this->_timestamp = $timestamp;
    }

    private $_mode = 'datetime';

    /**
     * Установить режим форматирования дат.
     *
     * datetime
     * date
     * time
     *
     * @param string $mode
     */
    public function setFormatMode($mode) {
        $this->_mode = $mode;
    }

    /**
     * Получить режим форматирования дат
     *
     * @return string
     */
    public function getFormatMode() {
        return $this->_mode;
    }

    public function __toString() {
        $mode = $this->_mode;

        $n = DateTime_Object::DiffDate('s', DateTime_Object::NOW(), DateTime_Object::FromTimeStamp($this->_timestamp));
        if ($n < 0) {
            // дата в будущем
            if ($mode == 'datetime') {
                return date('H:i d.m.Y', $this->_timestamp);
            } elseif ($mode == 'date') {
                return date('d.m.Y', $this->_timestamp);
            } elseif ($mode == 'time') {
                return date('H:i', $this->_timestamp);
            }
        } elseif ($n <= 60) {
            return DateTime_Translate::Get()->getTranslate('minute_ago');
        }

        $n = DateTime_Object::DiffDate('n', DateTime_Object::NOW(), DateTime_Object::FromTimeStamp($this->_timestamp));
        if ($n < 15) {
            return "$n ".DateTime_Translate::Get()->getTranslate('min_ago');
        }

        if (date('Y-m-d') == date('Y-m-d', $this->_timestamp)) {
            if ($mode == 'datetime') {
                return DateTime_Translate::Get()->getTranslate('today').", ".date('H:i', $this->_timestamp);
            } elseif ($mode == 'date') {
                return DateTime_Translate::Get()->getTranslate('today');
            } elseif ($mode == 'time') {
                return date('H:i', $this->_timestamp);
            }
        }

        if (date('Y-m-d', $this->_timestamp) == DateTime_Object::NOW()->addDay(-1)->setFormat('Y-m-d')->__toString()) {
            if ($mode == 'datetime') {
                return DateTime_Translate::Get()->getTranslate('yesterday').", ".date('H:i', $this->_timestamp);
            } elseif ($mode == 'date') {
                return DateTime_Translate::Get()->getTranslate('yesterday');
            } elseif ($mode == 'time') {
                return date('H:i', $this->_timestamp);
            }
        }

        $n = DateTime_Object::DiffDate('d', DateTime_Object::NOW(), DateTime_Object::FromTimeStamp($this->_timestamp));
        if ($n <= 7) {
            if ($this->_language == 'ru') {
                $day = date('N', $this->_timestamp);
                if ($day == 1) {
                    $day = DateTime_Translate::Get()->getTranslate('monday_small');
                } elseif ($day == 2) {
                    $day = DateTime_Translate::Get()->getTranslate('tuesday_small');
                } elseif ($day == 3) {
                    $day = DateTime_Translate::Get()->getTranslate('wednesday_small');
                } elseif ($day == 4) {
                    $day = DateTime_Translate::Get()->getTranslate('thursday_small');
                } elseif ($day == 5) {
                    $day = DateTime_Translate::Get()->getTranslate('friday_small');
                } elseif ($day == 6) {
                    $day = DateTime_Translate::Get()->getTranslate('saturday_small');
                } elseif ($day == 7) { // sunday
                    $day = DateTime_Translate::Get()->getTranslate('sunday_small');
                } else {
                    $day = 'Ахтунг!';
                }
            } else {
                $day = date('l', $this->_timestamp);
            }

            if ($mode == 'datetime') {
                return date('d.m.Y, ', $this->_timestamp).$day.date(', H:i', $this->_timestamp);
            } elseif ($mode == 'date') {
                return date('d.m.Y, ', $this->_timestamp).$day;
            } elseif ($mode == 'time') {
                return date('H:i', $this->_timestamp);
            }
        }

        if ($mode == 'datetime') {
            return date('d.m.Y, H:i', $this->_timestamp);
        } elseif ($mode == 'date') {
            return date('d.m.Y', $this->_timestamp);
        } elseif ($mode == 'time') {
            return date('H:i', $this->_timestamp);
        }
        return date('d.m.Y H:i', $this->_timestamp);
    }

    private $_format;

    private $_timestamp;

    private $_language;

}