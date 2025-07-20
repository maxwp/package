<?php
abstract class StreamLoop_AHandler {

    abstract public function readyRead();
    abstract public function readyWrite();
    abstract public function readyExcept();
    abstract public function readySelectTimeout();

    public function __construct(StreamLoop $loop) {
        $this->_loop = $loop;
    }

    public $stream;
    public $streamID;
    public $timeoutTo = 0;
    /**
     * @var StreamLoop
     */
    protected $_loop;

}