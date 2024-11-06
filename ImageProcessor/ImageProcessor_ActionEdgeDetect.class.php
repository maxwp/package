<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
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