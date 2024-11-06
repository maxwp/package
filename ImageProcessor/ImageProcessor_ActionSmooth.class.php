<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Action: smooth image
 * Смазать (аналогия размыть) с указанной степенью
 *
 * @package ImageProcessor
 * @copyright WebProduction
 * @author Max
 */
class ImageProcessor_ActionSmooth extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        imagefilter($im, IMG_FILTER_SMOOTH, $this->_level);

        return $im;
    }

    /**
     * @param int $level Smooth level
     */
    public function __construct($level) {
        $this->_level = $level;
    }

    private $_level = 0;

}