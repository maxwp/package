<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2016 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * ServiceUtils
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   ServiceUtils
 */
if (class_exists('PackageLoader')) {
    PackageLoader::Get()->registerPHPClass(__DIR__.'/ServiceUtils_Exception.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/ServiceUtils_AbstractService.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/ServiceUtils_UserService.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/ServiceUtils.class.php');
} else {
    throw new Exception('Package ServiceUtils requires PackageLoader', 0);
}