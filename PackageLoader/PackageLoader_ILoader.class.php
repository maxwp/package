<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Интерфейс Loader-класса для пакетов
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package PackageLoader
 */
interface PackageLoader_ILoader {

    /**
     * В конструктор передается массив параметров,
     * на которые может реагировать пакет
     *
     * @param array $paramsArray
     */
    public function __construct($paramsArray);

}