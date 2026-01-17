<?php
abstract class StreamLoop_UDP_DrainBackward_Abstract extends StreamLoop_UDP_Abstract {

    public function readyRead($tsSelect) {
        // to locals
        $socket = $this->_socketResource;
        $drainLimit = $this->_drainLimit; // как правило drain есть, поэтому я выношу всегда в locals

        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

        // --- recv #1 (must exist if select says readable) ---
        $bytes1 = socket_recvfrom(
            $socket,
            $buffer,
            1024,
            MSG_DONTWAIT,
            $fromAddress,
            $fromPort
        );

        if ($bytes1 <= 0) {
            // rare: select said readable but nothing read
            // редкая ситуация select сказал что данные есть, но ничего не прочиталось
            return;
        }

        // stash #1 (because next recv overwrites vars)
        $buffer1 = $buffer;
        $addr1   = $fromAddress;
        $port1   = $fromPort;

        // --- recv #2 (single extra recv to detect batching) ---
        $bytes2 = socket_recvfrom(
            $socket,
            $buffer,
            1024,
            MSG_DONTWAIT,
            $fromAddress,
            $fromPort
        );

        if ($bytes2 <= 0) {
            // common case: only one datagram available -> no arrays/loops
            $this->_onReceive($tsSelect, $buffer1, $bytes1, $addr1, $port1);
            return;
        }

        // --- batching case: buffer ALL and emit latest-first ---
        // push #1
        // push #2 (currently in $buffer/$fromAddress/$fromPort)
        $bufferArray = [$buffer1, $buffer];
        $bytesArray = [$bytes1, $bytes2];
        $fromAddressArray = [$addr1, $fromAddress];
        $fromPortArray = [$port1, $fromPort];

        $found = 2;

        // drain up to limit
        // start from 3 because we already have 2
        for ($drainIndex = 3; $drainIndex <= $drainLimit; $drainIndex++) {
            $bytes = socket_recvfrom(
                $socket,
                $buffer,
                1024,
                MSG_DONTWAIT,
                $fromAddress,
                $fromPort
            );

            if ($bytes > 0) {
                $bufferArray[] = $buffer;
                $bytesArray[] = $bytes;
                $fromAddressArray[] = $fromAddress;
                $fromPortArray[] = $fromPort;
                $found ++;
            } else {
                // end of drain
                break;
            }
        }

        // emit latest-first: newest datagram first
        for ($j = $found - 1; $j >= 0; $j--) {
            $this->_onReceive(
                $tsSelect,
                $bufferArray[$j],
                $bytesArray[$j],
                $fromAddressArray[$j],
                $fromPortArray[$j]
            );
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

    public function setDrain(int $limit) {
        if ($limit <= 1) {
            throw new StreamLoop_Exception("Drain limit can not be less than 2");
        }
        $this->_drainLimit = $limit;
    }

    private int $_drainLimit = 3; // нет никакого смысла в drain=1

}