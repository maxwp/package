<?php
/**
 * WebProduction Packages Engine
 *
 * @copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Стартер Engine в режиме 2.6
 * - В этом режиме неоходимы директории contents и файлы contents.*
 * - В этом режиме по умолчанию НЕ доступны FClasses
 * - В этом режиме по умолчанию НЕ подключаются все css и js файлы
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   Engine
 */

// подключаем PackageLoader
if (!class_exists('PackageLoader')) {
    if (file_exists(__DIR__.'/../PackageLoader/include.php')) {
        include_once(__DIR__.'/../PackageLoader/include.php');
    }
}

// определяем project path
try {
    PackageLoader::Get()->getProjectPath();
} catch (Exception $e) {
    PackageLoader::Get()->setProjectPath(dirname(dirname(__DIR__)));
}

// подключаем пакет движка
PackageLoader::Get()->import('Engine');

// инициализируем движок, пусть он подгрузит все что ему нужно,
// в том числе файлы engine.mode.php, engine.config.php, структуру contents
Engine::Initialize();