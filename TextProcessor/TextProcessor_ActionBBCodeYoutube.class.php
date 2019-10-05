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
 * Обработчик BB-код тега youtube
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */
class TextProcessor_ActionBBCodeYoutube implements TextProcessor_IAction {

    private $_width;

    private $_height;

    private $_type;

    public function __construct($width = 425, $height = 344, $type = 'iframe') {
        if ($width <= 0) {
            throw new TextProcessor_Exception("Invalid insertion width '{$width}' in ".__CLASS__);
        }
        if ($height <= 0) {
            throw new TextProcessor_Exception("Invalid insertion height '{$height}' in ".__CLASS__);
        }
        if ($type != 'iframe' && $type != 'flash') {
            throw new TextProcessor_Exception("Invalid insertion type '{$type}' in ".__CLASS__);
        }

        $this->_width = $width;
        $this->_height = $height;
        $this->_type = $type;
    }

    public function process($text) {
        if ($this->_type == 'iframe') {
            $replace = '<iframe width="'.$this->_width.'" height="'.$this->_height.'" src="http://www.youtube.com/embed/\1" frameborder="0" allowfullscreen></iframe>';
        } elseif ($this->_type == 'flash') {
            $replace = '<object width="'.$this->_width.'" height="'.$this->_height.'"><param name="movie" value="http://www.youtube.com/v/\1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/\1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="'.$this->_width.'" height="'.$this->_height.'"></embed></object>';
        }

        $matchArray[] = array(
        'search' => "/\[youtube\](.*?)\[\/youtube\]/si",
        'replace' => $replace,
        );

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