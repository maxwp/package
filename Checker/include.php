<?php
if (class_exists('XCMSE')) {
    XCMSE::GetPackages()->registerAutoloadPath(__DIR__);
} elseif (class_exists('PackageLoader')) {
    PackageLoader::Get()->registerJSFile(__DIR__.'/Checker.js', true);
    PackageLoader::Get()->registerPHPClass(__DIR__.'/Checker.class.php');
} else {
    include_once(__DIR__.'/Checker.class.php');
}