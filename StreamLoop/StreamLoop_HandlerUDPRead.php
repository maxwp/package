<?php
class StreamLoop_HandlerUDPRead extends StreamLoop_AHandler {

    // @todo как сделать универсально с drain forward/back и без него?
    // потому что hedger тоже будет юзать этот же SL_UDP

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

        $this->_callback = $callback; // @todo йобаный callable closure опять

        $this->flagRead = true;
        $this->flagWrite = false;
        $this->flagExcept = false;

        $this->setDrainLimit(50);
    }

    public function readyRead() {
        // reverse drain read loop
        $this->_messageCount = 0;

        for ($j = 1; $j <= $this->_drainLimit; $j++) {
            $bytes = socket_recvfrom(
                $this->_socket,
                $this->_buffer,
                1024,
                MSG_DONTWAIT,
                $this->_fromAddress,
                $this->_fromPort
            );

            if ($bytes === false) {
                // end of drain
                break;
            } else {
                $this->_messageArray[$this->_messageCount] = [$this->_buffer, $this->_fromAddress, $this->_fromPort];
                $this->_messageCount ++;
            }
        }

        // я вычисляю один ts на все сообщения, потому что из-за drain мне важно момент когда я начал обрабатвать (callback), а не когда я их достал
        // и это экономия на microtime-call
        $ts = microtime(true);
        $callback = $this->_callback; // @todo wtf Closure?
        // вдуваем сообщения в обратном порядке
        for ($j = $this->_messageCount - 1; $j >= 0; $j--) {
            $callback($ts, $this->_messageArray[$j][0], $this->_messageArray[$j][1], $this->_messageArray[$j][2]);
        }
    }

    public function readyWrite() {

    }

    public function readyExcept() {

    }

    public function readySelectTimeout() {

    }

    public function setDrainLimit(int $limit) {
        $this->_drainLimit = $limit;
        // инициируем массив до drain limit специально чтобы не менять его size на лету, бо динамическая херня ждет аллокации
        $this->_messageArray = array_fill(0, $this->_drainLimit, null);
    }

    private $_socket;

    private $_callback;

    private int $_drainLimit = 0;

    // я вынес параметры сюда для уменьшения malloc, потому что readyRead вызывается постоянно
    private $_buffer = '';
    private $_fromAddress = '';
    private $_fromPort = 0;
    private $_messageArray = [];
    private $_messageCount = 0;

}