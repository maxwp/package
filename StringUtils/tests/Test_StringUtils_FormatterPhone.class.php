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
 * @author FreeFox
 * @author Max
 * @copyright WebProduction
 * @package StringUtils
 * @subpackage FormatterPhone
 */
class Test_StringUtils_FormatterPhone extends TestKit_TestClass {

    public function testFormatterClear1() {
        $phone = new StringUtils_FormatterPhoneClear('02');
        $this->assertEquals('02', $phone->format());

        $phone = new StringUtils_FormatterPhoneClear('102');
        $this->assertEquals('102', $phone->format());

        $phone = new StringUtils_FormatterPhoneClear('5323');
        $this->assertEquals('5323', $phone->format());

        $phone = new StringUtils_FormatterPhoneClear('75323');
        $this->assertEquals('75323', $phone->format());

        $phone = new StringUtils_FormatterPhoneClear('275323');
        $this->assertEquals('275323', $phone->format());

        $phone = new StringUtils_FormatterPhoneClear('7275323');
        $this->assertEquals('7275323', $phone->format());

        $phone = new StringUtils_FormatterPhoneClear('0637275323');
        $this->assertEquals('0637275323', $phone->format());

        $phone = new StringUtils_FormatterPhoneClear('80637275323');
        $this->assertEquals('80637275323', $phone->format());

        $phone = new StringUtils_FormatterPhoneClear('380637275323');
        $this->assertEquals('380637275323', $phone->format());

        $phone = new StringUtils_FormatterPhoneClear('+380637275323');
        $this->assertEquals('380637275323', $phone->format());

        $phone = new StringUtils_FormatterPhoneClear('+38м(063)у727у-53ор-23');
        $this->assertEquals('380637275323', $phone->format());
    }

    public function testFormatterPhone() {
        $phone = new StringUtils_FormatterPhoneDefault('02');
        $this->assertEquals('02', $phone->format());

        $phone = new StringUtils_FormatterPhoneDefault('102');
        $this->assertEquals('102', $phone->format());

        $phone = new StringUtils_FormatterPhoneDefault('5323');
        $this->assertEquals('53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneDefault('75323');
        $this->assertEquals('7-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneDefault('275323');
        $this->assertEquals('27-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneDefault('7275323');
        $this->assertEquals('727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneDefault('0637275323');
        $this->assertEquals('(063) 727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneDefault('80637275323');
        $this->assertEquals('8 (063) 727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneDefault('380637275323');
        $this->assertEquals('+38 (063) 727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneDefault('+380637275323');
        $this->assertEquals('+38 (063) 727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneDefault('+38м(063)у727у-53ор-23');
        $this->assertEquals('+38 (063) 727-53-23', $phone->format());
    }

    public function testFormatterPhoneDefaultStatic() {
        $this->assertEquals('+38 (063) 727-53-23', StringUtils_FormatterPhoneDefault::Create('+38м(063)у727у-53ор-23')->format());
    }

    public function testFormatterPhoneException() {
        try {
            $p = new StringUtils_FormatterPhoneDefault(1);
            $this->fail();
        } catch (StringUtils_Exception $e) {

        }

        try {
            $p = new StringUtils_FormatterPhoneDefault('+38050447953-');
            $this->fail();
        } catch (StringUtils_Exception $e) {

        }

        // номер на 8 цифр для некоторых стран может считаться корректным, например,
        // в старом формате - начало на 8050... - получиться ровно 11 цифр
        try {
            $p = new StringUtils_FormatterPhoneDefault('38050447953-');
        } catch (StringUtils_Exception $e1) {
            $this->fail();
        }
    }

    /**
     * Тест форматтера черниговских номеров
     *
     * @author Max
     */
    public function testFormatterPhoneFormatterCNUA1() {
        $phone = new StringUtils_FormatterPhoneUACN('0637275323');
        $this->assertEquals('(063) 727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('80637275323');
        $this->assertEquals('8 (063) 727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('+380637275323');
        $this->assertEquals('+38 (063) 727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('+380462614261');
        $this->assertEquals('+38 (0462) 61-42-61', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('+380462214261');
        $this->assertEquals('+38 (04622) 1-42-61', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('+380463614261');
        $this->assertEquals('+38 (046) 361-42-61', $phone->format());
    }

    /**
     * Тест форматтера черниговских номеров в full-mode
     *
     * @author Max
     */
    public function testFormatterPhoneFormatterCNUAModeFull1() {
        $phone = new StringUtils_FormatterPhoneUACN('0637275323');
        $phone->setModeFull();
        $this->assertEquals('+38 (063) 727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('80637275323');
        $phone->setModeFull();
        $this->assertEquals('+38 (063) 727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('+380637275323');
        $phone->setModeFull();
        $this->assertEquals('+38 (063) 727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('+380462614261');
        $phone->setModeFull();
        $this->assertEquals('+38 (0462) 61-42-61', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('+380463614261');
        $phone->setModeFull();
        $this->assertEquals('+38 (046) 361-42-61', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('614261');
        $phone->setModeFull();
        $this->assertEquals('+38 (0462) 61-42-61', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('14261');
        $phone->setModeFull();
        $this->assertEquals('+38 (04622) 1-42-61', $phone->format());
    }

    /**
     * Тест форматтера черниговских номеров в full-mode
     *
     * @author Max
     */
    public function testFormatterPhoneFormatterCNUAModeFull2() {
        $phone = new StringUtils_FormatterPhoneUACN('0637275323');
        $this->assertEquals('+38 (063) 727-53-23', $phone->formatFull());

        $phone = new StringUtils_FormatterPhoneUACN('80637275323');
        $this->assertEquals('+38 (063) 727-53-23', $phone->formatFull());

        $phone = new StringUtils_FormatterPhoneUACN('+380637275323');
        $this->assertEquals('+38 (063) 727-53-23', $phone->formatFull());

        $phone = new StringUtils_FormatterPhoneUACN('+380462614261');
        $this->assertEquals('+38 (0462) 61-42-61', $phone->formatFull());

        $phone = new StringUtils_FormatterPhoneUACN('+380463614261');
        $this->assertEquals('+38 (046) 361-42-61', $phone->formatFull());

        $phone = new StringUtils_FormatterPhoneUACN('614261');
        $this->assertEquals('+38 (0462) 61-42-61', $phone->formatFull());

        $phone = new StringUtils_FormatterPhoneUACN('14261');
        $this->assertEquals('+38 (04622) 1-42-61', $phone->formatFull());
    }

    /**
     * Тест форматтера черниговских номеров в short-mode
     *
     * @author Max
     */
    public function testFormatterPhoneFormatterCNUAShort1() {
        $phone = new StringUtils_FormatterPhoneUACN('0637275323');
        $phone->setModeShort();
        $this->assertEquals('(063) 727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('80637275323');
        $phone->setModeShort();
        $this->assertEquals('(063) 727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('+380637275323');
        $phone->setModeShort();
        $this->assertEquals('(063) 727-53-23', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('+380462614261');
        $phone->setModeShort();
        $this->assertEquals('61-42-61', $phone->format());

        // это не черниговский номер!
        $phone = new StringUtils_FormatterPhoneUACN('+380463614261');
        $phone->setModeShort();
        $this->assertEquals('(046) 361-42-61', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('614261');
        $phone->setModeShort();
        $this->assertEquals('61-42-61', $phone->format());

        $phone = new StringUtils_FormatterPhoneUACN('14261');
        $phone->setModeShort();
        $this->assertEquals('1-42-61', $phone->format());
    }

    /**
     * Тест форматтера черниговских номеров в short-mode
     *
     * @author Max
     */
    public function testFormatterPhoneFormatterCNUAShort2() {
        $phone = new StringUtils_FormatterPhoneUACN('0637275323');
        $this->assertEquals('(063) 727-53-23', $phone->formatShort());

        $phone = new StringUtils_FormatterPhoneUACN('80637275323');
        $this->assertEquals('(063) 727-53-23', $phone->formatShort());

        $phone = new StringUtils_FormatterPhoneUACN('+380637275323');
        $this->assertEquals('(063) 727-53-23', $phone->formatShort());

        $phone = new StringUtils_FormatterPhoneUACN('+380462614261');
        $this->assertEquals('61-42-61', $phone->formatShort());

        // это не черниговский номер!
        $phone = new StringUtils_FormatterPhoneUACN('+380463614261');
        $this->assertEquals('(046) 361-42-61', $phone->formatShort());

        $phone = new StringUtils_FormatterPhoneUACN('614261');
        $this->assertEquals('61-42-61', $phone->formatShort());

        $phone = new StringUtils_FormatterPhoneUACN('14261');
        $this->assertEquals('1-42-61', $phone->formatShort());
    }

}