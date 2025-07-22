<?php
class StreamLoop_Timer extends StreamLoop_AHandler {

    public function __construct(StreamLoop $loop, $timeout) {
        parent::__construct($loop);

        $loop->unregisterHandler($this);

        $timeout = (float) $timeout;
        $this->_timeout = $timeout;

        $this->streamID = -1 * rand(1, 999999); // случайный отрицательный id
        $this->stream = null;

        $loop->registerHandler($this);

        $this->_loop->updateHandlerTimeout($this, microtime(true) + $timeout);
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
        $callback = $this->_callback;
        $callback($tsSelect, microtime(true));

        $this->_loop->updateHandlerTimeout($this, $tsSelect + $this->_timeout);
    }

    public function onTimer(callable $callback) {
        $this->_callback = $callback;
    }

    private $_timeout;

    private $_callback;

}