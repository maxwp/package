<?php
abstract class StreamLoop_ATimer extends StreamLoop_AHandler {

    abstract protected function _onTimer($tsSelect);

    public function __construct(StreamLoop $loop, $timerID, $timeout) {
        parent::__construct($loop);

        $loop->unregisterHandler($this);

        $this->setTimeout($timeout);

        // @todo check id
        $this->streamID = -1 * (int) $timerID; // id нужен отрицательный чтобы не пересекся с настоящими stream
        $this->stream = null;

        $this->_loop->registerHandler($this);
        $this->_loop->updateHandlerTimeoutTo($this, microtime(true) + $this->_timeout);
    }

    public function readyRead($tsSelect) {
        // nothing
    }

    public function readyWrite($tsSelect) {
        // nothing
    }

    public function readyExcept($tsSelect) {
        // nothing
    }

    public function readySelectTimeout($tsSelect) {
        $this->_onTimer($tsSelect);
        $this->_loop->updateHandlerTimeoutTo($this, $tsSelect + $this->_timeout);
    }

    public function setTimeout($timeout) {
        $this->_timeout = (float) $timeout;
    }

    public function getTimeout() {
        return $this->_timeout;
    }

    private $_timeout = 0.0;

}