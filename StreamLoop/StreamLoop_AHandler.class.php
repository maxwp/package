<?php
abstract class StreamLoop_AHandler {

    abstract public function readyRead($tsSelect);
    abstract public function readyWrite($tsSelect);
    abstract public function readyExcept($tsSelect);
    abstract public function readySelectTimeout($tsSelect);

    public function __construct(StreamLoop $loop) {
        $this->_loop = $loop;
    }

    public $stream;
    public $streamID;
    /**
     * @var StreamLoop
     */
    protected $_loop;

}