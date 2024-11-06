<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

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

    public static function GetMemory($key, $blockSize = 128) {
        if (isset(self::$_MemoryArray[$key])) {
            return self::$_MemoryArray[$key];
        }

        $ipcAddress = IPC_Addressing::Get()->generateIPCAddressByKey($key.$blockSize);
        self::$_MemoryArray[$key] = new IPC_Memory($ipcAddress, $blockSize);
        return self::$_MemoryArray[$key];
    }

    private static $_SemaphoreArray = [];
    private static $_MemoryArray = [];

}