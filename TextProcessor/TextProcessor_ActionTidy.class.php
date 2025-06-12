<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * HTML-Tidy text processor action.
 * Need php-tidy extension.
 */
class TextProcessor_ActionTidy implements TextProcessor_IAction {

    public function __construct($compressConfig = false) {
        if (!function_exists('tidy_parse_string')) {
            throw new TextProcessor_Exception(
            "TextProcessor_ActionTidy needs php-tidy extension"
            );
        }

        if (!$compressConfig) {
            $compressConfig = array(
            'clean' => false,
            'output-xhtml' => true,
            'hide-comments' => true,
            'tidy-mark' => false,
            );
        }
        $this->_compressConfig = $compressConfig;
    }

    /**
     * @param string $text
     * @return string
     */
    public function process($text) {
        $tidy = tidy_parse_string(
            $text,
            $this->_compressConfig,
            'UTF8'
        );

        $tidy->cleanRepair();
        return (string) $tidy;
    }

    private $_compressConfig;

}