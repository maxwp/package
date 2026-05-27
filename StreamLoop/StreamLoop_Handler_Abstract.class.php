<?php
abstract class StreamLoop_Handler_Abstract {

    abstract public function readyRead($tsSelect);
    abstract public function readyWrite($tsSelect);
    abstract public function readyExcept($tsSelect);
    abstract public function readyTimeout($tsSelect);

    public function __construct(StreamLoop $loop) {
        $this->_loop = $loop;
    }

    /**
     * @var resource
     */
    public $stream;
    /**
     * @var positive-int
     */
    public $streamID;
    /**
     * @var StreamLoop
     */
    protected $_loop;

}