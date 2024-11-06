<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
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