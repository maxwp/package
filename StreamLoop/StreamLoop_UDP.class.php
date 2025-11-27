<?php
/**
 * @deprecated use UDP_Abstract
 */
class StreamLoop_UDP extends StreamLoop_Handler_Abstract {

    public function __construct(StreamLoop $loop, $host, $port, StreamLoop_UDP_ICallback $receiver) {
        parent::__construct($loop);

        $this->stream = stream_socket_server(
            'udp://'.$host.':'.$port,
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

        $this->socket = new Connection_SocketStream($this->stream);
        $this->socket->setReuseAddr(0);
        $this->socket->setBufferSizeRead(50 * 1024 * 1024);
        $this->socket->setNonBlocking();
        $this->socket->setKeepAlive();
        $this->_socketResource = $this->socket->getLink();

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
        $socket = $this->_socketResource;

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

        // редко бывают ситуации когда bytes === 0 - данных нет, но это валидно
        if ($bytes > 0) {
            $this->_receiver->onReceive($this, $tsSelect, $buffer, $bytes, $fromAddress, $fromPort);
        } else {
            $err = socket_last_error($socket);

            // в Linux EAGAIN == EWOULDBLOCK (11), достаточно одного сравнения
            if ($err != SOCKET_EAGAIN /* || $err === SOCKET_EWOULDBLOCK */) {
                $this->_receiver->onError($this, $tsSelect, $err);
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

    public Connection_SocketStream $socket;
    protected $_socketResource;
    protected StreamLoop_UDP_ICallback $_receiver;

}