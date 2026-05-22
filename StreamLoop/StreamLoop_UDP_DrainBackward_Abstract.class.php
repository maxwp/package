<?php
abstract class StreamLoop_UDP_DrainBackward_Abstract extends StreamLoop_UDP_DrainForward_Abstract {

    public function readyRead($tsSelect) {
        // тут я не делаю socket to locals, потому что в 90% случаев будет одно чтение,
        // в 7% случаев 2 чтения,
        // и 3% случаев 3+ чтения,
        // поэтому не выгодно выносить переменные в локальные

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
        $buffer1 = $buffer;
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
        $drainLimit = $this->_drainLimit; // это обязательно делать из-за for
        for ($drainIndex = 3; $drainIndex <= $drainLimit; $drainIndex++) {
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
        }

        // emit latest-first: newest datagram first
        // тут нельзя foreach, потому что бегу по элементам в обратном порядке
        for ($j = $found - 1; $j >= 0; $j--) {
            $this->_onReceive(
                $tsSelect,
                $bufferArray[$j],
                $bytesArray[$j],
                $fromAddressArray[$j]
            );
        }
    }

}