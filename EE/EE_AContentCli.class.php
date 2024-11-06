<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Content for CLI
 */
abstract class EE_AContentCli extends EE_AContent implements EE_IContent {

    public function print($s) {
        if (defined('EE_PRINT')) {
            print $s;
        }
    }

}