<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software; you cannot redistribute it and/or
 * modify it.
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */

if (class_exists('PackageLoader')) {
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_Exception.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_IAction.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionBBCodeEOL.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionBBCodeURL.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionBBCodeU.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionBBCodeI.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionBBCodeS.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionBBCodeB.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionBBCodeImg.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionBBCodeColor.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionBBCodeRutube.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionBBCodeVimeo.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionBBCodeQuote.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionBBCodeCode.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionContentFromURL.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionTidy.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionPregMatch.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionPregReplace.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionIconv.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionCSSClear.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionHTMLClear.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionHTMLTagsClear.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionHTMLTagsRemove.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionCSSCompress.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_ActionTextToHTML.class.php');
    PackageLoader::Get()->registerPHPClass(__DIR__.'/TextProcessor_BBCode.class.php');
} else {
    include_once(__DIR__.'/TextProcessor.class.php');
    include_once(__DIR__.'/TextProcessor_Exception.class.php');
    include_once(__DIR__.'/TextProcessor_IAction.class.php');
    include_once(__DIR__.'/TextProcessor_ActionBBCodeEOL.class.php');
    include_once(__DIR__.'/TextProcessor_ActionBBCodeURL.class.php');
    include_once(__DIR__.'/TextProcessor_ActionBBCodeU.class.php');
    include_once(__DIR__.'/TextProcessor_ActionBBCodeI.class.php');
    include_once(__DIR__.'/TextProcessor_ActionBBCodeS.class.php');
    include_once(__DIR__.'/TextProcessor_ActionBBCodeB.class.php');
    include_once(__DIR__.'/TextProcessor_ActionBBCodeImg.class.php');
    include_once(__DIR__.'/TextProcessor_ActionBBCodeColor.class.php');
    include_once(__DIR__.'/TextProcessor_ActionBBCodeYoutube.class.php');
    include_once(__DIR__.'/TextProcessor_ActionBBCodeRutube.class.php');
    include_once(__DIR__.'/TextProcessor_ActionBBCodeVimeo.class.php');
    include_once(__DIR__.'/TextProcessor_ActionBBCodeQuote.class.php');
    include_once(__DIR__.'/TextProcessor_ActionBBCodeCode.class.php');
    include_once(__DIR__.'/TextProcessor_ActionContentFromURL.class.php');
    include_once(__DIR__.'/TextProcessor_ActionTidy.class.php');
    include_once(__DIR__.'/TextProcessor_ActionPregMatch.class.php');
    include_once(__DIR__.'/TextProcessor_ActionPregReplace.class.php');
    include_once(__DIR__.'/TextProcessor_ActionIconv.class.php');
    include_once(__DIR__.'/TextProcessor_ActionCSSClear.class.php');
    include_once(__DIR__.'/TextProcessor_ActionHTMLClear.class.php');
    include_once(__DIR__.'/TextProcessor_ActionHTMLTagsClear.class.php');
    include_once(__DIR__.'/TextProcessor_ActionHTMLTagsRemove.class.php');
    include_once(__DIR__.'/TextProcessor_ActionCSSCompress.class.php');
    include_once(__DIR__.'/TextProcessor_ActionTextToHTML.class.php');
    include_once(__DIR__.'/TextProcessor_BBCode.class.php');
}