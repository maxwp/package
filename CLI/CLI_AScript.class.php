<?php
abstract class CLI_AScript {

    abstract function main();

    public function getArgument($name, $type = false, $isDefaultArg = false) {
        return CLI_Service::Get()->getCLIArgument($name, true, $type, $isDefaultArg);
    }

    public function getArgumentSecure($name, $type = false, $isDefaultArg = false) {
        return CLI_Service::Get()->getCLIArgument($name, false, $type, $isDefaultArg);
    }

}