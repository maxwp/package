<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class IPC_Memory {

    public function __construct($ipcAddress, $blockSize = 128) {
        $this->_blockSize = $blockSize;

        $this->_memory = shmop_open(
            $ipcAddress,
            "c",
            0644,
            $this->_blockSize
        );
    }

    public function read($offset, $length) {
        return shmop_read($this->_memory, $offset, $length);
    }

    public function write($value, $offset) {
        return shmop_write($this->_memory, $value, $offset);
    }

    /**
     * Получить значение всего блока памяти как есть
     *
     * @return string
     */
    public function getValue() {
        return shmop_read($this->_memory, 0, $this->_blockSize);
    }

    /**
     * Записать значение в блок памяти
     *
     * @param $value
     * @return void
     */
    public function setValue($value) {
        shmop_write($this->_memory, $value, 0);
    }

    public function getString() {
        $packed_length = shmop_read($this->_memory, 0, 4);
        $length = unpack('L', $packed_length)[1];
        return shmop_read($this->_memory, 4, $length);
    }

    public function setString($value) {
        $packed = pack('L', strlen($value)) . $value;
        shmop_write($this->_memory, $packed, 0);
    }

    public function getBool() {
        $packed = shmop_read($this->_memory, 0, 1);
        return (bool) unpack('c', $packed)[1];
    }

    public function setBool($value) {
        $packed = pack('c', $value ? 1 : 0);
        shmop_write($this->_memory, $packed, 0);
    }

    public function getInt64u() {
        $packed = shmop_read($this->_memory, 0, 8);
        return unpack('Q', $packed)[1];
    }

    public function setInt64u($value) {
        $packed = pack('Q', $value);
        shmop_write($this->_memory, $packed, 0);
    }

    private $_memory;

    private $_blockSize;

}