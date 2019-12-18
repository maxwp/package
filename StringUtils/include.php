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
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_Converter.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_Transliterate.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_SimilarText.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_Orthographic.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_BadLanguageDetector.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_Limiter.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_Punycode.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_AFormatter.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_FormatterPhoneClear.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_FormatterPhoneDefault.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_FormatterPhoneUACN.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_FormatterAddressUACN.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_FormatterURL.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_FormatterPrice.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_MD5.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils_Exception.class.php');