<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @package StringUtils
 * @copyright WebProduction
 */
if (class_exists('PackageLoader')) {
    PackageLoader::Get()->registerPHPDirectory(__DIR__);
} else {
    include_once(__DIR__.'/StringUtils_Converter.class.php');
    include_once(__DIR__.'/StringUtils_Transliterate.class.php');
    include_once(__DIR__.'/StringUtils_SimilarText.class.php');
    include_once(__DIR__.'/StringUtils_Orthographic.class.php');
    include_once(__DIR__.'/StringUtils_BadLanguageDetector.class.php');
    include_once(__DIR__.'/StringUtils_Limiter.class.php');
    include_once(__DIR__.'/StringUtils_Punycode.class.php');
    include_once(__DIR__.'/StringUtils_AFormatter.class.php');
    include_once(__DIR__.'/StringUtils_FormatterPhoneClear.class.php');
    include_once(__DIR__.'/StringUtils_FormatterPhoneDefault.class.php');
    include_once(__DIR__.'/StringUtils_FormatterPhoneUACN.class.php');
    include_once(__DIR__.'/StringUtils_FormatterAddressUACN.class.php');
    include_once(__DIR__.'/StringUtils_FormatterURL.class.php');
    include_once(__DIR__.'/StringUtils_FormatterPrice.class.php');
    include_once(__DIR__.'/StringUtils_MD5.class.php');
    include_once(__DIR__.'/StringUtils_Exception.class.php');
}