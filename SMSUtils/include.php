<?php
/**
 * SMSUtils
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   SMSUtils
 */

if (class_exists('PackageLoader')) {
    PackageLoader::Get()->import('SQLObject');

    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_ISender.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_Exception.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_SenderTurbosmsua.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_SenderSMSCru.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_SenderSMSCkz.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_SenderWebSMS.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_SenderQueDB.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_SenderWorldWide.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_SenderSMSFly.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_SenderAsteriskSMS.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_SenderOpenVoxSMS.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_SenderLife.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_SenderSMSru.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_SenderAlphaSMS.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/SMSUtils_DB.class.php');

    Events::Get()->observe('SQLObject.build.before', 'SMSUtils_DB');
} else {
    include_once(__DIR__.'/SMSUtils.class.php');
    include_once(__DIR__.'/SMSUtils_ISender.class.php');
    include_once(__DIR__.'/SMSUtils_Exception.class.php');
    include_once(__DIR__.'/SMSUtils_SenderTurbosmsua.class.php');
    include_once(__DIR__.'/SMSUtils_SenderSMSCkz.class.php');
    include_once(__DIR__.'/SMSUtils_SenderSMSCru.class.php');
    include_once(__DIR__.'/SMSUtils_SenderQueDB.class.php');
    include_once(__DIR__.'/SMSUtils_SenderWorldWide.class.php');
    include_once(__DIR__.'/SMSUtils_SenderWebSMS.class.php');
    include_once(__DIR__.'/SMSUtils_SenderSMSFly.class.php');
    include_once(__DIR__.'/SMSUtils_SenderAsteriskSMS.class.php');
    include_once(__DIR__.'/SMSUtils_SenderSMSru.class.php');
    include_once(__DIR__.'/SMSUtils_SenderAlphaSMS.class.php');
    include_once(__DIR__.'/SMSUtils_SenderOpenVoxSMS.class.php');
    include_once(__DIR__.'/SMSUtils_SenderLife.class.php');
}