<?php
class EV extends Pattern_ARegistrySingleton {

    /**
     * @param string $key
     * @return EV_IHandler
     */
    public static function Get(string $key) {
        return self::_Get($key);
    }

    /**
     * @return EV_Internal
     */
    public static function GetInternal() {
        return self::_Get(self::EV_INTERNAL);
    }

    public static function Register(string $key, $object) {
        self::_Register($key, $object, 'EV_IHandler');
    }

    protected static $_ExceptionClass = EV_Exception::class;

    const EV_INTERNAL = 'internal';

}