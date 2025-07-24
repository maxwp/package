<?php
class StreamLoop_UDP_DrainForward extends StreamLoop_UDP {

    public function readyRead($tsSelect) {
        // в php init локальной переменной дешевле чем доступ к свойству
        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

        // to locals
        $socket = $this->_socketResource;
        $drainLimit = $this->_drainLimit; // как правило drain есть, поэтому я выношу всегда в locals
        $receiver = $this->_receiver; // как правило readyRead срабатывает если что-то есть

        for ($drainIndex = 0; $drainIndex < $drainLimit; $drainIndex++) {
            $bytes = socket_recvfrom(
                $socket,
                $buffer,
                1024,
                MSG_DONTWAIT,
                $fromAddress,
                $fromPort
            );

            if ($bytes > 0) {
                $receiver->onReceive(microtime(true), $buffer, $fromAddress, $fromPort);
            } else {
                // stop draon
                return;
            }
        }
    }

    public function readyWrite($tsSelect) {
        // nothing for UDP
    }

    public function readyExcept($tsSelect) {
        // nothing for UDP
    }

    public function readySelectTimeout($tsSelect) {
        // nothing for UDP
    }

    public function setDrain($limit) {
        $this->_drainLimit = (int) $limit;
    }

    private int $_drainLimit = 1;

}