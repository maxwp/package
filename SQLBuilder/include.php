<?php
// подключаем ClassLoader
if (!class_exists('ClassLoader')) {
    include_once(__DIR__.'/../ClassLoader/include.php');
}

ClassLoader::Get()->registerClass(__DIR__.'/SQLBuilder.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/SQLBuilder_Exception.class.php');
