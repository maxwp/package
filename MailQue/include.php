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
 * MailQue
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   MailQue
 */
ClassLoader::Get()->registerClass(__DIR__.'/MailQue.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue_Letter.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue_Smarty.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue_ISender.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue_SenderMail.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue_SenderSMTP.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue_SenderQueDB.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue_SMTP.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue_Exception.class.php');
