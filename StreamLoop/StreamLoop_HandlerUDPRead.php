<?php
class StreamLoop_HandlerUDPRead extends StreamLoop_AHandler {

    public function __construct($host, $port, callable $callback) {
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

        $this->_socket = socket_import_stream($this->stream);
        socket_set_option($this->_socket, SOL_SOCKET, SO_REUSEADDR, 1); // повторный биндинг
        socket_set_option($this->_socket, SOL_SOCKET, SO_RCVBUF, 2**20); // увеличиваем приёмный буфер ядра
        socket_set_nonblock($this->_socket); // делаем неблокирующим

        // Отключаем все таймауты и буферизацию PHP
        stream_set_read_buffer($this->stream, 0);
        stream_set_write_buffer($this->stream, 0);

        stream_set_blocking($this->stream, false);

        $this->_callback = $callback;

        $this->flagRead = true;
        $this->flagWrite = false;
        $this->flagExcept = false;
    }

    public function readyRead() {
        $buffer = '';
        $fromIP = '';
        $fromPort = 0;

        // drain loop
        // @todo приделать наоборот, сразу в массив и считать index массива на лету
        // а затем обработка в обратном порядке
        for ($j = 1; $j <= 10; $j++) {
            $bytes = socket_recvfrom(
                $this->_socket,
                $buffer,
                1024,
                0, // @todo MSG_DONTWAIT ?
                $fromIP,
                $fromPort
            );

            $ts = microtime(true);

            // Если получили больше нуля байт — обрабатываем
            if ($bytes > 0) {
                $callback = $this->_callback;
                $callback($ts, $buffer, $fromIP);
            } else {
                // end of drain
                break;
            }
        }
    }

    public function readyWrite() {

    }

    public function readyExcept() {

    }

    public function readySelectTimeout() {

    }

    private $_socket;

    private $_callback;
}