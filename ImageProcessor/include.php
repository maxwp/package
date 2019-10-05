<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * @copyright WebProduction
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @package ImageProcessor
 */

if (class_exists('PackageLoader')) {
    PackageLoader::Get()->registerPHPDirectory(__DIR__);
} else {
    include_once(__DIR__.'/ImageProcessor.class.php');
    include_once(__DIR__.'/ImageProcessor_Action.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionToPNG.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionToJPEG.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionResizeCrop.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionResizeProportional.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionBlurGaussian.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionNegate.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionGrayscale.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionBrightness.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionContrast.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionColorize.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionEdgeDetect.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionEmboss.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionSmooth.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionPixelate.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionSharpen.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionRoundCorners.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionGammaCorrect.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionCut.class.php');

    include_once(__DIR__.'/ImageProcessor_Thumber.class.php');
    include_once(__DIR__.'/ImageProcessor_ThumberStorage.class.php');
    include_once(__DIR__.'/ImageProcessor_Exception.class.php');
    include_once(__DIR__.'/ImageProcessor_ActionWatermarkPNG.class.php');
}