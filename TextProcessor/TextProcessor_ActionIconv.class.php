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
 * iconv() action processor
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */
class TextProcessor_ActionIconv implements TextProcessor_IAction {

    public function __construct($from, $to) {
        if (!function_exists('iconv')) {
        	throw new TextProcessor_Exception(
        	"TextProcessor_ActionIconv needs php-extension iconv"
        	);
        }

        $this->_from = $from;
        $this->_to = $to;
    }

	/**
     * @param string $text
     * @return string
     */
	public function process($text) {
        return iconv($this->_from, $this->_to, $text.'');
	}

	private $_from;

	private $_to;

}