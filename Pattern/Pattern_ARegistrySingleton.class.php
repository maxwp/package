<?php
abstract class Pattern_ARegistrySingleton {

    abstract public static function Get(string $key);
    abstract public static function Register(string $key, $object);

    protected static function _Get(string $key) {
        if (!$key) {
            throw new Exception("Empty key");
        }

        if (!isset(self::$_ObjectArray[$key])) {
            throw new Storage_Exception("Key '{$key}' not found, please, call Register() first");
        }

        return self::$_ObjectArray[$key];
    }

    protected static function _Register(string $key, Object $object, $type = false) {
        if (!$key) {
            throw new Storage_Exception("Empty key");
        }
        if ($type && !($object instanceof $type)) {
            throw new Storage_Exception("Invalid type: needs $type");
        }

        self::$_ObjectArray[$key] = $object; // @todo стоит ли лепить RegistryArray?
        return $object;
    }

    public static function Reset() {
        self::$_ObjectArray = [];
    }

    private function __construct() {
        // singleton
    }

    private function __clone() {
        throw new Exception("Cannot clone singleton " . get_called_class());
    }

    private static $_ObjectArray = [];

}
