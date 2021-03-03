<?php
interface EE_IRequest {

    public function getURL();

    public function getHost();

    public function getArgumentArray();

    public function getArgument($key, $argType = false);

    public function getArgumentSecure($key, $argType = false);

    public function getCOOKIEArray();

}