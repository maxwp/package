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

    // @todo а нахер он надо?
    // @todo встроить в остальные print_*?
    public function print($s) {
        if (defined('EE_PRINT') || $this->_print) {
            print $s.''; // это нужно для типизации в string, потому что я могу передать объект типа DateTime_Object
        }
    }

    public function print_n($s = '') {
        if (defined('EE_PRINT') || $this->_print) {
            print "$s\n";
        }
    }

    public function print_break($symbol = '-', $length = 80) {
        if (defined('EE_PRINT') || $this->_print) {
            print "\n";
            print str_repeat($symbol, $length);
            print "\n\n";
        }
    }

    public function print_t($s = '') {
        if (defined('EE_PRINT') || $this->_print) {
            print "$s\t";
        }
    }

    public function print_r($a) {
        if (defined('EE_PRINT') || $this->_print) {
            print_r($a);
        }
    }

    public function print_e($callback) {
        if (defined('EE_PRINT') || $this->_print) {
            print $callback();
        }
    }

    public function print_f($s, $format, $eol = ' ') {
        if (defined('EE_PRINT') || $this->_print) {
            if (substr_count($format, '%')) {
                print sprintf($format, $s) . $eol;
            } else {
                print sprintf('%1$' . $format, $s) . $eol;
            }
        }
    }

    protected function _setPrintMode(bool $mode = true) {
        $this->_print = $mode;
    }

    protected function _setWorkTimeLimit(float $seconds) {
        $this->_workTimeLimit = microtime(true) + $seconds;
    }

    protected function _checkWorkTimeLimit() {
        if ($this->_workTimeLimit > 0 && microtime(true) > $this->_workTimeLimit) {
            $this->print_n("Exit by work time limit");
            return true;
        }

        return false;
    }

    private float $_workTimeLimit = 0;

    private bool $_print = false;

}