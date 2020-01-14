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
 * SMSQue
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   SMSQue
 */
ClassLoader::Get()->registerClass(__DIR__.'/SMSQue.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/SMSQue_Smarty.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/SMSQue_ISender.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/SMSQue_SenderTurboSMSUA.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/SMSQue_Exception.class.php');
