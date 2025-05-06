<?php
interface StreamLoop_IHandler {

    public function readyRead();
    public function readyWrite();
    public function readyExcept();
    public function getStreamConfig();

}