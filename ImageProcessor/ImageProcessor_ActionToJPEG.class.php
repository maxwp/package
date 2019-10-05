<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Action: save image to JPEG-file
 * Сохранить изображение в JPEG-файл
 *
 * @package ImageProcessor
 *
 * @copyright WebProduction
 *
 * @author Max
 */
class ImageProcessor_ActionToJPEG extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        $pattern = $this->_pathPattern;
        $pathinfo = pathinfo($this->getImageFilename());
        $ext = @$pathinfo['extension'];
        $dir = @$pathinfo['dirname'];
        $name = rtrim(@$pathinfo['basename'], $ext);

        $pattern = str_replace('{name}', $name, $pattern);
        $pattern = str_replace('{extension}', $ext, $pattern);
        $pattern = str_replace('{directory}', $dir, $pattern);

        imagejpeg($im, $pattern, $this->_quality);

        return $im;
    }

    /**
     * Save image to JPEG-file.
     * $pathPattern - path to save.
     *
     * Quality - is JPEG quality (1..100)
     *
     * Сохранить картинку в формате JPEG
     * $pathPattern задает пусть к сохраняемому файлу,
     * либо шаблон сохранения
     *
     * @param string $pathPattern
     * @param int $quality
     */
    public function __construct($pathPattern = false, $quality = 100) {
        $this->_pathPattern = $pathPattern;
        $this->_quality = $quality;
    }

    private $_pathPattern = false;

    private $_quality = 100;

}