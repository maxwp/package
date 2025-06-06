<?php
class StreamLoop_HandlerUDPRead extends StreamLoop_AHandler {

    // @todo как сделать универсально с drain forward/back и без него?
    // потому что hedger тоже будет юзать этот же SL_UDP

    public function __construct($host, $port, StreamLoop_HandlerUDPRead_IReceiver $receiver) {
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

        $this->_receiver = $receiver;

        $this->flagRead = true; // true only for UDP
        $this->flagWrite = false;
        $this->flagExcept = false;

        $this->setDrainLimit(50);
    }

    public function readyRead() {
        // reverse drain read loop
        $messageArray = [];

        // в php init локальной переменной дешевле чем доступ к свойству, поэтому не могу вынести
        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

        for ($j = 1; $j <= $this->_drainLimit; $j++) {
            $bytes = socket_recvfrom(
                $this->_socket,
                $buffer,
                1024,
                MSG_DONTWAIT,
                $fromAddress,
                $fromPort
            );

            if ($bytes === false) {
                // end of drain
                break;
            } else {
                $messageArray[] = [$buffer, $fromAddress, $fromPort];
            }
        }

        // я вычисляю один ts на все сообщения, потому что из-за drain мне важно момент когда я начал обрабатвать (callback), а не когда я их достал
        // и это экономия на microtime-call
        $ts = microtime(true);

        // вдуваем сообщения в обратном порядке
        $cnt = count($messageArray);
        for ($j = $cnt - 1; $j >= 0; $j--) {
            $this->_receiver->onReceive($ts, $messageArray[$j][0], $messageArray[$j][1], $messageArray[$j][2]);
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
    }

    private $_socket;

    private StreamLoop_HandlerUDPRead_IReceiver $_receiver;

    private int $_drainLimit = 0;

}