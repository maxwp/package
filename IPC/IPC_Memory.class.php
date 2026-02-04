<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class IPC_Memory {

    public function __construct($ipcAddress, $blockSize = 128, $readOnly = false) {
        $this->_blockSize = $blockSize;

        if ($readOnly) {
            $this->_memory = @shmop_open( // тут с собакой, только в режиме read only
                $ipcAddress,
                'a', // SHM_RDONLY
                0644,
                $this->_blockSize
            );
        } else {
            $this->_memory = shmop_open(
                $ipcAddress,
                'c', // IPC_CREATE
                0644,
                $this->_blockSize
            );
        }
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

    public function setDouble($value) {
        $packed = pack('d', (float) $value);
        shmop_write($this->_memory, $packed, 0);
    }

    public function getDouble() {
        $packed = shmop_read($this->_memory, 0, 8);
        $unpacked = unpack('d', $packed);
        return $unpacked[1];
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