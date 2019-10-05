<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
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