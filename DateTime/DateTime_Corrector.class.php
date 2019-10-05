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
 * Корректор дат
 *
 * @copyright WebProduction
 * @author DFox
 * @author Max
 * @package DateTime
 */
class DateTime_Corrector {

    public static function CorrectDate($string, $format = 'Y-m-d') {
        return DateTime_Object::FromString($string.'')->setFormat($format)->__toString();
    }

    public static function CorrectDateTime($string) {
        return DateTime_Object::FromString($string.'')->setFormat('Y-m-d H:i:s')->__toString();
    }

    public static function CorrectTime($string) {
        return DateTime_Object::FromString($string.'')->setFormat('H:i:s')->__toString();
    }

}