<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Abstract action class.
 * Абстрактный класс обработчика для изображения.
 *
 * @abstract
 * @copyright WebProduction
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @package ImageProcessor
 */
abstract class ImageProcessor_Action {

    /**
     * Run this action.
     * Выполнить обработку и вернуть результат
     *
     * @return resource
     */
    abstract public function process();

    /**
     * Set image resource.
     * Задать ресурс изображения
     *
     * @param resource $image
     */
    public function setImageResource($image) {
        $this->_image = $image;
    }

    /**
     * Get image resource.
     * Получить ресурс изображения
     *
     * @return resource
     */
    public function getImageResource() {
        return $this->_image;
    }

    /**
     * Set file path
     * Задать путь к файлу
     *
     * @param string $filename
     */
    public function setImageFilename($filename) {
        $this->_filename = $filename;
    }

    /**
     * Get file path.
     * Warning! Method will works only in process() method.
     *
     * Получить полный путь файла с изображением.
     *
     * Внимание! Метод будет работать только при вызове из метода process(),
     * так как ранее изображение не определено!
     *
     * @return string
     */
    public function getImageFilename() {
        return $this->_filename;
    }

    /**
     * Get filename extension (without dot).
     *
     * По имени файла получить расширение файла.
     * Возвращает расширение БЕЗ точки в начале
     *
     * @see getImageFilename()
     * @return string
     */
    public function getImageFileExtension() {
        $f = $this->getImageFilename();
        if (!$f) {
            return false;
        }
        return pathinfo($f, PATHINFO_EXTENSION);
    }

    /**
     * Get image width.
     *
     * Получить ширину картинки.
     * Внимание! Метод будет работать только при вызове из метода process(),
     * так как ранее изображение не определено!
     *
     * @return int
     */
    public function getImageWidth() {
        return imagesx($this->getImageResource());
    }

    /**
     * Get image height.
     *
     * Получить высоту картинки.
     * Внимание! Метод будет работать только при вызове из метода process(),
     * так как ранее изображение не определено!
     *
     * @return int
     */
    public function getImageHeight() {
        return imagesy($this->getImageResource());
    }

    private $_image;

    private $_filename;

}