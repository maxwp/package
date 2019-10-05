<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2011 WebProduction <webproduction.com.ua>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU LESSER GENERAL PUBLIC LICENSE
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

/**
 * Обработчик BB-код тега rutube
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */
class TextProcessor_ActionBBCodeRutube implements TextProcessor_IAction {

    private $_width;

    private $_height;

    public function __construct($width = 425, $height = 344) {
        if ($width <= 0) {
            throw new TextProcessor_Exception("Invalid insertion width '{$width}' in ".__CLASS__);
        }
        if ($height <= 0) {
            throw new TextProcessor_Exception("Invalid insertion height '{$height}' in ".__CLASS__);
        }

        $this->_width = $width;
        $this->_height = $height;
    }

    public function process($text) {
        $matchArray[] = array(
        'search' => "/\[rutube\](.*?)\[\/rutube\]/si",
        'replace' => '<OBJECT width="'.$this->_width.'" height="'.$this->_height.'"><PARAM name="movie" value="http://video.rutube.ru/\1"></PARAM><PARAM name="wmode" value="window"></PARAM><PARAM name="allowFullScreen" value="true"></PARAM><PARAM name="flashVars" value="uid=3029015"></PARAM><EMBED src="http://video.rutube.ru/\1" type="application/x-shockwave-flash" wmode="window" width="'.$this->_width.'" height="'.$this->_height.'" allowFullScreen="true" flashVars="uid=3029015"></EMBED></OBJECT>',
        );

        // @todo: wtf uid in link?

        foreach ($matchArray as $match) {
            $textOld = false;
            while ($text != $textOld) {
                $textOld = $text;
                $text = preg_replace($match['search'], $match['replace'], $text);
            }
        }

        return $text;
    }

}