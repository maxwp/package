<?php
abstract class Pattern_ASingleton {

    /**
     * @return static
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new static();
        }

        return self::$_Instance;
    }

    public static function Set($object) {
        self::$_Instance = $object;
    }

    abstract protected function __construct();

    private static $_Instance;

}