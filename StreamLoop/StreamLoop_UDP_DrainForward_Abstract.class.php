<?php
abstract class StreamLoop_UDP_DrainForward_Abstract extends StreamLoop_UDP_Drain_Abstract {

    public function readyRead($tsSelect) {
        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

        $drainLimit = $this->_drainLimit; // это обязательно делать из-за for

        // тут я не делаю socket to locals, потому что в 90% случаев будет одно чтение,
        // в 7% случаев 2 чтения,
        // и 3% случаев 3+ чтения,
        // поэтому не выгодно выносить переменные в локальные
        for ($drainIndex = 1; $drainIndex <= $drainLimit; $drainIndex++) {
            $bytes = socket_recvfrom(
                $this->_socketResource,
                $buffer,
                1024,
                MSG_DONTWAIT,
                $fromAddress,
                $fromPort
            );

            if ($bytes > 0) {
                $this->_onReceive($tsSelect, $buffer, $bytes, $fromAddress);
            } else {
                // внимание! я не делаю тут проверки на ошибки, потому что эта штука занимает 0..1.1 us
                // stop drain
                return;
            }
        }
    }

}