<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2026 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

// кидать ошибку если не php8+, потому что работать не будет
if (PHP_MAJOR_VERSION < 8) {
    throw new Exception("Eventic needs PHP 8+");
}

// default locale
setlocale(LC_ALL, 'en_EN.utf8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding("UTF-8");
}

// fix for Mac OS X PHP 5.3 default
//@date_default_timezone_set(date_default_timezone_get());

//date_default_timezone_set('Europe/Kyiv');

//gc_disable();

// костыляка для Throwable и сборщика трасс (в php 8.3 отключат и так)
ini_set('zend.exception_ignore_args', 1);

// 1st
// NB! Супер важно именно Patten подключить прямым include
include_once(__DIR__.'/Pattern/Pattern_Exception.class.php');
include_once(__DIR__.'/Pattern/Pattern_ASingleton.class.php');
include_once(__DIR__.'/Pattern/Pattern_RegistryArray.class.php');
include_once(__DIR__.'/Pattern/Pattern_ARegistrySingleton.class.php');

// 2nd
// NB! Супер важно именно ClassLoader подключить прямым include
include_once(__DIR__.'/ClassLoader/ClassLoader.class.php');
include_once(__DIR__.'/ClassLoader/ClassLoader_Exception.class.php');

// @todo а можно делать массовый register? сбор массива быстрее чем multi-call?

// 3rd: others
ClassLoader::Get()->registerClass(__DIR__.'/File/File.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/File/File_Exception.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/Checker/Checker.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_IConnection.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_IDatabaseAdapter.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_MySQLi.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_RDS.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_PDO.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_Redis.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_Memcached.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_Socket_IReceiver.interface.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_Socket_Abstract.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_SocketStream.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_SocketUDP.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_SocketUDPConnected.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Connection/Connection_SocketUDS.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/DateTime/DateTime_IClassFormat.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/DateTime/DateTime_ClassFormatDefault.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/DateTime/DateTime_ClassFormatPhonetic.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/DateTime/DateTime_ClassFormatPhoneticFuture.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/DateTime/DateTime_Object.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/DateTime/DateTime_Differ.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/DateTime/DateTime_Formatter.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/DateTime/DateTime_Translate.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/Events/Events_Abstract.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Events/Events_Exception.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_Events.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_Typing.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_Smarty.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_IRequest.interface.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_IResponse.interface.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_IContent.interface.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_IRouting.interface.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_Request.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_RequestFile.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_ResponseHTTP.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_Routing.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_RequestCLI.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_ResponseCLI.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_RoutingCLI.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_AContent.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_AContentCli.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_AContentSmarty.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_Network.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_RequestRemote.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EE/EE_RoutingRemote.class.php');

// default contents
// @todo только если был загружен EE
ClassLoader::Get()->registerClass(__DIR__.'/content/ee500.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_Action.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionToPNG.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionToJPEG.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionResizeCrop.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionResizeProportional.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionBlurGaussian.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionNegate.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionGrayscale.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionBrightness.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionContrast.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionColorize.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionEdgeDetect.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionEmboss.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionSmooth.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionPixelate.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionSharpen.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionRoundCorners.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionGammaCorrect.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionCut.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_Thumber.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ThumberStorage.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor/ImageProcessor_ActionWatermarkPNG.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_Letter.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_Smarty.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_ISender.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_SenderMail.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_SenderSMTP.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_SenderQueDB.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_SMTP.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_Exception.class.php');

// @todo перенести ветку onebox'a
include(__DIR__.'/Smarty/2.6.26-optimized/Smarty.class.php');
include(__DIR__.'/Smarty/Smarty_FileFetch.class.php');

//ClassLoader::Get()->registerClass(__DIR__.'/SQLBuilder/SQLBuilder.class.php');
//ClassLoader::Get()->registerClass(__DIR__.'/SQLBuilder/SQLBuilder_Exception.class.php');
//ClassLoader::Get()->registerClass(__DIR__.'/SQLBuilder/SQLBuilder_String.class.php');
//ClassLoader::Get()->registerClass(__DIR__.'/SQLBuilder/SQLBuilder_AQuery.class.php');
//ClassLoader::Get()->registerClass(__DIR__.'/SQLBuilder/SQLBuilder_Select.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/Storage/Storage.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage/Storage_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage/Storage_IHandler.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage/Storage_Array.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage/Storage_Memcached.class.php');
//ClassLoader::Get()->registerClass(__DIR__.'/Storage/Storage_MemSock.class.php'); // @todo
ClassLoader::Get()->registerClass(__DIR__.'/Storage/Storage_Redis.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Storage/Storage_Shmop.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_Converter.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_Transliterate.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_SimilarText.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_Orthographic.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_BadLanguageDetector.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_Limiter.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_Punycode.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_AFormatter.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_FormatterPhoneClear.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_FormatterPhoneDefault.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_FormatterPhoneUACN.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_FormatterAddressUACN.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_FormatterURL.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_MD5.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StringUtils/StringUtils_Exception.class.php');
include_once __DIR__.'/StringUtils/StringUtils_FormatterPrice.class.php'; // no autoload for static classes, performance
include_once __DIR__.'/StringUtils/StringUtils_FormatterTimestamp.class.php'; // no autoload for static classes, performance

ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor/TextProcessor_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor/TextProcessor_IAction.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor/TextProcessor_ActionContentFromURL.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor/TextProcessor_ActionTidy.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor/TextProcessor_ActionPregMatch.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor/TextProcessor_ActionPregReplace.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor/TextProcessor_ActionIconv.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor/TextProcessor_ActionCSSClear.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor/TextProcessor_ActionHTMLClear.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor/TextProcessor_ActionHTMLTagsClear.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor/TextProcessor_ActionHTMLTagsRemove.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor/TextProcessor_ActionCSSCompress.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/TextProcessor/TextProcessor_ActionTextToHTML.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/Array/Array_Object.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Array/Array_Static.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/Cron/Cron.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Cron/Cron_Clear.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/IPC/IPC.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/IPC/IPC_Addressing.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/IPC/IPC_Semaphore.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/IPC/IPC_Memory.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/Cli/Cli.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop/StreamLoop.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop/StreamLoop_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop/StreamLoop_Handler_Abstract.class.php');
include __DIR__.'/StreamLoop/StreamLoop_HTTPS_Const.class.php';
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop/StreamLoop_TCP_Abstract.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop/StreamLoop_HTTPS_Abstract.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop/StreamLoop_UDP_Abstract.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop/StreamLoop_UDP_Drain_Abstract.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop/StreamLoop_UDP_DrainForward_Abstract.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop/StreamLoop_UDP_DrainBackward_Abstract.class.php');
include __DIR__.'/StreamLoop/StreamLoop_WebSocket_Const.class.php';
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop/StreamLoop_WebSocket_Abstract.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/StreamLoop/StreamLoop_Timer_Abstract.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/Benchmark/Benchmark_Interface.interface.php');
ClassLoader::Get()->registerClass(__DIR__.'/Benchmark/Benchmark_Stub.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Benchmark/Benchmark.class.php');
