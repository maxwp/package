<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Ускоренный MD5-генератор хешей.
 * Ускорение за счет кеширования результатов.
 * Полезно при больших нагрузках.
 *
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package StringUtils
 */
class StringUtils_MD5 {

    /**
     * Построить MD5-хеш по строке
     *
     * @param string $string
     * @return string
     */
	public static function FromString($string) {
	    foreach (self::$_CacheArray as $x) {
	    	if ($x[0] == $string) {
	    		return $x[1];
	    	}
	    }

	    $x = md5($string);
	    self::$_CacheArray[] = array($string, $x);
	    return $x;
	}

	/**
	 * Построить MD5-хеш по файлу
	 *
	 * @param string $file
	 * @return string
	 */
	public static function FromFile($file) {
	    if (!file_exists($file)) {
	    	throw new StringUtils_Exception("File {$file} not exists");
	    }

	    return self::FromString(file_get_contents($file));
	}

	/**
	 * Построить MD5-хеш по любому массиву.
	 * Используется json_encode()
	 *
	 * @param array $array
	 * @return string
	 */
	public static function FromArray($array) {
	    return self::FromString(json_encode($array));
	}

	/**
	 * @var array
	 */
	private static $_CacheArray = array();

}