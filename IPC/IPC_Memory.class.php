<?php
class IPC_Memory {

    public function __construct($ipcAddress, $blockSize = 128) {
        $this->_memory = shmop_open(
            $ipcAddress,
            "c",
            0644,
            $blockSize
        );
    }

    public function getString() {
        $packed_length = shmop_read($this->_memory, 0, 4);
        $length = unpack('L', $packed_length)[1];
        $string = shmop_read($this->_memory, 4, $length);
        return $string;
    }

    public function setString($value) {
        $packed = pack('L', strlen($value)) . $value;
        shmop_write($this->_memory, $packed, 0);
    }

    private $_memory;

}