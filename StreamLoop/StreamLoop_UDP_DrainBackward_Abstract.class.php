<?php
abstract class StreamLoop_UDP_DrainBackward_Abstract extends StreamLoop_UDP_Drain_Abstract {

    public function readyRead($tsSelect) {
        // тут всегда будет как минимум две попытки чтения, поэтому to locals оправдан для всего

        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

        // --- recv #1 (must exist if select says readable) ---
        $bytes1 = socket_recvfrom(
            $this->_socketResource,
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
        $buffer1 = $buffer; // @todo проще отдельную переменную, чем переприсванивать
        $addr1 = $fromAddress;

        // --- recv #2 (single extra recv to detect batching) ---
        $bytes2 = socket_recvfrom(
            $this->_socketResource,
            $buffer,
            1024,
            MSG_DONTWAIT,
            $fromAddress,
            $fromPort
        );

        if ($bytes2 <= 0) {
            // common case: only one datagram available -> no arrays/loops
            $this->_onReceive($tsSelect, $buffer1, $bytes1, $addr1);
            return;
        }

        // --- batching case: buffer ALL and emit latest-first ---
        // push #1
        // push #2 (currently in $buffer/$fromAddress/$fromPort)
        // NB! Такой подход с отдельными массивами на 32% быстрее чем делать вложенный массив, я проверил дважды.
        $bufferArray = [$buffer1, $buffer];
        $bytesArray = [$bytes1, $bytes2];
        $fromAddressArray = [$addr1, $fromAddress];

        $found = 2;

        // drain up to limit:
        // start from 3 because we already have 2
        $drainLimit = $this->_drainLimit - 2;

        do {
            $bytes = socket_recvfrom(
                $this->_socketResource,
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
                $found ++;
            } else {
                // end of drain
                break;
            }
        } while (--$drainLimit);

        // emit latest-first: newest datagram first
        // тут нельзя foreach, потому что бегу по элементам в обратном порядке
        do {
            --$found;

            $this->_onReceive(
                $tsSelect,
                $bufferArray[$found],
                $bytesArray[$found],
                $fromAddressArray[$found]
            );
        } while ($found);
    }

}