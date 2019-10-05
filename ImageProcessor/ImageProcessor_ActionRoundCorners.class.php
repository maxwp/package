<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Action: round image corners
 * Скгрулить углы изображения
 *
 * @package ImageProcessor
 * @copyright WebProduction
 * @author Max
 */
class ImageProcessor_ActionRoundCorners extends ImageProcessor_Action {

    public function process() {
        $im = $this->getImageResource();

        $imageWidth = imagesx($im);
        $imageHeight = imagesy($im);

        foreach ($this->_cornersArray as $corner => $radius) {
            if ($corner == 'topleft') {
                $this->addRound($radius, 0, 0, 0, 0, $radius, $radius);
            }
            if ($corner == 'topright') {
                $this->addRound($radius, $imageWidth - $radius, 0, $radius, 0, $radius, $radius);
            }
            if ($corner == 'bottomleft') {
                $this->addRound($radius, 0, $imageHeight - $radius, 0, $radius, $radius, $radius);
            }
            if ($corner == 'bottomright') {
                $this->addRound($radius, $imageWidth - $radius, $imageHeight - $radius, $radius, $radius, $radius, $radius);
            }
        }

        if (!$this->_roundsArray) {
            return $im;
        }

        imagealphablending($im, false);
        imagesavealpha($im, true);

        foreach ($this->_roundsArray as $corner) {
            // создаем кружок
            $round = imagecreatetruecolor($corner['radius']*2, $corner['radius']*2);
            imagealphablending($round, false);

            $bgcolor = imagecolorallocate($round, 0, 0, 0);
            $trcolor = imagecolorallocatealpha($round, 255, 255, 255, 127);
            imagefill($round, 0, 0, $bgcolor);
            imagefilledellipse($round, $corner['radius'], $corner['radius'], $corner['radius']*2, $corner['radius']*2, $trcolor);

            $trcolor2 = imagecolorallocatealpha($im, 255, 255, 255, 127);
            for ($x = $corner['roundx']; $x < $corner['roundx'] + $corner['roundw']; $x++) {
                for ($y = $corner['roundy']; $y < $corner['roundy'] + $corner['roundh']; $y++) {
                    if (imagecolorat($round, $x, $y) == $bgcolor) {
                        imagesetpixel($im, $corner['x'] + $x - $corner['roundx'], $corner['y'] + $y - $corner['roundy'], $trcolor2);
                    }
                }
            }
        }

        return $im;
    }

    /**
     * Add round (manual mode)
     * Добавить скругление (ручной режим)
     *
     * @param int $radius Радиус скругления
     * @param int $x X-координата куда ставить круг скругления
     * @param int $y Y-координата куда ставить круг скругления
     * @param int $roundX Откуда начинать копировать круг скругления
     * @param int $roundY Откуда начинать копировать круг скругления
     * @param int $roundWidth Ширина копирования круга
     * @param int $roundHeight Высота копирования круга
     */
    public function addRound($radius, $x, $y, $roundX, $roundY, $roundWidth, $roundHeight) {
        $this->_roundsArray[] = array(
        'radius' => $radius,
        'x' => $x,
        'y' => $y,
        'roundx' => $roundX,
        'roundy' => $roundY,
        'roundw' => $roundWidth,
        'roundh' => $roundHeight,
        );
    }

    /**
     * Скруглить верхний левый угол
     *
     * @param int $radius
     */
    public function roundCornerTopLeft($radius) {
        $this->_cornersArray['topleft'] = $radius;
    }

    /**
     * Скруглить верхний правый угол
     *
     * @param int $radius
     */
    public function roundCornerTopRight($radius) {
        $this->_cornersArray['topright'] = $radius;
    }

    /**
     * Скруглить нижний левый угол
     *
     * @param int $radius
     */
    public function roundCornerBottomLeft($radius) {
        $this->_cornersArray['bottomleft'] = $radius;
    }

    /**
     * Скруглить нижний правый угол
     *
     * @param int $radius
     */
    public function roundCornerBottomRight($radius) {
        $this->_cornersArray['bottomright'] = $radius;
    }

    /**
     * Скруглить все углы
     *
     * @param int $radius
     */
    public function roundCornersAll($radius) {
        $this->roundCornerTopLeft($radius);
        $this->roundCornerTopRight($radius);
        $this->roundCornerBottomLeft($radius);
        $this->roundCornerBottomRight($radius);
    }

    private $_roundsArray = array();

    private $_cornersArray = array();

}