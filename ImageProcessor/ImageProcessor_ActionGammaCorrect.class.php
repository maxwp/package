<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Action: gamma-correct
 * Гамма-коррекция изображения
 *
 * @package ImageProcessor
 * @copyright WebProduction
 * @author Max
 */
class ImageProcessor_ActionGammaCorrect extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        imagegammacorrect($im, $this->_inputgamma, $this->_outputgamma);

        return $im;
    }

    /**
     * Image gamma correct
     * input gamma - by default is 1.0
     *
     * @param float $inputGamma
     * @param float $outputGamma
     */
    public function __construct($inputGamma, $outputGamma) {
        $this->_inputgamma = $inputGamma;
        $this->_outputgamma = $outputGamma;
    }

    private $_inputgamma;

    private $_outputgamma;

}