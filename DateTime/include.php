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

// fix for Mac OS X PHP 5.3 default
@date_default_timezone_set(date_default_timezone_get());

if (class_exists('XCMSE')) {
    XCMSE::GetPackages()->registerAutoloadPath(__DIR__);
} elseif (class_exists('PackageLoader')) {
    PackageLoader::Get()->registerPHPDirectory(__DIR__);
} else {
    include_once(__DIR__.'/DateTime_IClassFormat.class.php');
    include_once(__DIR__.'/DateTime_ClassFormatDefault.class.php');
    include_once(__DIR__.'/DateTime_ClassFormatPhonetic.class.php');
    include_once(__DIR__.'/DateTime_ClassFormatPhoneticFuture.class.php');
    include_once(__DIR__.'/DateTime_Object.class.php');
    include_once(__DIR__.'/DateTime_Corrector.class.php');
    include_once(__DIR__.'/DateTime_Differ.class.php');
    include_once(__DIR__.'/DateTime_Formatter.class.php');
}