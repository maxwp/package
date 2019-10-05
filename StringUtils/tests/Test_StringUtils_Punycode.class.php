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
 * Тест класса StringUtils_Punycode
 *
 * @author Max
 * @copyright WebProduction
 * @package StringUtils
 * @subpackage Punycode
 */
class Test_StringUtils_Punycode extends TestKit_TestClass {

    public function setUp() {

    }

    public function testPunycode1() {
        $ps = new StringUtils_Punycode();
        $this->assertEquals('webproduction.com.ua', $ps->encode('webproduction.com.ua'));
        $this->assertEquals('xn--80ahtmbfh2e.com.ua', $ps->encode('продакшн.com.ua'));
    }

    public function testIDNPhishing1() {
        $this->assertTrue(StringUtils_Punycode::DetectIDNPhishing('макс.com.ua'));
        $this->assertTrue(StringUtils_Punycode::DetectIDNPhishing('авто.com.ua'));
        $this->assertFalse(StringUtils_Punycode::DetectIDNPhishing('max.com.ua'));
        $this->assertFalse(StringUtils_Punycode::DetectIDNPhishing('продакшн.com.ua'));
    }

    public function tearDown() {

    }

}