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

    public function print_n($s = '') {
        if (defined('EE_PRINT')) {
            print $s.PHP_EOL;
        }
    }

    public function print_t($s) {
        if (defined('EE_PRINT')) {
            print $s."\t";
        }
    }

    public function print_r($a) {
        if (defined('EE_PRINT')) {
            print_r($a);
        }
    }

    // @todo print_e (callback)
    // @todo print_s (sprintf)

}