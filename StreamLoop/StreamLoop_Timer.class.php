<?php
class StreamLoop_Timer extends StreamLoop_AHandler {

    public function __construct(StreamLoop $loop, $timerID, $timeout, StreamLoop_Timer_ICallback $callback) {
        parent::__construct($loop);

        $loop->unregisterHandler($this);

        $this->setTimeout($timeout);

        // @todo check id
        $this->streamID = -1 * (int) $timerID; // id нужен отрицательный чтобы не пересекся с настоящими stream //
        $this->stream = null;

        $this->onCallback($callback);

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
        $this->_callback->onTimer($this, $tsSelect);
        $this->_loop->updateHandlerTimeoutTo($this, $tsSelect + $this->_timeout);
    }

    public function onCallback(StreamLoop_Timer_ICallback $callback) {
        $this->_callback = $callback;
    }

    public function setTimeout($timeout) {
        $this->_timeout = (float) $timeout;
    }

    public function getTimeout() {
        return $this->_timeout;
    }

    private $_timeout = 0.0;

    private StreamLoop_Timer_ICallback $_callback;

}