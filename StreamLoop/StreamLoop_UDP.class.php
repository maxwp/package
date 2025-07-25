<?php
class StreamLoop_UDP extends StreamLoop_AHandler {

    public function __construct(StreamLoop $loop, $host, $port, StreamLoop_UDP_IReceiver $receiver) {
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

        $this->socket = new Connection_SocketStream($this->stream);
        $this->socket->setReuseAddr(0);
        $this->socket->setBufferSizeRead(50 * 1024 * 1024);
        $this->socket->setNonBlocking();
        $this->_socketResource = $this->socket;

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

        if ($bytes > 0) {
            $this->_receiver->onReceive(microtime(true), $buffer, $fromAddress, $fromPort);
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
    protected StreamLoop_UDP_IReceiver $_receiver;

}