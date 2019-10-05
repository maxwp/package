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
 * Обработчик BB-код тега color
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */
class TextProcessor_ActionBBCodeColor implements TextProcessor_IAction {

    public function process($text) {
        $matchArray[] = array(
        'search' => "/\[color=([#A-Za-z0-9]+?)\](.*?)\[\/color\]/si",
        'replace' => '<font color="\1">\2</font>',
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