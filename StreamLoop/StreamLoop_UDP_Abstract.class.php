<?php
abstract class StreamLoop_UDP_Abstract extends StreamLoop_Handler_Abstract {

    abstract protected function _onReceive($tsSelect, $message, $messageSize, $fromAddress, $fromPort);

    abstract protected function _onError($tsSelect, $errorCode);

    public function updateConnection($host, $port) {
        $this->_host = $host;
        $this->_port = $port;
    }

    public function connect() {
        $this->stream = stream_socket_server(
            'udp://'.$this->_host.':'.$this->_port,
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
            $this->_onReceive($tsSelect, $buffer, $bytes, $fromAddress, $fromPort);
        } else {
            $err = socket_last_error($socket);

            // в Linux EAGAIN == EWOULDBLOCK (11), достаточно одного сравнения
            if ($err != SOCKET_EAGAIN /* || $err === SOCKET_EWOULDBLOCK */) {
                $this->_onError($tsSelect, $err);
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
    private $_host;
    private $_port;

}