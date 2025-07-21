<?php
class StreamLoop_HandlerUDPRead extends StreamLoop_AHandler {

    public function __construct(StreamLoop $loop, $host, $port, StreamLoop_HandlerUDPRead_IReceiver $receiver) {
        parent::__construct($loop);

        $this->stream = stream_socket_server(
            sprintf('udp://%s:%d', $host, $port),
            $errno,
            $errstr,
            STREAM_SERVER_BIND
        );
        if ($this->stream === false) {
            // критическая ошибка — завершаем
            throw new StreamLoop_Exception("$errstr ($errno)");
        }

        $this->streamID = (int) $this->stream;

        // регистрация handler'a в loop'e
        $this->_loop->registerHandler($this);

        $this->_socket = socket_import_stream($this->stream);
        socket_set_option($this->_socket, SOL_SOCKET, SO_REUSEADDR, 1); // повторный биндинг
        socket_set_option($this->_socket, SOL_SOCKET, SO_RCVBUF, 2**20); // увеличиваем приёмный буфер ядра
        socket_set_nonblock($this->_socket); // делаем неблокирующим

        // @todo add busy_poll mode

        // Отключаем все таймауты и буферизацию PHP
        stream_set_read_buffer($this->stream, 0);
        stream_set_write_buffer($this->stream, 0);

        stream_set_blocking($this->stream, false);

        $this->_receiver = $receiver;

        $this->_loop->updateHandlerFlags($this, true, false, false);
    }

    public function readyRead($tsSelect) {
        // в php init локальной переменной дешевле чем доступ к свойству
        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

        // to locals
        $socket = $this->_socket;
        $drainLimit = $this->_drainLimit; // как правило drain есть, поэтому я выношу всегда в locals
        $receiver = $this->_receiver; // как правило readyRead срабатывает если что-то есть

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
            $receiver->onReceive(microtime(true), $buffer, $fromAddress, $fromPort);
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
                $fromAddressArray[] = $fromAddress;
                $fromPortArray[] = $fromPort;
                $found += 1;
            } else {
                // тут более правильно проверять на === false,
                // но в реальности пустой дата-граммы быть не может
                // end of drain
                break;
            }
        }

        // если вдруг ничего нет - на выход
        if ($found == 0) {
            return;
        }

        // 1. я вычисляю один ts на все сообщения, потому что из-за drain мне важно момент когда я начал обрабатвать (callback), а не когда я их достал
        // 2. ну и это экономия на microtime-call
        $ts = microtime(true);

        if ($this->_drainReverse) {
            // вдуваем сообщения в обратном порядке
            for ($j = $found - 1; $j >= 0; $j--) {
                $receiver->onReceive($ts, $bufferArray[$j], $fromAddressArray[$j], $fromPortArray[$j]);
            }
        } else {
            // вдуваем сообщения в прямом порядке
            for ($j = 0; $j < $found; $j++) {
                $receiver->onReceive($ts, $bufferArray[$j], $fromAddressArray[$j], $fromPortArray[$j]);
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

    public function setDrain(int $limit, bool $reverse) {
        $this->_drainLimit = $limit;
        $this->_drainReverse = $reverse;
    }

    private $_socket;

    private StreamLoop_HandlerUDPRead_IReceiver $_receiver;

    private int $_drainLimit = 1;
    private bool $_drainReverse = false;

}