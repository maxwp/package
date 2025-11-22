<?php
abstract class StreamLoop_UDP_DrainForward_Abstract extends StreamLoop_UDP_Abstract {

    public function readyRead($tsSelect) {
        // в php init локальной переменной дешевле чем доступ к свойству
        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

        // to locals
        $socket = $this->_socketResource;
        $drainLimit = $this->_drainLimit; // как правило drain есть, поэтому я выношу всегда в locals

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
                $this->_onReceive($tsSelect, $buffer, $bytes, $fromAddress, $fromPort);
            } else {
                // внимание! я не делаю тут проверки на ошибки, потому что эта штука занимает 0..1,1 us

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