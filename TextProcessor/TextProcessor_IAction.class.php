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
interface TextProcessor_IAction {

    /**
     * Выполнить обработку
     *
     * @param string $text
     * @return string
     */
	public function process($text);

}