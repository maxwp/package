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

        // @todo надо красиво перебрать все CLI-аргументы

        return $argv;
    }

    public function getArgument($key, $source = false) {
        global $argv;

        // проверка на дурачка
        if ($source && $source != self::ARG_SOURCE_CLI) {
            throw new EE_Exception("Cli has only source CLI arguments");
        }

        $key = str_replace('--', '', $key);
        if (!$key) {
            throw new EE_Exception('no arg name', 1);
        }

        $returnArray = [];
        for ($j = 1; $j <= 100; $j++) {
            $arg = @$argv[$j];
            if (!$arg) {
                continue;
            }

            $arg = preg_replace('/^--/', '', $arg);

            if (preg_match("/^(.+?)=(.+?)$/ius", $arg, $r)) {
                if ($r[1] == $key) {
                    $returnArray[] = $r[2];
                }
            } elseif ($arg == $key) {
                // похоже на bool
                $returnArray[] = true;
            }
        }

        if ($returnArray) {
            // если один элемент - выдаем его
            if (count($returnArray) == 1) {
                return $returnArray[0];
            } else {
                // инача массив
                return $returnArray;
            }
        }

        throw new EE_Exception('No argument '.$key);
    }

}