<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Action: edge detect
 * Выделение артефактов изображения
 *
 * @package ImageProcessor
 * @copyright WebProduction
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 */
class ImageProcessor_ActionEdgeDetect extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        imagefilter($im, IMG_FILTER_EDGEDETECT);

        return $im;
    }

}