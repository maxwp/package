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
 * Тест класса StringUtils_FormatterURL
 *
 * @author Max
 * @copyright WebProduction
 * @package StringUtils
 * @subpackage FormatterURL
 */
class Test_StringUtils_FormatterPrice extends TestKit_TestClass {

    public function testPrice1() {
        $this->assertEquals('0.1', StringUtils_FormatterPrice::format('0.1'));
        $this->assertEquals('0.1', StringUtils_FormatterPrice::format('0,1'));
        $this->assertEquals('0.15', StringUtils_FormatterPrice::format('0,15'));
        $this->assertEquals('0.15', StringUtils_FormatterPrice::format('0.15'));
        $this->assertEquals('10.1', StringUtils_FormatterPrice::format('10.1'));
        $this->assertEquals('10.10', StringUtils_FormatterPrice::format('10.10'));
        $this->assertEquals('10.1', StringUtils_FormatterPrice::format('10,1'));
        $this->assertEquals('10.10', StringUtils_FormatterPrice::format('10,10'));
        $this->assertEquals('123', StringUtils_FormatterPrice::format('123'));
        $this->assertEquals('123.3', StringUtils_FormatterPrice::format('123,3'));
        $this->assertEquals('123.39', StringUtils_FormatterPrice::format('123.39'));
        $this->assertEquals('123456789', StringUtils_FormatterPrice::format('123456789'));
        $this->assertEquals('123456789.9', StringUtils_FormatterPrice::format('123,456,789.9'));
        $this->assertEquals('123456789.99', StringUtils_FormatterPrice::format('123.456.789,99'));

    }

    public function testPrice2() {
        $this->assertEquals('1452156458.29', StringUtils_FormatterPrice::format('1,452,156.458.29'));
        $this->assertEquals('1452156458.29', StringUtils_FormatterPrice::format('gsdfgsdfg1,452,156.458.29'));
        $this->assertEquals('1452156458.29', StringUtils_FormatterPrice::format('1sdf,sdf45sdfgsdfg2,156.458.29yu'));
        $this->assertEquals('1452156458.29', StringUtils_FormatterPrice::format('1@4,5#2,1`/*-+)((5);6.(458).29'));
        $this->assertEquals('1452156458.29', StringUtils_FormatterPrice::format('1,4.5,2.1,5.6,4.5,8.29'));
        $this->assertEquals('14521564582.9', StringUtils_FormatterPrice::format('1,4.5,2.1,5.6,4.5,8.2,9'));
        $this->assertEquals('145215645829', StringUtils_FormatterPrice::format('1,4.5,2.1,5.6,4.5,8.2,9.'));
    }

    public function testPrice3() {
        $this->assertEquals(0, StringUtils_FormatterPrice::format(false));
        $this->assertEquals(0, StringUtils_FormatterPrice::format(true));
        $this->assertEquals(0, StringUtils_FormatterPrice::format('true'));
        $this->assertEquals('128', StringUtils_FormatterPrice::format('-128-'));
        $this->assertEquals('1965', StringUtils_FormatterPrice::format('+1965+'));
        $this->assertEquals(0, StringUtils_FormatterPrice::format('aaa'));
    }

}