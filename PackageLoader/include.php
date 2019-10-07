<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2011 WebProduction <webproduction.com.ua>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU LESSER GENERAL PUBLIC LICENSE
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package PackageLoader
 */

// fix for Mac OS X PHP 5.3 default
@date_default_timezone_set(date_default_timezone_get());

include_once(dirname(__FILE__).'/PackageLoader.class.php');
include_once(dirname(__FILE__).'/PackageLoader_ILoader.class.php');
include_once(dirname(__FILE__).'/PackageLoader_IDataProcessor.class.php');
include_once(dirname(__FILE__).'/PackageLoader_Exception.class.php');
include_once(dirname(__FILE__).'/PackageLoader_DataProcessor_CSSHashFile.class.php');
include_once(dirname(__FILE__).'/PackageLoader_DataProcessor_JSHashFile.class.php');