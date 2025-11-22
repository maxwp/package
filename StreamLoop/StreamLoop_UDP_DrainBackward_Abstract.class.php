<?php
abstract class StreamLoop_UDP_DrainBackward_Abstract extends StreamLoop_UDP_Abstract {

    public function readyRead($tsSelect) {
        // в php init локальной переменной дешевле чем доступ к свойству
        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

        // to locals
        $socket = $this->_socketResource;
        $drainLimit = $this->_drainLimit; // как правило drain есть, поэтому я выношу всегда в locals

        // первое сообщене всегда, независимо от drain
        // так нужно сделать, потому что в 90% случаев сообщение в порту всего одно
        // и не надо тратиться на циклы с массивами
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
            // редкая ситуация select сказал что данные есть, но ничего не прочиталось
            return;
        }

        // если дальше drain нет - на выход
        if ($drainLimit <= 1) {
            return;
        }

        $found = 0;
        $bufferArray = [];
        $bytesArray = [];
        $fromAddressArray = [];
        $fromPortArray = [];

        for ($drainIndex = 2; $drainIndex <= $drainLimit; $drainIndex++) {
            $bytes = socket_recvfrom(
                $socket,
                $buffer,
                1024,
                MSG_DONTWAIT,
                $fromAddress,
                $fromPort
            );

            if ($bytes > 0) { // пустые дата-граммы мне не нужны
                // три параллельных массива быстрее чем один вложенный
                $bufferArray[] = $buffer;
                $bytesArray[] = $bytes;
                $fromAddressArray[] = $fromAddress;
                $fromPortArray[] = $fromPort;
                $found ++;
            } else {
                // тут более правильно проверять на === false,
                // но в реальности пустой дата-граммы быть не может

                // внимание! я не делаю тут проверки на ошибки, потому что эта штука занимает 0..1,1 us

                // end of drain
                break;
            }
        }

        // если вдруг ничего нет - на выход
        if ($found == 0) {
            return;
        }

        // вдуваем сообщения в обратном порядке
        for ($j = $found - 1; $j >= 0; $j--) {
            $this->_onReceive($tsSelect, $bufferArray[$j], $bytesArray[$j], $fromAddressArray[$j], $fromPortArray[$j]);
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
        $this->_drainLimit = $limit;
    }

    private int $_drainLimit = 1;

}