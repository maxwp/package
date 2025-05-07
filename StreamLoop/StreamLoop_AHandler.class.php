<?php
abstract class StreamLoop_AHandler {

    abstract public function readyRead();
    abstract public function readyWrite();
    abstract public function readyExcept();

    public $stream;

    public bool $flagRead = false;
    public bool $flagWrite = false;
    public bool $flagExcept = false;

}