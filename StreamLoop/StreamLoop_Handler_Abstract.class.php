<?php
abstract class StreamLoop_Handler_Abstract {

    abstract public function readyRead($tsSelect);
    abstract public function readyWrite($tsSelect);
    abstract public function readyExcept($tsSelect);
    abstract public function readySelectTimeout($tsSelect);

    public function __construct(StreamLoop $loop) {
        $this->_loop = $loop;
    }

    /**
     * @return StreamLoop
     */
    public function getLoop() {
        return $this->_loop;
    }

    /**
     * @var resource
     */
    public $stream;
    public $streamID;
    /**
     * @var StreamLoop
     */
    protected $_loop;

}