<?php
class EE_Print {

    public static function print($s) {
        if (defined('EE_PRINT')) {
            print (string) $s; // это нужно для типизации в string, потому что я могу передать объект типа DateTime_Object
        }
    }

    public static function print_n($s = '') {
        if (defined('EE_PRINT')) {
            print "$s\n";
        }
    }

    public static function print_break($symbol = '-', $length = 80) {
        if (defined('EE_PRINT') ) {
            print "\n";
            print str_repeat($symbol, $length);
            print "\n\n";
        }
    }

    public static function print_t($s = '') {
        if (defined('EE_PRINT')) {
            print "$s\t";
        }
    }

    public static function print_r($a) {
        if (defined('EE_PRINT')) {
            print_r($a);
        }
    }

    public static function  print_e($callback) {
        if (defined('EE_PRINT')) {
            print $callback();
        }
    }

    public static function print_f($s, $format, $eol = ' ') {
        if (defined('EE_PRINT')) {
            if (substr_count($format, '%')) {
                print sprintf($format, $s) . $eol;
            } else {
                print sprintf('%1$' . $format, $s) . $eol;
            }
        }
    }

}