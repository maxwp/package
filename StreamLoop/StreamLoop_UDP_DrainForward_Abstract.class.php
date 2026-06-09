<?php
abstract class StreamLoop_UDP_DrainForward_Abstract extends StreamLoop_UDP_Drain_Abstract {

    public function readyRead($tsSelect) {
        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

        // to locals
        // нужно потому что будет минимум две попытки чтения
        $socket = $this->_socketResource;

        // counter
        $drainLimit = $this->_drainLimit;

        do {
            $bytes = socket_recvfrom(
                $socket,
                $buffer,
                1024,
                MSG_DONTWAIT,
                $fromAddress,
                $fromPort
            );

            // if-tree optimization
            if ($bytes > 0) {
                $this->_onReceive($tsSelect, $buffer, $bytes, $fromAddress);
            } else {
                // внимание! я не делаю тут проверки на ошибки, потому что эта штука занимает 0..1.1 us
                // stop drain
                return;
            }
        } while (--$drainLimit);
    }

}