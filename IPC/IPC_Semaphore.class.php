<?php
class IPC_Semaphore {

    public function __construct($ipcAddress) {
        $this->_semaphore = sem_get(
            $ipcAddress,
            1
        );
    }

    public function acquire($nonBlocking = false) {
        return sem_acquire($this->_semaphore, $nonBlocking);
    }

    public function release() {
        return sem_release($this->_semaphore);
    }

    private $_semaphore;

}