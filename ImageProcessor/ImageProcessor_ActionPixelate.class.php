<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Action: pixelize image
 * Пикселизировать изображение
 *
 * @package ImageProcessor
 * @copyright WebProduction
 * @author Max
 */
class ImageProcessor_ActionPixelate extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        imagefilter($im, IMG_FILTER_PIXELATE, $this->_blocksize, $this->_advanced);

        return $im;
    }

    /**
     * Pixelate effect.
     *
     * @param int $blocksize block size in px
     * @param bool $advanced Advanced mone
     */
    public function __construct($blocksize, $advanced = false) {
        $this->_blocksize = $blocksize;
        $this->_advanced = $advanced;
    }

    private $_blocksize = 0;

    private $_advanced = 0;

}