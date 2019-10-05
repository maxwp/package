<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Action: change image size to proportions and crop tail.
 *
 * Изменить размер изображения до заданных пропорций.
 * Все что не влазит - обрезать.
 *
 * @package ImageProcessor
 * @copyright WebProduction
 * @author Max
 */
class ImageProcessor_ActionResizeCrop extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        // получаем размеры оригинального изображения
        $w = imagesx($im);
        $h = imagesy($im);

        // определяем коеффициент
        $mul = min($w/$this->_width, $h/$this->_height);

        // вычисляем размеры области, которую будем копировать в обрезанное изображение
        $width_o = $this->_width*$mul;
        $height_o = $this->_height*$mul;

        // создаем новое изображение и заливаем его белым
        $image = imagecreatetruecolor($this->_width, $this->_height);
        imagefill($image, 0, 0, 0xffffff);

        // копируем и пережимаем
        imagecopyresampled($image, $im, 0, 0, 0, 0, $this->_width, $this->_height, $width_o, $height_o);

        return $image;
    }

    /**
     * Resize and crop image
     *
     * @param int $width
     * @param int $height
     */
    public function __construct($width, $height) {
        if (!$width || !$height) {
        	throw new Exception('width or height not implemented', 0);
        }

        $this->_width = $width;
        $this->_height = $height;
    }

    private $_width;

    private $_height;

}