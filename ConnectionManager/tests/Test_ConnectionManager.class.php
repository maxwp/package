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
 * Global test for class ConnectionManager
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package ConnectionManager
 */
class Test_ConnectionManager extends TestKit_TestClass {

    public function setUp() {
        ConnectionManager::Get()->clearConnections();
    }

    /**
     * Тест на очистку соединений
     */
    public function testConnectionsClear() {
        ConnectionManager::Get()->addConnection(new ConnectionManager_MySQL(1, 2, 3));
        ConnectionManager::Get()->clearConnections();

        try {
            ConnectionManager::Get()->getConnection('ConnectionManager_MySQL');
            $this->fail();
        } catch (ConnectionManager_Exception $e) {

        }
    }

    /**
     * Обычный тест на get/set
     */
    public function testConnections1() {
        ConnectionManager::Get()->addConnection(new ConnectionManager_MySQL(1, 2, 3));

        try {
            ConnectionManager::Get()->getConnection('ConnectionManager_MySQL-1');
            $this->fail();
        } catch (ConnectionManager_Exception $e) {

        }

        try {
            ConnectionManager::Get()->getConnection('ConnectionManager_MySQL');
        } catch (ConnectionManager_Exception $e) {
            $this->fail();
        }
    }

    /**
     * Тест на замещение соеденений
     */
    public function testConnections2() {
        ConnectionManager::Get()->addConnection(new ConnectionManager_MySQL(1, 2, 3));
        try {
            ConnectionManager::Get()->addConnection(new ConnectionManager_MySQL(4, 5, 6));
            $this->fail();
        } catch (ConnectionManager_Exception $e) {

        }

        try {
            ConnectionManager::Get()->addConnection(new ConnectionManager_MySQL(4, 5, 6), false, true);
        } catch (ConnectionManager_Exception $e) {
            $this->fail();
        }

        try {
            ConnectionManager::Get()->getConnection('ConnectionManager_MySQL');
        } catch (ConnectionManager_Exception $e) {
            $this->fail();
        }
    }

    /**
     * Тест на несколько одинаковых соединений
     */
    public function testConnections4() {
        try {
            ConnectionManager::Get()->clearConnections();
            ConnectionManager::Get()->addConnection(new ConnectionManager_MySQL(1, 2, 3), 'key1');
            ConnectionManager::Get()->addConnection(new ConnectionManager_MySQL(4, 5, 6), 'key2');
            ConnectionManager::Get()->addConnection(new ConnectionManager_MySQL(7, 8, 9), 'key3');
        } catch (Exception $e) {
            $this->fail();
        }

        try {
            ConnectionManager::Get()->getConnection('key1');
        } catch (ConnectionManager_Exception $e) {
            $this->fail();
        }

        try {
            ConnectionManager::Get()->getConnection('key2');
        } catch (ConnectionManager_Exception $e) {
            $this->fail();
        }

        try {
            ConnectionManager::Get()->getConnection('key3');
        } catch (ConnectionManager_Exception $e) {
            $this->fail();
        }
    }

    public function testConnectionDatabase1() {
        ConnectionManager::Get()->addConnection(new ConnectionManager_MySQL(1, 2, 3));
        ConnectionManager::Get()->addConnection(new ConnectionManager_PgSQL(1, 2, 3));

        try {
            $c = ConnectionManager::Get()->getConnection('ConnectionManager_MySQL');
            if (!($c instanceof ConnectionManager_IDatabaseAdapter)) {
            	$this->fail();
            }

            $c = ConnectionManager::Get()->getConnection('ConnectionManager_PgSQL');
            if (!($c instanceof ConnectionManager_IDatabaseAdapter)) {
            	$this->fail();
            }
        } catch (ConnectionManager_Exception $e) {
            $this->fail();
        }
    }

    public function testConnectionDatabase2() {
        ConnectionManager::Get()->addConnectionDatabase(new ConnectionManager_MySQL(1, 2, 3));

        try {
            $c = ConnectionManager::Get()->getConnectionDatabase();
            if (!($c instanceof ConnectionManager_IDatabaseAdapter)) {
            	$this->fail();
            }
        } catch (ConnectionManager_Exception $e) {
            $this->fail();
        }
    }

    public function tearDown() {

    }

}