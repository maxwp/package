<?php
if (class_exists('XCMSE')) {
    XCMSE::GetPackages()->registerAutoloadPath(dirname(__FILE__));
} elseif (class_exists('PackageLoader')) {
    //PackageLoader::Get()->registerJSFile(dirname(__FILE__).'/Checker.js', true);
    PackageLoader::Get()->registerPHPClass(dirname(__FILE__).'/Checker.class.php');
} else {
    include_once(dirname(__FILE__).'/Checker.class.php');
}