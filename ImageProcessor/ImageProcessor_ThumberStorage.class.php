<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * ImageProcessor with Storage integration.
 * Alpha-version.
 *
 * ImageProcessor, основанный на взаимодействии с хранилищем Storage.
 * Созданный трумб автоматически подвязывается в хранилище.
 * Также есть возможность добавлять свои imageprocess'оры (например,
 * наложение watermarka, цветокоррекция и т.п.)
 *
 * @see Storage package
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 *
 * @copyright WebProduction
 *
 * @package ImageProcessor
 *
 * @subpackage Thumber
 */
class ImageProcessor_ThumberStorage {

    /**
     * Добавить post-action.
     * Он будет выполнен после создания thumb'a.
     * Например, "наложить watermark"
     *
     * @param ImageProcessor_Action $action
     */
    public function addImageProcessorAction(ImageProcessor_Action $action) {
        $this->_actionsArray[] = $action;
    }

    /**
     * Get
     *
     * @param Storage $storage
     *
     * @return ImageProcessor_ThumberStorage
     */
    public static function Get(Storage $storage) {
        return new self($storage);
    }

    /**
     * Сделать трумб картинки и записать его обратно в хранилище.
     *
     * @param string $storageKey
     * @param int $width
     * @param int $height
     * @param string $method
     *
     * @return string
     */
    public function makeThumb($storageKey, $width = false, $height = false, $method = false) {
        $filepath = $this->getStorage()->getData($storageKey);

        // задано условие сохранения
        $pathinfo = pathinfo($filepath);
        $ext = @$pathinfo['extension'];
        $dir = @$pathinfo['dirname'];

        if ($method != 'crop') {
            $method = 'prop';
        }

        // имя трумб-key построено
        $thumbKey = str_replace('.'.$ext, '.ipthumb'.$width.'x'.$height.$method.'.png', $storageKey);
        if ($thumbKey == $storageKey) {
            $thumbKey .= '.ipthumb'.$width.'x'.$height.$method.'.png';
        }




        // проверяем наличие файла в хранилище
        try {
            // файл есть
            $x = $this->getStorage()->getData($thumbKey);
        } catch (Storage_Exception $e) {
            // создаем трумб и запихиваем его в storage
            $thumbPath = str_replace('.'.$ext, '.ipthumb'.$width.'x'.$height.$method.'.png', $filepath);

            $thumbPath = str_replace('//', '/', $thumbPath);
            $thumbPath = str_replace('/media/shop/', '/media/thumb/', $thumbPath);

            $ip = new ImageProcessor($filepath);
            if ($method == 'crop') {
                $ip->addAction(new ImageProcessor_ActionResizeCrop($width, $height));
            } else {
                $ip->addAction(new ImageProcessor_ActionResizeProportional($width, $height));
            }

            // добавляем action-ы если они есть
            foreach ($this->_actionsArray as $action) {
                $ip->addAction($action);
            }

            $ip->addAction(new ImageProcessor_ActionToPNG($thumbPath));
            if (!$ip->process()) {
                return false;
            }

            $this->getStorage()->setData($thumbKey, $thumbPath, false, $storageKey);

            $x = $thumbPath;
        }

        $x = str_replace(PackageLoader::Get()->getProjectPath(), '/', $x);
        $x = str_replace('//', '/', $x);
        return $x;
    }

    /**
     * GetStorage
     *
     * @return Storage
     */
    public function getStorage() {
        return $this->_storage;
    }

    private function __construct(Storage $storage) {
        $this->_storage = $storage;
    }

    private function __clone() {

    }

    private $_storage;

    private $_actionsArray = array();

}