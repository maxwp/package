<?php
class StreamLoop_HandlerUDPRead implements StreamLoop_IHandler {

    public function __construct($host, $port, callable $callback) {
        $this->_stream = stream_socket_server(
            sprintf('udp://%s:%d', $host, $port),
            $errno,
            $errstr,
            STREAM_SERVER_BIND
        );
        if ($this->_stream === false) {
            // критическая ошибка — завершаем
            fwrite(STDERR, "Ошибка создания сокета: $errstr ($errno)\n");
            exit(1);
        }

        $this->_socket = socket_import_stream($this->_stream);
        socket_set_option($this->_socket, SOL_SOCKET, SO_REUSEADDR, 1); // повторный биндинг
        socket_set_option($this->_socket, SOL_SOCKET, SO_RCVBUF, 2**20); // увеличиваем приёмный буфер ядра
        socket_set_nonblock($this->_socket); // делаем неблокирующим

        // Отключаем все таймауты и буферизацию PHP
        stream_set_read_buffer($this->_stream, 0);
        stream_set_write_buffer($this->_stream, 0);

        stream_set_blocking($this->_stream, false);

        $this->_callback = $callback;
    }

    public function readyRead() {
        $buffer = '';
        $fromIP = '';
        $fromPort = 0;

        $data = socket_recvfrom(
            $this->_socket,
            $buffer,
            1024,
            0,
            $fromIP,
            $fromPort
        );

        $ts = microtime(true);

        /*if ($data === false) {
            // нет данных — CPU-спин
            usleep(1);
            continue;
        }*/

        // Если получили больше нуля байт — обрабатываем
        if ($data > 0) {
            $callback = $this->_callback;
            $callback($ts, $buffer, $fromIP);
        }
    }

    public function readyWrite() {

    }

    public function readyExcept() {

    }

    public function getStreamConfig() {
        // stream, r, w, e
        return [$this->_stream, true, false, false];
    }

    private $_stream;
    private $_socket;

    private $_callback;
}