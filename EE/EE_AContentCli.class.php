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
            print "$s\n";
        }
    }

    public function print_t($s) {
        if (defined('EE_PRINT')) {
            print "$s\t";
        }
    }

    public function print_r($a) {
        if (defined('EE_PRINT')) {
            print_r($a);
        }
    }

    public function print_e($callback) {
        if (defined('EE_PRINT')) {
            print $callback();
        }
    }

    public function print_f($s, $format, $eol = ' ') {
        if (defined('EE_PRINT')) {
            if (substr_count($format, '%')) {
                print sprintf($format, $s) . $eol;
            } else {
                print sprintf('%1$' . $format, $s) . $eol;
            }
        }
    }

}