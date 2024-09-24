<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Storage in shared memory (shmop with semaphores)
 *
 * @author Maxim Miroshnichenko
 * @copyright WebProduction
 * @package Storage
 */
class Storage_HandlerShmop implements Storage_IHandler {

    public function set($key, $value, $ttl = false) {
        if ($ttl) {
            throw new Storage_Exception("No TTL for shmop");
        }

        $packed = pack('L', strlen($value)) . $value;

        $sem = $this->_getSemaphore($key);
        $memory = $this->_getMemory($key);

        sem_acquire($sem);
        shmop_write($memory, $packed, 0);
        sem_release($sem);
    }

    public function get($key) {
        $sem = $this->_getSemaphore($key);
        $memory = $this->_getMemory($key);

        sem_acquire($sem);
        $packed_length = shmop_read($memory, 0, 4);
        $length = unpack('L', $packed_length)[1];
        $string = shmop_read($memory, 4, $length);
        sem_release($sem);

        return $string;
    }

    public function remove($key) {
        // @todo
    }

    public function clean() {
        // @todo
    }

    private function _getMemory($key) {
        if (isset($this->_memoryArray[$key])) {
            return $this->_memoryArray[$key];
        }

        $ipc = $this->_generateKeyIPC($key);

        $this->_memoryArray[$key] = shmop_open(
            $ipc,
            "c",
            0644,
            $this->_blockSize
        );

        return $this->_memoryArray[$key];
    }

    private function _getSemaphore($key) {
        if (isset($this->_semaphoreArray[$key])) {
            return $this->_semaphoreArray[$key];
        }

        $ipc = $this->_generateKeyIPC($key);

        $this->_semaphoreArray[$key] = sem_get(
            $ipc,
            1
        );

        return $this->_semaphoreArray[$key];
    }

    private function _generateKeyIPC($key) {
        if (!$key) {
            throw new Storage_Exception("Key not set");
        }

        if (isset($this->_keyArray[$key])) {
            return $this->_keyArray[$key];
        }

        $this->_keyArray[$key] = crc32($key);

        return $this->_keyArray[$key];
    }

    private $_keyArray = [];

    private $_semaphoreArray = [];

    private $_blockSize = 128; // @todo

    private $_memoryArray = [];

}