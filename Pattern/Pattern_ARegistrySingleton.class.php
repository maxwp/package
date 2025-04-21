<?php
abstract class Pattern_ARegistrySingleton {

    abstract public static function Get(string $key);
    abstract public static function Register(string $key, $object);

    protected static function _Get(string $key) {
        if (!$key) {
            throw new static::$_ExceptionClass("Empty key");
        }

        $class = static::class;

        if (empty(static::$_ObjectArray[$class][$key])) {
            throw new static::$_ExceptionClass("Key '{$key}' not found, please, call Register() first");
        }

        return self::$_ObjectArray[$class][$key];
    }

    protected static function _Register(string $key, Object $object, $type = false) {
        if (!$key) {
            throw new static::$_ExceptionClass("Empty key");
        }
        if ($type && !($object instanceof $type)) {
            throw new static::$_ExceptionClass("Invalid type: needs $type");
        }

        $class = static::class;

        self::$_ObjectArray[$class][$key] = $object; // @todo стоит ли лепить RegistryArray?
        return $object;
    }

    public static function Reset() {
        $class = static::class;
        self::$_ObjectArray[$class] = [];
    }

    protected function __construct() {
        // singleton
    }

    private function __clone() {
        throw new Pattern_Exception("Cannot clone singleton " . get_called_class());
    }

    private static $_ObjectArray = [];

    protected static $_ExceptionClass = Pattern_Exception::class; // @todo overload не пашет же

}
