<?php
abstract class Pattern_ASingleton {

    /**
     * Массив для хранения экземпляров для каждого наследника.
     * @var array
     */
    private static $_InstanceArray = [];

    /**
     * @return static
     */
    public static function Get() {
        $class = static::class;
        if (!isset(self::$_InstanceArray[$class])) {
            self::$_InstanceArray[$class] = new static();
        }
        return self::$_InstanceArray[$class];
    }

    public static function Set($object) {
        self::$_InstanceArray[$object::class] = $object;
    }

    abstract protected function __construct();

}