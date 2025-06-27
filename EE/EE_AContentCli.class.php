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

    public function printSGRStart(...$args) {
        Cli::PrintSGRStart(...$args);
    }

    public function printSGREnd() {
        Cli::PrintSGREnd();
    }

    public function print($s) {
        Cli::Print($s);
    }

    public function print_n($s = '') {
        Cli::Print_n($s);
    }

    public function print_break($symbol = '-', $length = 80, $separator = "\n") {
        Cli::Print_break($symbol, $length, $separator);
    }

    public function print_t($s = '') {
        Cli::Print_t($s);
    }

    public function print_r($a) {
        Cli::Print_r($a);
    }

    public function print_f($s, $format, $eol = ' ', $color = false) {
        Cli::Print_f($s, $format, $eol, $color);
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

}