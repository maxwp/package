<?php
/**
 * @deprecated use ATimer
 */
class StreamLoop_Timer extends StreamLoop_ATimer {

    protected function _onTimer($tsSelect) {
        $this->_callback->onTimer($this, $tsSelect);
    }

    public function setCallback(StreamLoop_Timer_ICallback $callback) {
        $this->_callback = $callback;
    }

    private StreamLoop_Timer_ICallback $_callback;

}