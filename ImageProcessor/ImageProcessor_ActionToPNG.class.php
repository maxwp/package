<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Action: save image to PNG
 * Сохранить изображение в PNG
 *
 * @package ImageProcessor
 * @copyright WebProduction
 * @author Max
 */
class ImageProcessor_ActionToPNG extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        $pattern = $this->_pathPattern;
        // задано условие сохранения
        $pathinfo = pathinfo($this->getImageFilename());
        $ext = @$pathinfo['extension'];
        $dir = @$pathinfo['dirname'];
        $name = rtrim(@$pathinfo['basename'], $ext);

        $pattern = str_replace('{name}', $name, $pattern);
        $pattern = str_replace('{extension}', $ext, $pattern);
        $pattern = str_replace('{directory}', $dir, $pattern);

        imagepng($im, $pattern);

        return $im;
    }

    /**
     * Save image to PNG-file.
     * $pathPattern - path to save.
     *
     * Сохранить картинку в формате PNG
     * $pathPattern задает пусть к сохраняемому файлу,
     * либо шаблон сохранения
     *
     * @param string $pathPattern
     */
    public function __construct($pathPattern) {
        $this->_pathPattern = $pathPattern;
    }

    private $_pathPattern = false;

}