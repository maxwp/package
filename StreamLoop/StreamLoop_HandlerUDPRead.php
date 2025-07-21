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
        // reverse drain read loop
        $messageArray = [];

        // в php init локальной переменной дешевле чем доступ к свойству
        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

        $found = false;

        // to locals
        $socket = $this->_socket;
        $drainLimit = $this->_drainLimit;

        for ($j = 1; $j <= $drainLimit; $j++) {
            $bytes = socket_recvfrom(
                $socket,
                $buffer,
                1024,
                MSG_DONTWAIT,
                $fromAddress,
                $fromPort
            );

            if ($bytes === false) {
                // end of drain
                break;
            } elseif ($bytes > 0) { // пустые дата-граммы мне не нужны
                // если нет drain - то вызываем сразу передачу
                if ($drainLimit == 1) {
                    $this->_receiver->onReceive(microtime(true), $buffer, $fromAddress, $fromPort);
                    return;
                }

                // @todo теоретически можно поменять на какую-то другую структуру, а не массив - потому что его тяжеловато клеить
                // потому что дальше revert loop и он херовый
                $messageArray[] = [$buffer, $fromAddress, $fromPort];
                $found = true;
            }
        }

        // если вдруг ничего нет - на выход
        if (!$found) {
            return;
        }

        // 1. я вычисляю один ts на все сообщения, потому что из-за drain мне важно момент когда я начал обрабатвать (callback), а не когда я их достал
        // 2. ну и это экономия на microtime-call
        $ts = microtime(true);

        $receiver = $this->_receiver; // to locals
        if ($this->_drainReverse) {
            // вдуваем сообщения в обратном порядке
            $cnt = count($messageArray);
            for ($j = $cnt - 1; $j >= 0; $j--) {
                $message = $messageArray[$j];
                $receiver->onReceive($ts, $message[0], $message[1], $message[2]);
            }
        } else {
            // вдуваем сообщения в прямом порядке
            foreach ($messageArray as $message) {
                $receiver->onReceive($ts, $message[0], $message[1], $message[2]);
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