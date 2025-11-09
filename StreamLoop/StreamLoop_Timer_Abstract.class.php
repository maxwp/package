<?php
abstract class StreamLoop_Timer_Abstract extends StreamLoop_Handler_Abstract {

    abstract protected function _onTimer($tsSelect);

    /**
     *  Важно: таймер может сработать не супер точно, а с дрейфом на время обработки handler-ов.
     *  Это связано с тем, что я использую prev_tsSelect для расчета таймеров следуюего круга.
     *  Потому что запрос времени занимает 40 ns, и это реально 1/3 от всего event loop'a.
     *
     * @param StreamLoop $loop
     * @param $timerID
     * @param $timeout
     * @throws StreamLoop_Exception
     */
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

    protected $_timeout = 0.0;

}