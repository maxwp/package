<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */
class TextProcessor_ActionPregMatch implements TextProcessor_IAction {

    public function __construct($pregPattern) {
        $this->_pregPattern = $pregPattern;
        // @todo: check preg match
    }

	/**
     * @param string $text
     * @return string
     */
	public function process($text) {
        if (preg_match($this->_pregPattern, $text, $r)) {
        	return $r[1];
        }
        return '';
	}

	private $_pregPattern;

}