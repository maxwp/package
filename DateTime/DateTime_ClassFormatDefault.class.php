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
 * Обычное (дефолтное) форматирование дат
 *
 * @author DFox
 * @copyright WebProduction
 * @package DateTime
 */
class DateTime_ClassFormatDefault implements DateTime_IClassFormat {

    private $_format;

    private $_timestamp;

    public function setFormat($format) {
        $this->_format = $format;
    }

    public function setDate($timestamp) {
        $this->_timestamp = $timestamp;
    }

    public function __toString() {
        return date($this->_format, $this->_timestamp);
    }

}