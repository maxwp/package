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
 * Тест классов StringUtils_FormatterPhone*
 *
 * @author Max
 * @copyright WebProduction
 * @package StringUtils
 * @subpackage FormatterAddress
 */
class Test_StringUtils_FormatterAddress extends TestKit_TestClass {

    public function testFormatterAddress1() {
        $address = new StringUtils_FormatterAddressUACN('Чернигов, Украина, пр. Победы, 95, офис 404');
        $this->assertEquals('Чернигов, Украина, проспект Победы, 95, офис 404', $address->formatFull());

        $address = new StringUtils_FormatterAddressUACN('пр. Победы, 95, офис 404');
        $this->assertEquals('Украина, г. Чернигов, проспект Победы, 95, офис 404', $address->formatFull());
        $this->assertEquals('пр. Победы, 95, оф. 404', $address->formatShort());

    }


}