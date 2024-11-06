<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Request for CLI
 */
class EE_RequestCLI implements EE_IRequest {

    public function getArgumentArray() {
        global $argv;

        // @todo надо красиво перебрать все аргументы

        return $argv;
    }

    public function getArgument($key, $argType = false) {
        global $argv;

        $key = str_replace('--', '', $key);
        if (!$key) {
            throw new EE_Exception('no arg name', 1);
        }

        $returnArray = [];
        for ($j = 1; $j < 100; $j++) {
            $arg = @$argv[$j];
            if ($arg) {
                $arg = str_replace('--', '', $arg);

                if (preg_match("/^(.+?)=(.+?)$/ius", $arg, $r)) {
                    if ($r[1] == $key) {
                        $returnArray[] = $r[2];
                    }
                } elseif ($arg == $key && $argType == 'bool') {
                    $returnArray[] = $arg;
                }
            }
        }

        if ($returnArray) {
            if ($argType === 'string') {
                return implode(';', $returnArray);
            } elseif ($argType == 'array') {
                return $returnArray;
            } elseif ($argType == 'bool') {
                return (bool) $returnArray[0];
            } elseif (count($returnArray) == 1) {
                return $returnArray[0];
            } else {
                return $returnArray;
            }
        }

        throw new EE_Exception('No argument '.$key);
    }

    public function getArgumentSecure($key, $argType = false) {
        try {
            return $this->getArgument($key, $argType);
        } catch (Exception $e) {

        }

        return false;
    }
}