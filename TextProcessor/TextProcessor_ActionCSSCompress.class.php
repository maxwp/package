<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Remove \t, \r, \n, double+ spaces from CSS code
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */
class TextProcessor_ActionCSSCompress implements TextProcessor_IAction {

	public function process($text) {
        $text = str_replace(array("\r", "\n", "\t"), '', $text);
        $text = preg_replace("/(\s+){2,}/ius", ' ', $text);
        $text = trim($text);
        return $text;
	}

}