<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
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
