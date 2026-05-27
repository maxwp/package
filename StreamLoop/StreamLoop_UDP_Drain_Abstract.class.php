<?php
abstract class StreamLoop_UDP_Drain_Abstract extends StreamLoop_UDP_Abstract {

    public function readyWrite($tsSelect) {
        // nothing for UDP
    }

    public function readyExcept($tsSelect) {
        // nothing for UDP
    }

    public function readyTimeout($tsSelect) {
        // nothing for UDP
    }

    public function setDrain(int $limit) {
        if ($limit <= 1) {
            throw new StreamLoop_Exception('Drain limit must be greater than 1');
        }
        $this->_drainLimit = $limit;
    }

    protected int $_drainLimit = 3; // нет никакого смысла в drain=1

}