<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Action: Add PNG-watermark to image.
 *
 * Action для ImageProcessor'а позволяющий накладывать водяной знак
 * (PNG-watermark) на изображение.
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package ImageProcessor
 */
class ImageProcessor_ActionWatermarkPNG extends ImageProcessor_Action {

    /**
     * Add PNG-watermark to image.
     *
     * x/y can be integer coordinates or left/right/top/center
     *
     * @param string $pathWatermark path to file watermark (PNG)
     * @param mixed $x
     * @param mixed $y
     */
    public function __construct($fileWatermark, $x = 'right', $y = 'bottom') {
        if (!is_file($fileWatermark)) {
            throw new ImageProcessor_Exception("Path '{$fileWatermark}' is not a file");
        }

        $this->_watermarkFile = $fileWatermark;
        $this->_x = $x;
        $this->_y = $y;
    }

    /**
     * Get image watermark resource
     * Получить ресурс watermark-картинки
     *
     * @return resource
     */
    private function _getImageWatermark() {
        if ($this->_watermarkImage) {
            return $this->_watermarkImage;
        }

        $this->_watermarkImage = imagecreatefrompng($this->_watermarkFile);
        if (!$this->_watermarkImage) {
            throw new ImageProcessor_Exception("Invalid watermark PNG file");
        }
        return $this->_watermarkImage;
    }

    /**
     * Run this action
     * Выполнить обработку
     *
     * @return string
     */
    public function process() {
        // ресурс водяного знака
        $watermark = $this->_getImageWatermark();

        // ресурс картинки
        $image = $this->getImageResource();

        // получаем размеры оригинального изображения
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        // получаем размеры watermark'a
        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);

        $proportionalSize = SettingService::Get()->getSettingValue('watermark-proportion-size');

        if (!$proportionalSize) {
            $proportionalSize = 5; // отношение размера картинки к размеру вотермарки
        }

        $newWatermarkWidth = $imageWidth/$proportionalSize;

        $newWatermarkHeight = $watermarkHeight * $newWatermarkWidth/$watermarkWidth;

        // вычисляем координаты watermark'a
        if ($this->_x == 'left') {
            // водяной знак будет слева
            $x = 0;
        } elseif ($this->_x == 'center') {
            $x = ($imageWidth - $newWatermarkWidth) / 2;
            if ($x <= 0) {
                $x = 0;
            }
        } elseif ($this->_x == 'right') {
            $x = $imageWidth - $newWatermarkWidth;
            if ($x <= 0) {
                $x = 0;
            }
        } else {
            $x = $this->_x;
        }

        if ($this->_y == 'top') {
            $y = 0;
        } elseif ($this->_y == 'center') {
            $y = ($imageHeight - $newWatermarkHeight) / 2;
            if ($y <= 0) {
                $y = 0;
            }
        } elseif ($this->_y == 'bottom') {
            $y = $imageHeight - $newWatermarkHeight;
            if ($y <= 0) {
                $y = 0;
            }
        } else {
            $y = $this->_y;
        }


        // накладываем watermark
        imagecopyresampled(
        $image, $watermark,
        $x, $y, // куда
        0, 0, // откуда
        $newWatermarkWidth, $newWatermarkHeight, // размер watermark'a на оригинале
        $watermarkWidth, $watermarkHeight // размер watermark'a на картинке
        );

        return $image;
    }

    private $_watermarkFile;

    private $_watermarkImage = false;

    private $_x;

    private $_y;

}