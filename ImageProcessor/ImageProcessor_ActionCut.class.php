<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Action: Cut fragment from image
 * Вырезать из изображения заданный фрагмент
 *
 * @package ImageProcessor
 * @copyright WebProduction
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 */
class ImageProcessor_ActionCut extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        $cutted = imagecreatetruecolor($this->_width, $this->_height);
        imagecopyresampled($cutted, $im, 0, 0, $this->_x, $this->_y, $this->_width, $this->_height, $this->_width, $this->_height);

        return $cutted;
    }

    /**
     * Cut fragment from image
     *
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     */
    public function __construct($x, $y, $width, $height) {
    	$this->_x = $x;
    	$this->_y = $y;
    	$this->_width = $width;
    	$this->_height = $height;
    }

    private $_x;

    private $_y;

    private $_height;

    private $_width;

}