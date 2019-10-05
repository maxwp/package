<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * ImageProcessor utility class.
 * Automatically create image thumbs
 *
 * Класс для автоматического создание thumbnail's файлов для картинок
 * различного формата. Построен на основе ImageProcessor'a
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 *
 * @copyright WebProduction
 *
 * @package ImageProcessor
 */
class ImageProcessor_Thumber {

    /**
     * Create image thumb by parameters and return path to file thumb
     *
     * @param string $filepath
     * @param int $width
     * @param int $height
     *
     * @return string
     */
    public static function MakeThumbProportional($filepath, $width = false, $height = false, $format = 'png') {
        // задано условие сохранения
        $pathinfo = pathinfo($filepath);
        $ext = @$pathinfo['extension'];
        if ($ext) {
            $thumbPath = str_replace('.'.$ext, '.ipthumb'.$width.'x'.$height.'prop.'.$format, $filepath);
        } else {
            $thumbPath = $filepath.'.ipthumb'.$width.'x'.$height.'prop.'.$format;
        }

        $thumbPath = str_replace('//', '/', $thumbPath);
        $thumbPath = str_replace('/media/shop/', '/media/thumb/', $thumbPath);

        if (!file_exists($thumbPath)) {
            $ip = new ImageProcessor($filepath);
            $ip->addAction(new ImageProcessor_ActionResizeProportional($width, $height));

            if ($format == 'png') {
                $ip->addAction(new ImageProcessor_ActionToPNG($thumbPath));
            } else {
                $ip->addAction(new ImageProcessor_ActionToJPEG($thumbPath));
            }

            if (!$ip->process()) {
                return false;
            }
        }

        return $thumbPath;
    }

    /**
     * Create image thumb by parameters and return path to file thumb
     *
     * @param string $filepath
     * @param int $width
     * @param int $height
     *
     * @return string
     */
    public static function MakeThumbCrop($filepath, $width = false, $height = false, $format = 'png') {
        // задано условие сохранения
        $pathinfo = pathinfo($filepath);
        $ext = @$pathinfo['extension'];
        if ($ext) {
            $thumbPath = str_replace('.'.$ext, '.ipthumb'.$width.'x'.$height.'crop.'.$format, $filepath);
        } else {
            $thumbPath = $filepath.'.ipthumb'.$width.'x'.$height.'crop.'.$format;
        }

        $thumbPath = str_replace('//', '/', $thumbPath);
        $thumbPath = str_replace('/media/shop/', '/media/thumb/', $thumbPath);

        if (!file_exists($thumbPath)) {
            $ip = new ImageProcessor($filepath);
            $ip->addAction(new ImageProcessor_ActionResizeCrop($width, $height));

            if ($format == 'png') {
                $ip->addAction(new ImageProcessor_ActionToPNG($thumbPath));
            } else {
                $ip->addAction(new ImageProcessor_ActionToJPEG($thumbPath));
            }

            if (!$ip->process()) {
                return false;
            }
        }

        return $thumbPath;
    }

    /**
     * Create image thumb by parameters and return path to file thumb
     *
     * @param string $filepath Полный путь к файлу
     * @param mixed $width Желаяемая ширина
     * @param mixed $height Желаемая высота
     * @param string $method Желаемый метод обрезки
     * @param string $projectPath Начало абсолютного пути, которое стоит убрать
     *
     * @return string
     *
     * @access static
     */
    public static function MakeThumbUniversal($filepath, $width = false, $height = false,
                                              $method = false, $projectPath = false, $format = 'png'
    ) {
        if ($method == 'crop') {
            $x = self::MakeThumbCrop($filepath, $width, $height, $format);
        } else {
            $x = self::MakeThumbProportional($filepath, $width, $height, $format);
        }
        if ($projectPath) {
            $x = str_replace($projectPath, '/', $x);
            $x = str_replace('//', '/', $x);
        }
        return $x;
    }

    /**
     * Remive all thumbs for filepath.
     * Method returns count of deleted thumb-files
     *
     * Удалить все thumb-ы для файла $filepath
     * которые построил ImageProcessor
     * (удаляются файлы "рядом" с маской ipthumb
     *
     * @param string $filepath
     *
     * @return int Вернет количество удаленных thumb-файлов
     */
    public static function DeleteThumbs($filepath) {
        $pathinfo = pathinfo($filepath);
        $ext = @$pathinfo['extension'];
        $dir = @$pathinfo['dirname'].'/';
        $basename = @$pathinfo['basename'];
        $thumbMatch = str_replace('.'.$ext, '.ipthumb', $basename);

        $deleted = 0;

        $d = opendir($dir);
        while ($x = readdir($d)) {
            if (preg_match("/^$thumbMatch/is", $x)) {
                @unlink($dir.$x);
                $deleted++;
            }
        }
        closedir($d);

        return $deleted;
    }

}