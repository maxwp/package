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

        $this->_socket = socket_import_stream($this->stream);
        $this->setBufferSizeRead(50 * 1024 * 1024);
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

    public function setSocketOption($type, $value) {
        if (!socket_set_option($this->_socket, SOL_SOCKET, $type, $value)) {
            throw new Connection_Exception("Socket option error type=$type");
        }
    }

    public function setBufferSizeRead($size) {
        $this->setSocketOption(SO_RCVBUF, $size);
        $this->_checkBufferSize(SO_RCVBUF, $size);
    }

    public function setBufferSizeWrite($size) {
        $this->setSocketOption(SO_SNDBUF, $size);
        $this->_checkBufferSize(SO_SNDBUF, $size);
    }

    private function _checkBufferSize($side, $size) {
        // При установке опции SO_RCVBUF/SO_SNDBUF в Linux значение, которое вы указываете, автоматически удваивается для учёта
        // накладных расходов ядра (служебных структур, буферов для управления данными и т.п.)
        // Это стандартное поведение, задокументированное в описании сокетов в Linux,
        // и оно работает как для TCP, так и для UDP.
        if (socket_get_option($this->_socket, SOL_SOCKET, $side) < $size * 2) {
            throw new Connection_Exception("$side size error");
        }
    }

    protected $_socket;
    protected StreamLoop_UDP_IReceiver $_receiver;

}