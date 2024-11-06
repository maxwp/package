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
class TextProcessor_ActionPregReplace implements TextProcessor_IAction {

    public function __construct($matchPattern, $replacePattern) {
        // @todo: check preg match
        $this->_matchPattern = $matchPattern;
        $this->_replacePattern = $replacePattern;
    }

    /**
     * @param string $text
     * @return string
     */
    public function process($text) {
        if (!preg_match($this->_matchPattern, $text)) {
        	return $text;
        }

        return preg_replace(
        $this->_matchPattern,
        $this->_replacePattern,
        $text);
    }

    private $_matchPattern;

    private $_replacePattern;

}