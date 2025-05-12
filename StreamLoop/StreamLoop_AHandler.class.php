<?php
abstract class StreamLoop_AHandler {

    abstract public function readyRead();
    abstract public function readyWrite();
    abstract public function readyExcept();
    abstract public function readySelectTimeout();
    abstract public function tick($ts);
    public $stream;
    public bool $flagRead = false;
    public bool $flagWrite = false;
    public bool $flagExcept = false;
    public bool $flagTick = false;
    public $timeoutTo = 0;

}