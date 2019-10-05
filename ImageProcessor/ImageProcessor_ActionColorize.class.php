<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Action: colorize image.
 *
 * Оцветнение изображения (colorize)
 *
 * @package ImageProcessor
 * @copyright WebProduction
 * @author Max
 */
class ImageProcessor_ActionColorize extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        imagefilter($im, IMG_FILTER_COLORIZE, $this->_r, $this->_g, $this->_b);

        return $im;
    }

    /**
     * RGB from 0 to 255
     * RGB составляющие 0..255
     *
     * @param int $r
     * @param int $g
     * @param int $b
     */
    public function __construct($r, $g, $b) {
        $this->_r = $r;
        $this->_g = $g;
        $this->_b = $b;
    }

    private $_r = 0;

    private $_g = 0;

    private $_b = 0;

}