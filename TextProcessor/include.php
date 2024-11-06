<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */

ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor_IAction.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor_ActionContentFromURL.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor_ActionTidy.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor_ActionPregMatch.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor_ActionPregReplace.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor_ActionIconv.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor_ActionCSSClear.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor_ActionHTMLClear.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor_ActionHTMLTagsClear.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor_ActionHTMLTagsRemove.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor_ActionCSSCompress.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor_ActionTextToHTML.class.php');