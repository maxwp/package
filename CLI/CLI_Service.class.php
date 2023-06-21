<?php
class CLI_Service {

    public function getCLIArgument($name, $required = false, $type = false, $isDefaultArg = false) {
        global $argv;

        $name = str_replace('--', '', $name);
        if (!$name) {
            throw new Exception('no arg name', 1);
        }

        $returnArray = array();

        for ($j = 1; $j < 100; $j++) {
            $arg = @$argv[$j];
            if ($arg) {
                $arg = str_replace('--', '', $arg);

                if (preg_match("/^(.+?)=(.+?)$/ius", $arg, $r)) {
                    if ($r[1] == $name) {
                        $returnArray[] = $r[2];
                    }
                } elseif (!substr_count($arg, '=') && $isDefaultArg) {
                    $returnArray[] = $arg;
                } elseif ($arg == $name && $type == 'bool') {
                    $returnArray[] = $arg;
                }
            }
        }

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

        if ($required) {
            throw new Exception('no arg '.$name, 1);
        }

        return false;
    }

    /**
     * @return CLI_Service
     */
    public static function Get() {
        if (!self::$_Instance) {
            $classname = __CLASS__;
            self::$_Instance = new $classname();
        }

        return self::$_Instance;
    }

    private static $_Instance;

}