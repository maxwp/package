<?php
class StreamLoop_HandlerWSS extends StreamLoop_AHandler {

    public function __construct(StreamLoop $loop, $host, $port, $path, $writeArray, $ip) {
        parent::__construct($loop);

        $this->_host = $host;
        $this->_port = $port;
        $this->_path = $path;
        $this->_writeArray = $writeArray;
        $this->_ip = $ip ? $ip : $this->_host;

        // @todo как слепить в кучу websocket over https?
        // @todo сначала надо придумать как сделать StateMachine, чтобы я мог помещать команду с событиями onXXX,
        // и затем handshake и switching protocol снанут этими командами
        // @todo тут странноватая реализация WebSocket, потому что мне нужно стабильно каждые 250ms получать callback message, даже пустую.
        // возможно можно переписать как-то на таймеры, чтобы не ограничивать специально socket_select.

        $this->connect();
    }

    public function onMessage(callable $callback) {
        $this->_callbackMessage = $callback;
    }

    public function onError(callable $callback) {
        $this->_callbackError = $callback;
    }

    public function connect() {
        $this->_buffer = '';

        // to locals
        $loop = $this->_loop;

        $loop->unregisterHandler($this);

        $stream = stream_socket_client(
            "tcp://{$this->_ip}:{$this->_port}",
            $errno,
            $errstr,
            0, // timeout = 0, чтобы мгновенно вернулось
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT,
            stream_context_create() // без ssl-опций! @todo возмоэно надо будет таки перенести контекст из Connection_WebSocket
        );
        if (!$stream) {
            throw new StreamLoop_Exception("TCP connect failed immediately: $errstr ($errno)");
        }

        $this->streamID = (int) $stream;
        $this->stream = $stream;

        $loop->registerHandler($this);

        $this->_updateState(self::_STATE_CONNECTING, false, true, false);

        // Устанавливаем буфер до начала SSL
        $socket = socket_import_stream($stream);
        socket_set_option($socket, SOL_SOCKET, SO_RCVBUF, 4 * 1024 * 1024);
        socket_set_option($socket, SOL_SOCKET, SO_SNDBUF, 4 * 1024 * 1024);

        stream_set_blocking($stream, false);

        // отключаем буферизацию php
        stream_set_read_buffer($stream, 0);
        stream_set_write_buffer($stream, 0);
    }

    public function disconnect() {
        fclose($this->stream);
        $this->_buffer = '';
        $this->_loop->updateHandlerTimeout($this, 0);
    }

    public function readyRead() {
        switch ($this->_state) {
            case self::_STATE_HANDSHAKE:
                $this->_checkHandshake();
                return;
            case self::_STATE_WEBSOCKET_READY:
                $readFrameLength = $this->_readFrameLength;
                $stream = $this->stream;
                $buffer = $this->_buffer;

                $called = false;

                // dynamic drain: если еще что-то осталось - увеличиваем буфер и читаем еще раз
                // это сильно экономит вызовы stream_select
                for ($drainIndex = 1; $drainIndex <= 10; $drainIndex++) {
                    $data = fread($stream, $readFrameLength * $drainIndex);

                    if ($data === false) {
                        // в неблокирующем режиме если данных нет - то будет string ''
                        // а если false - то это ошибка чтения
                        // например, PHP Warning: fread(): SSL: Connection reset by peer
                        $errorString = error_get_last()['message'];
                        throw new Connection_Exception("$errorString - failed to read from {$this->_host}:{$this->_port}");
                    } elseif ($data === '') {
                        // Если fread вернул пустую строку, проверяем, достигнут ли EOF
                        $this->_checkEOF();
                        // EOF: connection closed by remote host

                        break; // stop drain
                    }

                    $buffer .= $data; // дописываемся в буфер
                    $offset = 0;
                    $bufferLength = strlen($buffer);

                    while ($offset < $bufferLength) {
                        // Минимальный заголовок — 2 байта
                        if ($bufferLength - $offset < 2) {
                            break;  // Недостаточно данных для заголовка
                        }

                        $firstByte = ord($buffer[$offset]);
                        $secondByte = ord($buffer[$offset + 1]);

                        $opcode = $firstByte & 0x0F;
                        $isMasked = ($secondByte & 0b10000000) !== 0;
                        $payloadLength = $secondByte & 0b01111111;
                        $maskOffset = 2;

                        // Если длина полезной нагрузки равна 126 или 127 — читаем дополнительные байты длины
                        switch ($payloadLength) {
                            case 126:
                                if ($bufferLength - $offset < 4) {
                                    break(2); // Недостаточно данных для заголовка с расширенной длиной
                                }
                                $payloadLength = unpack('n', substr($buffer, $offset + 2, 2))[1];
                                $maskOffset = 4;
                                break;
                            case 127:
                                if ($bufferLength - $offset < 10) {
                                    break(2); // Недостаточно данных для заголовка с расширенной длиной
                                }
                                $payloadLength = unpack('J', substr($buffer, $offset + 2, 8))[1];
                                $maskOffset = 10;
                                break;
                        }

                        // Полная длина фрейма: заголовок, маска (если есть) и payload
                        $frameLength = $maskOffset + ($isMasked ? 4 : 0) + $payloadLength;
                        if ($bufferLength - $offset < $frameLength) {
                            break; // Ждем, когда придут все данные
                        }

                        // Если сообщение замаскировано — читаем маску
                        $mask = '';
                        if ($isMasked) {
                            $mask = substr($buffer, $offset + $maskOffset, 4);
                        }

                        // Читаем полезную нагрузку
                        $payload = substr($buffer, $offset + $maskOffset + ($isMasked ? 4 : 0), $payloadLength);

                        // Если сообщение замаскировано — дешифруем payload
                        if ($isMasked) {
                            $unmaskedPayload = '';
                            for ($i = 0; $i < $payloadLength; $i++) {
                                $unmaskedPayload .= $payload[$i] ^ $mask[$i % 4];
                            }
                            $payload = $unmaskedPayload;
                        }

                        // to locals
                        $callback = $this->_callbackMessage;

                        // Обработка опкодов
                        switch ($opcode) {
                            case 0x8: // FRAME CLOSED
                                $this->disconnect();
                                $cb = $this->_callbackError;
                                $cb(microtime(true), "StreamLoop_HandlerWSS: frame-closed");
                                break;
                            case 0x9: // FRAME PING
                                # debug:start
                                Cli::Print_n("StreamLoop_HandlerWSS: frame-ping $payload");
                                # debug:end

                                // тут очень важный нюанс:
                                // stream_select может выйти по таймауту, а может по ping.
                                // в случае pong таймаут будет продлен, поэтому нужно все равно вызывать callback,
                                // так как он ждет четкий loop по тайм-ауту 0.5..1.0 sec.
                                try {
                                    $callback(microtime(true), false);
                                    $called = true;
                                } catch (Exception $userException) {
                                    // тут вылетаем, но надо сделать disconnect
                                    $this->disconnect();
                                    throw $userException;
                                }

                                $encodedPong = $this->_encodeWebSocketMessage($payload, 0xA);
                                fwrite($stream, $encodedPong);
                                break;
                            case 0xA:
                                // FRAME PONG
                                # debug:start
                                Cli::Print_n("StreamLoop_HandlerWSS: frame-pong $payload");
                                # debug:end

                                // тут очень важный нюанс:
                                // stream_select может выйти по таймауту, а может по pong.
                                // в случае pong таймаут будет продлен, поэтому нужно все равно вызывать callback,
                                // так как он ждет четкий loop по тайм-ауту 0.5..1.0 sec.
                                try {
                                    $callback(microtime(true), false);
                                    $called = true;
                                } catch (Exception $userException) {
                                    // тут вылетаем, но надо сделать disconnect
                                    $this->disconnect();
                                    throw $userException;
                                }

                                // запоминаем когда пришел pong
                                $this->_tsPong = 0;
                                break;
                            default: // FRAME PAYLOAD
                                try {
                                    $callback(microtime(true), $payload);
                                    $called = true;
                                } catch (Exception $userException) {
                                    // тут вылетаем, но надо сделать disconnect
                                    $this->disconnect();
                                    throw $userException;
                                }
                                break;
                        }

                        // Сдвигаем указатель на следующий фрейм
                        $offset += $frameLength;
                    }

                    // Удаляем обработанные данные из буфера
                    $buffer = substr($buffer, $offset);

                    // если так окажется, то я что-то прочитал, но сообщение невозможно распарсить
                    // то я делаю пустое сообщение как-будто я пришел по timeout,
                    // это особенность именно websocket layer, потому что там фрейм может прилететь не полный и я его не распаршу,
                    // а вызвать что-то надо
                    if (!$called) {
                        try {
                            $callback(microtime(true), false);
                        } catch (Exception $userException) {
                            // тут вылетаем, но надо сделать disconnect
                            $this->disconnect();
                            throw $userException;
                        }
                    }
                }

                // сохраняем буфер или что от него осталось
                $this->_buffer = $buffer;

                // ping-pong в конце
                $ts = microtime(true);
                $this->_loop->updateHandlerTimeout($this, $ts + $this->_selectTimeout);
                $this->_checkPingPong($ts);

                return;
            case self::_STATE_WAITING_FOR_UPGRADE:
                $this->_checkUpgrade();
                return;
        }
    }

    public function readyWrite() {
        // to locals
        $stream = $this->stream;

        switch ($this->_state) {
            case self::_STATE_CONNECTING:
                // коннект установился, я готов к записи
                stream_context_set_option($stream, array(
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                    ],
                ));
                stream_context_set_option($stream, 'ssl', 'peer_name', $this->_host);
                stream_context_set_option($stream, 'ssl', 'allow_self_signed', true);

                $this->_updateState(self::_STATE_HANDSHAKE, true, true, false);
                $this->_checkHandshake();
                return;
            case self::_STATE_HANDSHAKE:
                $this->_checkHandshake();
                return;
            case self::_STATE_WAITING_FOR_UPGRADE:
                $this->_checkUpgrade();
                return;
            case self::_STATE_READY:
                $key = base64_encode(random_bytes(16)); // Уникальный ключ для Handshake
                $headers = "GET {$this->_path} HTTP/1.1\r\n"
                    . "Host: {$this->_host}\r\n"
                    . "Upgrade: websocket\r\n"
                    . "Connection: Upgrade\r\n"
                    . "Sec-WebSocket-Key: $key\r\n"
                    . "Sec-WebSocket-Version: 13\r\n"
                    . "\r\n";
                fwrite($stream, $headers);
                $this->_updateState(self::_STATE_WAITING_FOR_UPGRADE, true, false, false);
                $this->_checkUpgrade();
                return;
        }
    }

    public function readyExcept() {
        $this->_checkEOF();

        switch ($this->_state) {
            case self::_STATE_HANDSHAKE:
                $this->_checkHandshake();
                return;
            case self::_STATE_WAITING_FOR_UPGRADE:
                $this->_checkUpgrade();
                return;
        }
    }

    public function readySelectTimeout() {
        // для WSS фиксированно задан период 0.25 сек когда он должен слать что-то что он жив,
        // это и есть _FRAME_SELECT_TIMEOUT
        // он срабатывает на stream_select
        // если ничего не пришло - пушим это сообщение

        if ($this->_state != self::_STATE_WEBSOCKET_READY) {
            return;
        }

        $ts = microtime(true);
        $this->_loop->updateHandlerTimeout($this, $ts + $this->_selectTimeout);

        // frame select timeout
        try {
            $callback = $this->_callbackMessage;
            $callback($ts, '');
        } catch (Exception $userException) {
            // тут вылетаем, но надо сделать disconnect
            $this->disconnect();
            throw $userException;
        }

        $this->_checkPingPong($ts);
    }

    private function _checkPingPong($ts) {
        // websocket layer ping
        // auto ping frame
        if ($ts - $this->_tsPing >= $this->_pingInterval) {
            $encodedPing = $this->_encodeWebSocketMessage('', 9);
            fwrite($this->stream, $encodedPing);

            # debug:start
            Cli::Print_n("StreamLoop_HandlerWSS: sent frame-ping");
            # debug:end

            $this->_tsPing = $ts;
            // дедлайн до которого должен прийти pong
            $this->_tsPong = $ts + $this->_pongDeadline;
        }

        if ($this->_tsPong > 0 && $ts > $this->_tsPong) {
            // если задан дедлайн pong,
            // и время уже больше этого дедлайна, то это означает что pong не пришет
            // и мы идем на выход
            $this->disconnect();
            throw new Connection_Exception("StreamLoop_HandlerWSS: no frame-pong - exit");
        }
    }

    private function _checkUpgrade() {
        $line = fgets($this->stream, 4096);
        if ($line === false) {
            $this->_checkEOF();
        } else {
            $this->_buffer .= $line; // @todo locals
            // пустая строка — конец блока заголовков
            if ($line == "\r\n" || $line == "\n") {
                if (!str_contains($this->_buffer, '101 Switching Protocols')) {
                    throw new StreamLoop_Exception("Handshake error: ".$this->_buffer);
                }

                // вот тут опционально ебашим writeArray если он передан
                if ($this->_writeArray) {
                    foreach ($this->_writeArray as $msg) {
                        $this->write($msg);
                    }
                }

                $this->_updateState(
                    self::_STATE_WEBSOCKET_READY,
                    true,
                    false,
                    false,
                );
                $this->_buffer = '';

                $this->_tsPing = 0;
                $this->_tsPong = 0;
            }
        }
    }

    private function _checkEOF() {
        if (feof($this->stream)) {
            $this->disconnect();

            $cb = $this->_callbackError;
            $cb(microtime(true), 'EOF');
        }
    }

    private function _checkHandshake() {
        $this->_checkEOF();

        $return = stream_socket_enable_crypto(
            $this->stream,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if ($return === false) {
            throw new StreamLoop_Exception("Failed to setup SSL");
        }

        if ($return === true) {
            $this->_updateState(self::_STATE_READY, false, true, false);
        }
    }

    private function _updateState($state, $flagRead, $flagWrite, $flagExcept) {
        // @todo maybe inline
        $this->_state = $state;
        $this->_loop->updateHandlerFlags($this, $flagRead, $flagWrite, $flagExcept);
    }

    public function write($data) {
        $data = $this->_encodeWebSocketMessage($data);
        fwrite($this->stream, $data);
    }

    public function setReadFrameLength($length) {
        $this->_readFrameLength = $length;
    }

    /**
     * Функция для кодирования сообщений с маскировкой в WebSocket Frame
     *
     * @param $message
     * @param $opcode
     * @return string
     * @throws \Random\RandomException
     */
    private function _encodeWebSocketMessage($message, $opcode = 1) {
        $frame = [];
        $frame[0] = 128 | $opcode; // Финальный фрейм (FIN) и тип фрейма (текст или пинг)

        $length = strlen($message);
        $mask = random_bytes(4); // Генерация 4-байтового маскирующего ключа

        if ($length <= 125) {
            $frame[1] = $length | 0b10000000; // Устанавливаем бит маскировки
        } elseif ($length <= 65535) {
            $frame[1] = 126 | 0b10000000; // Устанавливаем бит маскировки
            $frame[2] = ($length >> 8) & 255;
            $frame[3] = $length & 255;
        } else {
            $frame[1] = 127 | 0b10000000; // Устанавливаем бит маскировки
            for ($i = 0; $i < 8; $i++) {
                $frame[$i + 2] = ($length >> (8 * (7 - $i))) & 255;
            }
        }

        // Добавляем маскирующий ключ
        $maskLength = strlen($mask);
        for ($i = 0; $i < $maskLength; $i++) {
            $frame[] = ord($mask[$i]);
        }

        // Маскируем сообщение
        $maskedMessage = '';
        for ($i = 0; $i < $length; $i++) {
            $maskedMessage .= chr(ord($message[$i]) ^ ord($mask[$i % 4]));
        }

        // Добавляем маскированное сообщение в фрейм
        $smm = str_split($maskedMessage);
        foreach ($smm as $char) {
            $frame[] = ord($char);
        }

        $result = '';
        foreach ($frame as $value) {
            $result .= chr($value);
        }
        return $result;
    }

    private $_host, $_port, $_path, $_ip;
    private $_writeArray;
    private $_callbackMessage, $_callbackError;
    private $_buffer = '';
    private $_state = 0;

    // @todo int-const
    private const _STATE_CONNECTING = 1;
    private const _STATE_HANDSHAKE = 2;
    private const _STATE_READY = 3;
    private const _STATE_WAITING_FOR_UPGRADE = 4;
    private const _STATE_WEBSOCKET_READY = 5;
    private $_tsPing = 0;
    private $_tsPong = 0;
    private $_pingInterval = 1;
    private $_pongDeadline = 3;
    private $_selectTimeout = 0.25; // @todo setup
    private $_readFrameLength = 512;

}