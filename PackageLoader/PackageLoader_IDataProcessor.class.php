<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Интерфейс data-процессора
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package PackageLoader
 */
interface PackageLoader_IDataProcessor {

    /**
     * Вызывается в момент поступления данных в PackageLoader
     * (в момент registerCSS[JS]Data())
     *
     * @param string $data
     * @return string
     */
    public function processBefore($data);

    /**
     * Вызывается в момент получения данных из PackageLoader'a
     * (в момент getCSS[JS]Data())
     *
     * @param string $data
     * @return string
     */
    public function processAfter($data);

}