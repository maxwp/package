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
 * Полный тест класса DateTime_Object
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package DateTime
 */
class Test_DateTime_Object extends TestKit_TestClass {

    public function setUp() {

    }

    public function testCheckNumberFormat() {
        // проверяем now
        $date = DateTime_Object::FromString('now');
        $this->assertTrue(!substr_count($date->__toString(), '1971'));

        // проверяем дифф двух now
        $this->assertTrue(DateTime_Differ::DiffMinute('now', 'now') == 0);
    }

    public function tearDown() {

    }

}