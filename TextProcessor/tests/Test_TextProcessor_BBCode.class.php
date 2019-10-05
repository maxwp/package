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
 * TextProcessor with BBCode handlers unit-test
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */
class Test_TextProcessor_BBCode extends TestKit_TestClass {

    public function setUp() {

    }

    public function testBBCodeURL1() {
        $text = 'My name is Maxim, [url]http://webproduction.com.ua[/url]';
        $text .= $text;

        $result = 'My name is Maxim, <a href="http://webproduction.com.ua">http://webproduction.com.ua</a>';
        $result .= $result;

        $processor = new TextProcessor();
        $processor->addAction(new TextProcessor_ActionBBCodeURL());
        $processor->addAction(new TextProcessor_ActionBBCodeURL());
        $processor->addAction(new TextProcessor_ActionBBCodeURL());

        $this->assertEquals(
        $result,
        $processor->process($text)
        );
    }

    public function testBBCodeURL2() {
        $text = 'My name is Maxim, [url=http://ya.ru/]bbb[/url]';

        $processor = new TextProcessor();
        $processor->addAction(new TextProcessor_ActionBBCodeURL());

        $this->assertEquals(
        'My name is Maxim, <a href="http://ya.ru/">bbb</a>',
        $processor->process($text)
        );
    }

    public function tearDown() {

    }

}