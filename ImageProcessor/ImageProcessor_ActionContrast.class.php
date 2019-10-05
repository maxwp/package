<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Action: change image contrast
 * Контрастность
 *
 * @package ImageProcessor
 * @copyright WebProduction
 * @author Max
 */
class ImageProcessor_ActionContrast extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        imagefilter($im, IMG_FILTER_CONTRAST, $this->_level);

        return $im;
    }

    /**
     * Contrast level
     * Уровень контрастности
     *
     * +255 increase / повысить
     * ..
     * +200 decrease / понизить
     *
     * @param int $level
     */
    public function __construct($level) {
        $this->_level = $level;
    }

    private $_level = 0;

}