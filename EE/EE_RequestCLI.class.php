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

    public function getArgument($key, $source = false, $type = false) {
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
            if ($arg) {
                $arg = str_replace('--', '', $arg);

                if (preg_match("/^(.+?)=(.+?)$/ius", $arg, $r)) {
                    if ($r[1] == $key) {
                        $returnArray[] = $r[2];
                    }
                } elseif ($arg == $key && $type == 'bool') {
                    $returnArray[] = $arg;
                }
            }
        }

        // @todo тут нет больше типизации, возвращается всегда что нашлось
        // а затем Typing уже приводит к нужному виду
        if ($returnArray) {
            if ($type === 'string') {
                return implode(';', $returnArray);
            } elseif ($type == 'array') {
                return $returnArray;
            } elseif ($type == 'bool') {
                return (bool) $returnArray[0];
            } elseif (count($returnArray) == 1) {
                return $returnArray[0];
            } else {
                return $returnArray;
            }
        }

        throw new EE_Exception('No argument '.$key);
    }

}