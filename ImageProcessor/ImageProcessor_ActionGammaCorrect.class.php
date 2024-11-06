<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
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