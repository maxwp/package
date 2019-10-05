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
 * Полный тест класса DateTime_Formatter
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package DateTime
 */
class Test_DateTime_Formatter extends TestKit_TestClass {

    public function setUp() {

    }

    public function testCheckNumberFormat() {
        $this->assertEquals('10.10.2010, 07:04', DateTime_Formatter::DateTimePhonetic('2010-10-10 07:04'));
        $this->assertEquals('09.10.2010, 07:04', DateTime_Formatter::DateTimePhonetic('2010-10-09 07:04'));
        $this->assertEquals('13.10.2010, 07:04', DateTime_Formatter::DateTimePhonetic('2010-10-13 07:04'));

        // @todo: у нас проблемы - все зависит от текущего времени
        // как написать тест?!!
        /*$this->assertEquals('10.10.2010, воскресенье, 07:04', DateTime_Formatter::DateTimePhonetic('2010-10-10 07:04'));
        $this->assertEquals('09.10.2010, суббота, 07:04', DateTime_Formatter::DateTimePhonetic('2010-10-09 07:04'));
        $this->assertEquals('13.10.2010, среда, 07:04', DateTime_Formatter::DateTimePhonetic('2010-10-13 07:04'));*/
    }

    public function tearDown() {

    }

}