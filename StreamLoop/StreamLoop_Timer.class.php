<?php
class StreamLoop_Timer extends StreamLoop_AHandler {

    public function __construct(StreamLoop $loop, $timerID, $timeout) {
        parent::__construct($loop);

        $loop->unregisterHandler($this);

        $this->setTimeout($timeout);

        $this->streamID = -1 * (int) $timerID; // id нужен отрицательный чтобы не пересекся с настоящими stream
        $this->stream = null;

        $this->_loop->registerHandler($this);
        $this->_loop->updateHandlerTimeout($this, microtime(true) + $this->_timeout);
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
        ($this->_callback)($this, $tsSelect, microtime(true));

        $this->_loop->updateHandlerTimeout($this, $tsSelect + $this->_timeout);
    }

    public function onTimer(callable $callback) {
        $this->_callback = $callback;
    }

    public function setTimeout($timeout) {
        $this->_timeout = (float) $timeout;
    }

    public function getTimeout() {
        return $this->_timeout;
    }

    private $_timeout = 0.0;

    private $_callback;

}