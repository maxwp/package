<?php
class IPC {

    /**
     * @param $key
     * @return IPC_Semaphore
     */
    public static function GetSemaphore($key) {
        if (isset(self::$_SemaphoreArray[$key])) {
            return self::$_SemaphoreArray[$key];
        }

        $ipcAddress = IPC_Addressing::Get()->generateIPCAddressByKey($key);
        self::$_SemaphoreArray[$key] = new IPC_Semaphore($ipcAddress);
        return self::$_SemaphoreArray[$key];
    }

    private static $_SemaphoreArray = [];

    /*public function getSemaphore($key) {
        if (isset($this->_semaphoreArray[$key])) {
            return $this->_semaphoreArray[$key];
        }

        $ipcAddress = IPC_Addressing::Get()->generateIPCAddressByKey($key);
        $this->_semaphoreArray[$key] = new IPC_Semaphore($ipcAddress);
        return $this->_semaphoreArray[$key];
    }

    private $_semaphoreArray = [];

    public static function Get() {
        if (!self::$_Instance) {
            $classname = __CLASS__;
            self::$_Instance = new $classname();
        }

        return self::$_Instance;
    }

    private static $_Instance;*/

}