<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Action: emboss
 * Давление изображения (реальефность)
 *
 * @package ImageProcessor
 * @copyright WebProduction
 * @author Max
 */
class ImageProcessor_ActionEmboss extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        imagefilter($im, IMG_FILTER_EMBOSS);

        return $im;
    }

}