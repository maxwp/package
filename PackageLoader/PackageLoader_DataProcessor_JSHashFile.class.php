<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package PackageLoader
 */
class PackageLoader_DataProcessor_JSHashFile
implements PackageLoader_IDataProcessor {

	/**
     * Вызывается в момент поступления данных в PackageLoader
     * (в момент registerCSS[JS]Data())
     *
     * @param string $data
     * @return string
     */
    public function processBefore($data) {
        return $data;
    }

    /**
     * Вызывается в момент получения данных из PackageLoader'a
     * (в момент getCSS[JS]Data())
     *
     * @param string $data
     * @return string
     */
    public function processAfter($data) {
        $hash = md5($data);
        $file = dirname(__FILE__).'/compile/'.$hash.'.js';
        if (!file_exists($file)) {

        }
        file_put_contents($file, $data, LOCK_EX);
        PackageLoader::Get()->registerJSFile($file, true);

        // ничего не возвращаем
        return false;
    }

}