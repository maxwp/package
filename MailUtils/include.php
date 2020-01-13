<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2016 WebProduction <webproduction.ua>
 *
 * This program is free software; you can not redistribute it and/or
 * modify it.
 */

/**
 * MailUtils
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   MailUtils
 */
ClassLoader::Get()->registerClass(__DIR__.'/MailUtils_Letter.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailUtils_Config.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailUtils_Smarty.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailUtils_SmartySender.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailUtils_ISender.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailUtils_SenderMail.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailUtils_SenderSMTP.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailUtils_SenderQueDB.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailUtils_SMTP.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailUtils_Exception.class.php');
