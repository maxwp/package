<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Action: increase or decrase image brightness
 * Контроль яркости изрображения
 *
 * @package ImageProcessor
 * @copyright WebProduction
 * @author Max
 */
class ImageProcessor_ActionBrightness extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        imagefilter($im, IMG_FILTER_BRIGHTNESS, $this->_level);

        return $im;
    }

    /**
     * +127 - maximum (максимум)
     *   0  - not change (не менять)
     * -127 - minimum (минимум)
     *
     * @param int $level
     */
    public function __construct($level) {
        $this->_level = $level;
    }

    private $_level = 0;

}