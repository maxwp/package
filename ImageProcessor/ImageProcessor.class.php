<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Bulk image processor.
 * Пакетный процессор (обработчик) изображений.
 *
 * @author    Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package   ImageProcessor
 */
class ImageProcessor {

    /**
     * Create processor by file (path)
     * Создать процессор для файла
     *
     * @param string $filename
     */
    public function __construct($filename = false) {
        if ($filename) {
            $this->setFilename($filename);
        }
    }

    /**
     * Set file name
     *
     * @param string $filename
     */
    public function setFilename($filename) {
        if (!$filename) {
            throw new Exception('No filename specified', 0);
        }

        if (file_exists($filename)) {
            $this->_filename = $filename;
        }
    }

    /**
     * Get file name
     *
     * @return string
     */
    public function getFilename() {
        return $this->_filename;
    }

    /**
     * Get file extension by file name.
     * Without dot at start (ex, png, jpg)
     *
     * По имени файла получить расширение файла.
     * Возвращает расширение БЕЗ точки в начале
     *
     * @see getFilename()
     *
     * @return string
     */
    public function getFileExtension() {
        $f = $this->getFilename();
        if (!$f) {
            return false;
        }
        return pathinfo($f, PATHINFO_EXTENSION);
    }

    /**
     * Add new action to processor.
     * Добавляем фильтр-обработчик.
     *
     * @param ImageProcessor_Action $actionObject
     */
    public function addAction(ImageProcessor_Action $actionObject) {
        $this->_actionsArray[] = $actionObject;
    }

    /**
     * Run all actions.
     * Выполнить все фильтры.
     *
     * @return mixed
     */
    public function process() {
        if ($this->_actionsArray) {
            // есть фильтры, проганяем по очереди
            $src = $this->getFilename();

            $list = @getimagesize($src);

            if (empty($list)) {
                return false;
            }

            if ($list[2] == 1) {
                $im = @imagecreatefromgif($src);
            } elseif ($list[2] == 2) {
                $im = @imagecreatefromjpeg($src);
            } elseif ($list[2] == 3) {
                $im = @imagecreatefrompng($src);
                imagesavealpha($im, true);
            } elseif ($list[2] == 6) {
                $im = $this->_imageCreateFromBMP($src);
            } else {
                return false;
            }

            if (!$im) {
            	return false;
            }

            foreach ($this->_actionsArray as $actionObject) {
                // задаем парметры action'a
                $actionObject->setImageResource($im);
                $actionObject->setImageFilename($src);

                // прогоняем через action
                $im = $actionObject->process();
            }

            // возвращаем изображение как есть
            return $im;
        } else {
            // нет фильтров - ничего не делаем
            return $this->getFilename();
        }
    }

    /**
     * Read windows BMP format.
     * Internal private function.
     *
     * @param string $filename
     * @return resource
     */
    private function _imageCreateFromBMP($filename) {
        if (! $f1 = fopen($filename, "rb")) {
            return false;
        }

        // 1 : Chargement des entites FICHIER
        $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1, 14));
        if ($FILE['file_type'] != 19778) return false;

        // 2 : Chargement des entites BMP
        $BMP = unpack(
            'Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel/Vcompression/Vsize_bitmap/Vhoriz_resolution/'
            . 'Vvert_resolution/Vcolors_used/Vcolors_important',
            fread($f1, 40)
        );
        $BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
        if ($BMP['size_bitmap'] == 0) $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
        $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
        $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
        $BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] -= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] = 4 - (4 * $BMP['decal']);
        if ($BMP['decal'] == 4) $BMP['decal'] = 0;

        //3 : Chargement des couleurs de la palette
        $PALETTE = array();
        if ($BMP['colors'] < 16777216) {
            $PALETTE = unpack('V' . $BMP['colors'], fread($f1, $BMP['colors'] * 4));
        }

        // 4 : Creation de l'image
        $IMG = fread($f1, $BMP['size_bitmap']);
        $VIDE = chr(0);

        $res = imagecreatetruecolor($BMP['width'],$BMP['height']);
        $P = 0;
        $Y = $BMP['height']-1;
        while ($Y >= 0) {
            $X = 0;
            while ($X < $BMP['width']) {
                if ($BMP['bits_per_pixel'] == 24) {
                    $COLOR = unpack("V", substr($IMG, $P, 3) . $VIDE);
                } elseif ($BMP['bits_per_pixel'] == 16) {
                    $COLOR = unpack("n", substr($IMG, $P, 2));
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 8) {
                    $COLOR = unpack("n", $VIDE . substr($IMG, $P, 1));
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 4) {
                    $COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
                    if (($P * 2) % 2 == 0) $COLOR[1] = ($COLOR[1] >> 4); else $COLOR[1] = ($COLOR[1] & 0x0F);
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 1) {
                    $COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
                    if (($P * 8) % 8 == 0) $COLOR[1] = $COLOR[1] >> 7;
                    elseif (($P * 8) % 8 == 1) $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
                    elseif (($P * 8) % 8 == 2) $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
                    elseif (($P * 8) % 8 == 3) $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
                    elseif (($P * 8) % 8 == 4) $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
                    elseif (($P * 8) % 8 == 5) $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
                    elseif (($P * 8) % 8 == 6) $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
                    elseif (($P * 8) % 8 == 7) $COLOR[1] = ($COLOR[1] & 0x1);
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } else {
                    return false;
                }
                imagesetpixel($res, $X, $Y, $COLOR[1]);
                $X++;
                $P += $BMP['bytes_per_pixel'];
            }
            $Y--;
            $P+=$BMP['decal'];
        }
        fclose($f1);

        return $res;
    }

    private $_filename = false;

    private $_actionsArray = false;

}