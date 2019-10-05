<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Action: sharpen image
 * Применить эффект "sharpen" (пошарпать)
 *
 * @package ImageProcessor
 * @copyright WebProduction
 * @author Max
 */
class ImageProcessor_ActionSharpen extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        imageconvolution($im, array(array(0, -1, 0), array(-1, 5, -1), array(0, -1, 0)), 1, 0);

        return $im;
    }

}