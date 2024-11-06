<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * @copyright WebProduction
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @package ImageProcessor
 */

ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_Action.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionToPNG.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionToJPEG.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionResizeCrop.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionResizeProportional.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionBlurGaussian.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionNegate.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionGrayscale.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionBrightness.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionContrast.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionColorize.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionEdgeDetect.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionEmboss.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionSmooth.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionPixelate.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionSharpen.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionRoundCorners.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionGammaCorrect.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionCut.class.php');

ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_Thumber.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ThumberStorage.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/ImageProcessor_ActionWatermarkPNG.class.php');