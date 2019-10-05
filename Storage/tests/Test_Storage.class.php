<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Полный тест пакета Storage
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @author Vladimir Gromyak <ramm@webproduction.com.ua>
 * @copyright WebProduction
 * @package Storage
 */
class Test_Storage extends TestKit_TestClass {

    public function setUp() {
        Storage::Reset();
    }

    public function testHandlerSession() {
        Storage::Initialize('session', new Storage_HandlerArray());
        Storage::Get('session')->setData('key1', 'value1');
        Storage::Get('session')->setData('key2', 'value2');

        $this->assertEquals('value1', Storage::Get('session')->getData('key1'));
        $this->assertEquals('value2', Storage::Get('session')->getData('key2'));

        try {
            Storage::Get('none');
            $this->fail();
        } catch (Exception $e) {

        }

        try {
            $this->assertEquals('xxx', file_get_contents(Storage::Get('files')->getData('key3')));
            $this->fail();
        } catch (Exception $e) {

        }
    }

    public function testHandlerArray() {
        Storage::Initialize('array', new Storage_HandlerArray());
        Storage::Get('array')->setData('key1', 'value1');
        Storage::Get('array')->setData('key2', 'value2');

        $this->assertEquals('value1', Storage::Get('array')->getData('key1'));
        $this->assertEquals('value2', Storage::Get('array')->getData('key2'));

        try {
            Storage::Get('none');
            $this->fail();
        } catch (Exception $e) {

        }

        try {
            $this->assertEquals('xxx', file_get_contents(Storage::Get('files')->getData('key3')));
            $this->fail();
        } catch (Exception $e) {

        }
    }

    public function testHandlerCacheFiles() {
        Storage::Initialize('array', new Storage_HandlerCacheFiles());

        Storage::Get('array')->setData('key1', 'value1');
        Storage::Get('array')->setData('key2', 'value2');

        $this->assertEquals('value1', Storage::Get('array')->getData('key1'));
        $this->assertEquals('value2', Storage::Get('array')->getData('key2'));

        // проверка TTL = 1 секунда
        Storage::Get('array')->setData('key3', 'value3', 1);
        Storage::Get('array')->setData('key4', 'value3', 3);
        $this->assertEquals('value3', Storage::Get('array')->getData('key3'));
        sleep(2); // через 2 секунды данные уже будут не доступен
        $this->assertFalse(Storage::Get('array')->hasData('key3'));
        $this->assertTrue(Storage::Get('array')->hasData('key4'));
    }

    public function testHandlerFiles() {
        Storage::Initialize('files',  Storage_HandlerFiles::CreateInternal('tmp1'));

        $file = __DIR__.'/test.txt';
        $fileContents = file_get_contents($file);

        Storage::Get('files')->setData('key1', $file);
        Storage::Get('files')->setData('key2', $file);

        $this->assertTrue(Storage::Get('files')->hasData('key1'));
        $this->assertFalse(Storage::Get('files')->hasData('key1.x'));

        $this->assertEquals($fileContents, file_get_contents(Storage::Get('files')->getData('key1')));
        $this->assertEquals($fileContents, file_get_contents(Storage::Get('files')->getData('key2')));

        try {
            $this->assertEquals($fileContents, file_get_contents(Storage::Get('files')->getData('key3')));
            $this->fail();
        } catch (Exception $e) {

        }

        // проверка parent-ов
        Storage::Get('files')->setData('key1.1', $file, false, 'key1');
        Storage::Get('files')->setData('key1.1.1', $file, false, 'key1.1');
        Storage::Get('files')->setData('key1.1.2', $file, false, 'key1.1');
        Storage::Get('files')->setData('key1.1.1.1', $file, false, 'key1.1.1');

        Storage::Get('files')->removeData('key1.1.1');
        $this->assertFalse(Storage::Get('files')->hasData('key1.1.1'));
        $this->assertFalse(Storage::Get('files')->hasData('key1.1.1.1'));
        $this->assertTrue(Storage::Get('files')->hasData('key1.1'));

        Storage::Get('files')->removeData('key1');
        $this->assertFalse(Storage::Get('files')->hasData('key1'));
    }

    public function tearDown() {
        try {
            Storage::Get('files')->clearData();
        } catch (Exception $e) {

        }
    }

}