<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Storage handler: array in memory.
 * Useful for registries, pulls, arrays.
 *
 * Обработчик данных "массив во временной памяти".
 * Можно использовать для построение реестров, пулов и т.п.
 *
 * @author Maxim Miroshnichenko
 * @copyright WebProduction
 * @package Storage
 */
class Storage_Array extends Pattern_RegistryArray implements Storage_IHandler {

    public function setEx($key, $value, $ttl) {
        throw new Exception("TTL not supported");
    }

}