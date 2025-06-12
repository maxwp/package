<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Storage in shared memory (shmop with semaphores)
 *
 * @author Maxim Miroshnichenko
 * @copyright WebProduction
 * @package Storage
 */
class Storage_Shmop implements Storage_IHandler {

    public function __construct($blockSize = 128) {
        $this->_blockSize = $blockSize;
    }

    public function set($key, $value) {
        $sem = IPC::GetSemaphore($key);
        $memory = IPC::GetMemory($key, $this->_blockSize);

        $sem->acquire();
        $memory->setString($value);
        $sem->release();
    }

    public function setEx($key, $value, $ttl) {
        throw new Storage_Exception("No TTL for shmop");
    }

    public function get($key) {
        $sem = IPC::GetSemaphore($key);
        $memory = IPC::GetMemory($key, $this->_blockSize);

        $sem->acquire();
        $string = $memory->getString();
        $sem->release();

        return $string;
    }

    public function incr($key, $value) {
        $sem = IPC::GetSemaphore($key);
        $memory = IPC::GetMemory($key, $this->_blockSize);

        $sem->acquire();
        $string = (float) $memory->getString();
        $newValue = $string + $value;
        $memory->setString((string)$newValue);
        $sem->release();

        return $newValue;
    }

    public function has($key) {
        // @todo
    }

    public function remove($key) {
        // @todo
    }

    public function clean() {
        // @todo
    }

    private $_blockSize = 128;
}