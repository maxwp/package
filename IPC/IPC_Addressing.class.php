<?php
class IPC_Addressing {

    public function generateIPCAddressByKey($key) {
        if (!$key) {
            throw new Exception("Key not set");
        }

        if (isset($this->_keyArray[$key])) {
            return $this->_keyArray[$key];
        }

        $this->_keyArray[$key] = crc32($key);

        return $this->_keyArray[$key];
    }

    /**
     * @return IPC_Addressing
     */
    public static function Get() {
        if (!self::$_Instance) {
            $classname = __CLASS__;
            self::$_Instance = new $classname();
        }

        return self::$_Instance;
    }

    private static $_Instance;

}