<?php
abstract class StreamLoop_UDP_Drain_Abstract extends StreamLoop_UDP_Abstract {

    public function setDrain($limit) {
        $limit = (int) $limit;
        if ($limit < 3) {
            throw new StreamLoop_Exception('Drain limit must be greater than 2');
        }
        $this->_drainLimit = $limit;
    }

    protected $_drainLimit = 3; // нет никакого смысла в drain=1

}