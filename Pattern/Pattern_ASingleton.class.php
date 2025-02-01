<?php
abstract class Pattern_ASingleton {

    abstract protected function __construct();

    protected function __clone() {
        throw new Exception("Cannot clone singleton " . get_called_class());
    }

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
        self::$_InstanceArray[static::class] = $object;
    }

    /**
     * @var array<Pattern_ASingleton>
     */
    private static array $_InstanceArray = [];

}