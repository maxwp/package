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
 * Полный тест класса Checker
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package Checker
 */
class Test_Checker extends TestKit_TestClass {

    public function setUp() {

    }

    public function testCheckEmail1() {
        $this->assertTrue(Checker::CheckEmail('max.1993@mail.ru'));
        $this->assertTrue(Checker::CheckEmail('max.m@max.com'));
        $this->assertTrue(Checker::CheckEmail('max--m@mail.ru'));
        $this->assertTrue(Checker::CheckEmail('m_ax@webproduction.com.ua'));
        $this->assertTrue(Checker::CheckEmail('max@webproduction.com.ua'));
        $this->assertTrue(Checker::CheckEmail('max@webproduction.com.com.ua'));
        $this->assertTrue(Checker::CheckEmail('max@webproduction.com.com.com.ua'));
        $this->assertTrue(Checker::CheckEmail('max@webproduction.com.com.com.com.ua'));
        $this->assertTrue(Checker::CheckEmail('m-ax@webproduction.com.ua'));
        $this->assertTrue(Checker::CheckEmail('m.ax@webproduction.com.ua'));
        $this->assertTrue(Checker::CheckEmail('m.a_x@webproduction.com.ua'));
        $this->assertTrue(Checker::CheckEmail('m.a_x@webproduction.com.ua'));

        $this->assertFalse(Checker::CheckEmail('m..ax@webproduction.com.ua'));
        $this->assertFalse(Checker::CheckEmail('max@webpro..duction.com.ua'));
        $this->assertFalse(Checker::CheckEmail('m!ax@webproduction.com.ua'));
        $this->assertFalse(Checker::CheckEmail('.max@webproduction.com.ua'));
        $this->assertFalse(Checker::CheckEmail('max@webprod/uction.com.ua'));
        $this->assertFalse(Checker::CheckEmail('max.@webproduction.com.ua'));
        $this->assertFalse(Checker::CheckEmail('max@.webproduction.com.ua'));
        $this->assertFalse(Checker::CheckEmail('max@webproduction.com.ua.'));
        $this->assertFalse(Checker::CheckEmail('!max@webproduction.com.ua'));
        $this->assertFalse(Checker::CheckEmail('max!@webproduction.com.ua'));
        $this->assertFalse(Checker::CheckEmail('max@.webproduction.com.ua'));
        $this->assertFalse(Checker::CheckEmail('max'));
    }

    public function testCheckICQ() {
        $this->assertTrue(Checker::CheckICQ('457447'));
        $this->assertTrue(Checker::CheckICQ('123456789'));
        $this->assertFalse(Checker::CheckICQ('1234567890'));
        $this->assertFalse(Checker::CheckICQ('1'));
        // $this->assertFalse(Checker::CheckICQ('000001'));
    }

    public function testCheckLogin() {
        $this->assertTrue(Checker::CheckLogin('max'));
        $this->assertTrue(Checker::CheckLogin('maxwebproduction'));
        $this->assertFalse(Checker::CheckLogin('max-wp'));
        $this->assertFalse(Checker::CheckLogin('max_wp'));
        $this->assertTrue(Checker::CheckLogin('001'));
        $this->assertFalse(Checker::CheckLogin('русский'));
    }

    public function testCheckPassword() {
        $this->assertTrue(Checker::CheckPassword('123456'));
        $this->assertTrue(Checker::CheckPassword('123456123456123456123456123456123456123456'));
        $this->assertFalse(Checker::CheckPassword('123456123456123456123456123456123456123456', 3, 6));
    }

    public function testCheckName() {
        $this->assertTrue(Checker::CheckName('Miroshnichenko Maxim A.'));
        $this->assertFalse(Checker::CheckName('Miroshnichenko Maxim'));
        $this->assertFalse(Checker::CheckName('X.Y.Z.'));
        $this->assertTrue(Checker::CheckName('Xerosom Y. Z.'));
        $this->assertTrue(Checker::CheckName('Мирошниченко М А'));
        $this->assertFalse(Checker::CheckName('Мирошниченко М'));
    }

    public function testCheckPhone() {
        $this->assertTrue(Checker::CheckPhone('0504629398'));
        $this->assertTrue(Checker::CheckPhone('123456'));
        $this->assertTrue(Checker::CheckPhone('123-456'));
        $this->assertTrue(Checker::CheckPhone('12-34-56'));
        $this->assertTrue(Checker::CheckPhone('+380504479530'));
        $this->assertTrue(Checker::CheckPhone('+38(050)4479530'));
        $this->assertTrue(Checker::CheckPhone('+38(050)447-95-30'));
        $this->assertFalse(Checker::CheckPhone('+38(050)#02'));
        $this->assertFalse(Checker::CheckPhone('02'));
    }

    public function testCheckDate() {
        $this->assertTrue(Checker::CheckDate('31-12-1986'));
        $this->assertTrue(Checker::CheckDate('1986/12/31'));
        $this->assertFalse(Checker::CheckDate('bugaga'));
    }

    public function testCheckDomainName() {
        $this->assertTrue(Checker::CheckDomainName('webproduction.ua'));
        $this->assertFalse(Checker::CheckDomainName('www.webproduction.ua'));
    }

    public function testCheckURL() {
        $this->assertTrue(Checker::CheckURL('webproduction.ua'));
        $this->assertTrue(Checker::CheckURL('www.webproduction.ua'));
        $this->assertTrue(Checker::CheckURL('http://www.webproduction.ua'));
        $this->assertTrue(Checker::CheckURL('http://www.webproduction.ua/xxx/yyy/'));
        $this->assertFalse(Checker::CheckURL('/www.webproduction.ua/'));
        $this->assertFalse(Checker::CheckURL('ftp:/www.webproduction.ua/'));
        $this->assertTrue(Checker::CheckURL('ftp://www.webproduction.ua/'));
        $this->assertTrue(Checker::CheckURL('ftp://ffx:123qwe@webproduction.ua/'));
        $this->assertTrue(Checker::CheckURL('ftp://ffx@webproduction.ua/'));
        $this->assertTrue(Checker::CheckURL('mission-centre.org'));
        $this->assertTrue(Checker::CheckURL('mission-centre.org:8080'));
        $this->assertTrue(Checker::CheckURL('webproduction.localhost'));
        $this->assertTrue(Checker::CheckURL('http://webproduction.localhost/url?id=1'));
        $this->assertTrue(Checker::CheckURL('http://webproduction.localhost:8080/url?id=1'));
        $this->assertFalse(Checker::CheckURL('http://-webproduction.localhost/url'));
        $this->assertFalse(Checker::CheckURL('ftp://-webproduction.localhost/url'));
        $this->assertTrue(Checker::CheckURL('ftp://192.168.0.1/'));
        $this->assertFalse(Checker::CheckURL(''));
    }

    public function testCheckHostName() {
        $this->assertTrue(Checker::CheckHostname('localhost'));
        $this->assertTrue(Checker::CheckHostname('local_host'));
        $this->assertTrue(Checker::CheckHostname('local_host.com.ua'));
        $this->assertTrue(Checker::CheckHostname('local_host.c_om.ua'));
        $this->assertTrue(Checker::CheckHostname('webproduction.ua'));
        $this->assertFalse(Checker::CheckHostname('webproduction.a'));
        $this->assertTrue(Checker::CheckHostname('www.webproduction.ua'));
    }

    public function testCheckIP() {
        $this->assertTrue(Checker::CheckIP('172.16.0.1'));
        $this->assertTrue(Checker::CheckIP('172.16.0.1', 'ipv4'));
        $this->assertFalse(Checker::CheckIP('172.16.0'));
        $this->assertFalse(Checker::CheckIP('xxx'));
    }

    public function testCheckImageFormat() {
        $this->assertTrue(Checker::CheckImageFormat(dirname(__FILE__).'/testimage.exe'));
        $this->assertTrue(Checker::CheckImageFormat(dirname(__FILE__).'/testimage.jpg'));
        $this->assertTrue(Checker::CheckImageFormat(dirname(__FILE__).'/testimage.gif'));
        $this->assertTrue(Checker::CheckImageFormat(dirname(__FILE__).'/testimage.png'));
        $this->assertFalse(Checker::CheckImageFormat(dirname(__FILE__).'/testimage.pdf'));
        $this->assertFalse(Checker::CheckImageFormat(dirname(__FILE__).'/include.php'));
        $this->assertFalse(Checker::CheckImageFormat('none'));
        $this->assertFalse(Checker::CheckImageFormat(''));
    }

    public function testCheckNumberFormat() {
        $this->assertTrue(Checker::CheckNumberFormat(10, false));
        $this->assertTrue(Checker::CheckNumberFormat(10.01, false));
        $this->assertTrue(Checker::CheckNumberFormat('10.01', false));
        $this->assertTrue(Checker::CheckNumberFormat('10,01', false));
        $this->assertFalse(Checker::CheckNumberFormat('10X01', false));
        // $this->assertFalse(Checker::CheckNumberFormat('10E01', false));

        $this->assertEquals(10, Checker::CheckNumberFormat(10, true));
        $this->assertEquals(10, Checker::CheckNumberFormat(10));
        $this->assertEquals('10.00', Checker::CheckNumberFormat(10), true);
    }

    public function tearDown() {

    }

}