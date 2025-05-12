<?php
class StreamLoop_HandlerWSS extends StreamLoop_AHandler {

    public function __construct($host, $port, $path, $writeArray, $ip) {
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
        $this->_state = new StreamLoop_HandlerWSS_StateMachine();

        $this->_buffer = '';

        $this->_updateState(StreamLoop_HandlerWSS_StateMachine::CONNECTING, false, true, false);

        $this->stream = stream_socket_client(
            "tcp://{$this->_ip}:{$this->_port}",
            $errno,
            $errstr,
            0, // timeout = 0, чтобы мгновенно вернулось
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT,
            stream_context_create() // без ssl-опций!
        );
        if (!$this->stream) {
            throw new StreamLoop_Exception("TCP connect failed immediately: $errstr ($errno)");
        }

        stream_set_blocking($this->stream, false);

        // отключаем буферизацию php
        stream_set_read_buffer($this->stream, 0);
        stream_set_write_buffer($this->stream, 0);
    }

    public function disconnect() {
        fclose($this->stream);
        $this->_buffer = '';
        $this->timeoutTo = 0;
    }

    public function readyRead() {
        $this->_checkEOF();

        switch ($this->_state->getState()) {
            case StreamLoop_HandlerWSS_StateMachine::HANDSHAKE:
                $this->_checkHandshake();
                return;
            case StreamLoop_HandlerWSS_StateMachine::WEBSOCKET_READY:
                $ts = microtime(true);
                $this->timeoutTo = $ts + $this->_selectTimeout;
                $this->_checkPingPong($ts);
                $this->_checkRead();
                return;
            case StreamLoop_HandlerWSS_StateMachine::WAITING_FOR_UPGRADE:
                $this->_checkUpgrade();
                return;
        }
    }

    public function readyWrite() {
        switch ($this->_state->getState()) {
            case StreamLoop_HandlerWSS_StateMachine::CONNECTING:
                // коннект установился, я готов к записи
                stream_context_set_option($this->stream, array(
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                    ],
                ));
                stream_context_set_option($this->stream, 'ssl', 'peer_name', $this->_host);
                stream_context_set_option($this->stream, 'ssl', 'allow_self_signed', true);

                $this->_updateState(StreamLoop_HandlerWSS_StateMachine::HANDSHAKE, true, true, false);
                $this->_checkHandshake();
                return;
            case StreamLoop_HandlerWSS_StateMachine::HANDSHAKE:
                $this->_checkHandshake();
                return;
            case StreamLoop_HandlerWSS_StateMachine::WAITING_FOR_UPGRADE:
                $this->_checkUpgrade();
                return;
            case StreamLoop_HandlerWSS_StateMachine::READY:
                $key = base64_encode(random_bytes(16)); // Уникальный ключ для Handshake
                $headers = "GET {$this->_path} HTTP/1.1\r\n"
                    . "Host: {$this->_host}\r\n"
                    . "Upgrade: websocket\r\n"
                    . "Connection: Upgrade\r\n"
                    . "Sec-WebSocket-Key: $key\r\n"
                    . "Sec-WebSocket-Version: 13\r\n"
                    . "\r\n";
                fwrite($this->stream, $headers);
                $this->_updateState(StreamLoop_HandlerWSS_StateMachine::WAITING_FOR_UPGRADE, true, false, false);
                $this->_checkUpgrade();
                return;
        }
    }

    public function readyExcept() {
        $this->_checkEOF();

        switch ($this->_state->getState()) {
            case StreamLoop_HandlerWSS_StateMachine::HANDSHAKE:
                $this->_checkHandshake();
                return;
            case StreamLoop_HandlerWSS_StateMachine::WAITING_FOR_UPGRADE:
                $this->_checkUpgrade();
                return;
        }
    }

    public function readySelectTimeout() {
        if ($this->_state->getState() != StreamLoop_HandlerWSS_StateMachine::WEBSOCKET_READY) {
            return;
        }

        $ts = microtime(true);
        $this->timeoutTo = $ts + $this->_selectTimeout;

        $msgArray = [];
        $msgArray[] = [self::_FRAME_SELECT_TIMEOUT, ''];
        $this->_processMsgArray($ts, $msgArray);

        $this->_checkPingPong($ts);
    }

    private function _checkPingPong($ts) {
        // websocket layer ping
        // auto ping frame
        if ($ts - $this->_tsPing >= $this->_pingInterval) {
            $this->_sendPingFrame();
            Cli::Print_n("Connection_WebSocket: sent iframe-ping");
            $this->_tsPing = $ts;
            // дедлайн до которого должен прийти pong
            $this->_tsPong = $ts + $this->_pongDeadline;
        }

        if ($this->_tsPong > 0 && $ts > $this->_tsPong) {
            // если задан дедлайн pong,
            // и время уже больше этого дедлайна, то это означает что pong не пришет
            // и мы идем на выход
            $this->disconnect();
            throw new Connection_Exception("Connection_WebSocket: no iframe-pong - exit");
        }
    }

    private function _checkRead() {
        $data = fread($this->stream, 2000);
        $ts = microtime(true);

        // в неблокирующем режиме если данных нет - то будет string ''
        // а если false - то это ошибка чтения
        // например, PHP Warning: fread(): SSL: Connection reset by peer
        if ($data === false) {
            $errorString = error_get_last()['message'];
            throw new Connection_Exception("$errorString - failed to read from {$this->_host}:{$this->_port}");
        }

        // Если fread вернул пустую строку, проверяем, достигнут ли EOF
        if ($data === '') {
            $this->_checkEOF();
            return;
        }

        $this->_buffer .= $data;
        $msgArray = $this->_decodeMessageArray();

        // если так окажется, то я что-то прочитал, но сообщение невозможно распарсить
        // то я делаю пустое сообщение как-будто я пришел по timeout,
        // это особенность изменно websocket layer, потому что там фрейм может прилететь не полный и я его не распаршу,
        // а вызвать что-то надо
        if (!$msgArray) {
            $msgArray[] = [self::_FRAME_SELECT_TIMEOUT, ''];
        }
        $this->_processMsgArray($ts, $msgArray);
    }

    private function _processMsgArray($ts, $msgArray) {
        foreach ($msgArray as $msg) {
            $msgType = $msg[0];
            $msgData = $msg[1];

            switch ($msgType) {
                case self::_FRAME_PING:
                    Cli::Print_n("Connection_WebSocket: received iframe-ping $msgData");
                    $this->_sendPongFrame($msgData);
                    break;
                case self::_FRAME_PONG:
                    Cli::Print_n("Connection_WebSocket: received iframe-pong $msgData");

                    // запоминаем когда пришел pong
                    $this->_tsPong = 0;

                    // тут очень важный нюанс:
                    // stream_select может выйти по таймауту, а может по pong.
                    // в случае pong таймаут будет продлен, поэтому нужно все равно вызывать $callback,
                    // потому что внутри callback может быть логика, которая ожидает что она будет выполняться ровно каждые 0.5..1.0 sec,
                    // например тот же DRSTC snapshot (S) или application layer ping.
                    try {
                        $callback = $this->_callbackMessage;
                        $callback($ts, false);
                    } catch (Exception $userException) {
                        // тут вылетаем, но надо сделать disconnect
                        $this->disconnect();
                        throw $userException;
                    }
                    break;
                case self::_FRAME_CLOSED:
                    $this->disconnect();
                    $cb = $this->_callbackError;
                    $cb($ts, "WebSocket: iframe-closed");
                    break;
                case self::_FRAME_SELECT_TIMEOUT:
                case self::_FRAME_DATA:
                    try {
                        $callback = $this->_callbackMessage;
                        $callback($ts, $msgData);
                    } catch (Exception $userException) {
                        // тут вылетаем, но надо сделать disconnect
                        $this->disconnect();
                        throw $userException;
                    }
                    break;
                default:
                    throw new Connection_Exception("WebSocket type $msgType not implemented");
            }
        }
    }

    private function _checkUpgrade() {
        $line = fgets($this->stream, 2048);
        if ($line !== false) {
            $this->_buffer .= $line;
            // пустая строка — конец блока заголовков
            if ($line == "\r\n" || $line == "\n") {
                if (strpos($this->_buffer, '101 Switching Protocols') === false) {
                    throw new StreamLoop_Exception("Handshake error: ".$this->_buffer);
                }

                // вот тут опционально ебашим writeArray если он передан
                if ($this->_writeArray) {
                    foreach ($this->_writeArray as $msg) {
                        $this->write($msg);
                    }
                }

                $this->_updateState(
                    StreamLoop_HandlerWSS_StateMachine::WEBSOCKET_READY,
                    true,
                    false,
                    false,
                );
                $this->_buffer = '';

                $this->_tsPing = 0;
                $this->_tsPong = 0;

                return;
            }
        }
    }

    private function _checkEOF() {
        if (feof($this->stream)) {
            $this->disconnect();

            $cb = $this->_callbackError;
            $cb(microtime(true), "EOF");
        }
    }

    private function _checkHandshake() {
        $return = stream_socket_enable_crypto(
            $this->stream,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if ($return === false) {
            throw new StreamLoop_Exception("Failed to setup SSL");
        }

        if ($return === true) {
            $this->_updateState(StreamLoop_HandlerWSS_StateMachine::READY, false, true, false);
        }
    }

    private function _updateState($state, $flagRead, $flagWrite, $flagExcept) {
        $this->_state->setState($state);
        $this->flagRead = $flagRead;
        $this->flagWrite = $flagWrite;
        $this->flagExcept = $flagExcept;
    }

    private function _decodeMessageArray() {
        $messages = [];
        $offset = 0;
        $bufferLength = strlen($this->_buffer);

        while ($offset < $bufferLength) {
            // Минимальный заголовок — 2 байта
            if ($bufferLength - $offset < 2) {
                break;  // Недостаточно данных для заголовка
            }

            $firstByte = ord($this->_buffer[$offset]);
            $secondByte = ord($this->_buffer[$offset + 1]);

            $opcode = $firstByte & 0x0F;
            $isMasked = ($secondByte & 0b10000000) !== 0;
            $payloadLength = $secondByte & 0b01111111;
            $maskOffset = 2;

            // Если длина полезной нагрузки равна 126 или 127 — читаем дополнительные байты длины
            if ($payloadLength === 126) {
                if ($bufferLength - $offset < 4) {
                    break; // Недостаточно данных для заголовка с расширенной длиной
                }
                $payloadLength = unpack('n', substr($this->_buffer, $offset + 2, 2))[1];
                $maskOffset = 4;
            } elseif ($payloadLength === 127) {
                if ($bufferLength - $offset < 10) {
                    break; // Недостаточно данных для заголовка с расширенной длиной
                }
                $payloadLength = unpack('J', substr($this->_buffer, $offset + 2, 8))[1];
                $maskOffset = 10;
            }

            // Полная длина фрейма: заголовок, маска (если есть) и payload
            $frameLength = $maskOffset + ($isMasked ? 4 : 0) + $payloadLength;
            if ($bufferLength - $offset < $frameLength) {
                break; // Ждем, когда придут все данные
            }

            // Если сообщение замаскировано — читаем маску
            $mask = '';
            if ($isMasked) {
                $mask = substr($this->_buffer, $offset + $maskOffset, 4);
            }

            // Читаем полезную нагрузку
            $payload = substr($this->_buffer, $offset + $maskOffset + ($isMasked ? 4 : 0), $payloadLength);

            // Если сообщение замаскировано — дешифруем payload
            if ($isMasked) {
                $unmaskedPayload = '';
                for ($i = 0; $i < $payloadLength; $i++) {
                    $unmaskedPayload .= $payload[$i] ^ $mask[$i % 4];
                }
                $payload = $unmaskedPayload;
            }

            // Обработка опкодов
            if ($opcode === 0x8) {
                $messages[] = [self::_FRAME_CLOSED, $payload];
            } elseif ($opcode === 0xA) {
                $messages[] = [self::_FRAME_PONG, $payload];
            } elseif ($opcode === 0x9) {
                $messages[] = [self::_FRAME_PING, $payload];
            } else {
                $messages[] = [self::_FRAME_DATA, $payload];
            }

            // Сдвигаем указатель на следующий фрейм
            $offset += $frameLength;
        }

        // Удаляем обработанные данные из буфера
        $this->_buffer = substr($this->_buffer, $offset);

        return $messages;
    }

    public function write($data) {
        $data = $this->_encodeWebSocketMessage($data);
        fwrite($this->stream, $data);
    }

    private function _sendPingFrame($payload = '') {
        $encodedPing = $this->_encodeWebSocketMessage($payload, 9);
        fwrite($this->stream, $encodedPing);
    }

    private function _sendPongFrame($payload = '') {
        $encodedPong = $this->_encodeWebSocketMessage($payload, 0xA);
        fwrite($this->stream, $encodedPong);
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
        foreach (str_split($maskedMessage) as $char) {
            $frame[] = ord($char);
        }

        $result = '';
        foreach ($frame as $value) {
            $result .= chr($value);
        }
        return $result;
    }

    private $_host, $_port, $_path, $_ip, $_writeArray;
    private $_callbackMessage, $_callbackError;
    private $_buffer = '';

    private StreamLoop_HandlerWSS_StateMachine $_state;

    private $_tsPing = 0;
    private $_tsPong = 0;
    private $_pingInterval = 1;
    private $_pongDeadline = 3;

    private const _FRAME_PING = 'frame-ping';
    private const _FRAME_PONG = 'frame-pong';
    private const _FRAME_CLOSED = 'frame-closed';
    private const _FRAME_DATA = 'frame-data';
    private const _FRAME_SELECT_TIMEOUT = 'frame-select-timeout';
    private $_selectTimeout = 0.25; // @todo setup

}