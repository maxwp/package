<?php
class StreamLoop_WebSocket extends StreamLoop_AHandler {

    public function __construct(StreamLoop $loop, $host, $port, $path, $writeArray, $ip = false, $bindPort = false) {
        parent::__construct($loop);

        $this->_host = $host;
        $this->_port = $port;
        $this->_path = $path;
        $this->_writeArray = $writeArray;
        $this->_ip = $ip ? $ip : $this->_host;
        $this->_bindPort = (int) $bindPort;

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

        // супер важно: надо создавать контекст без ssl-опций!
        $context = stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,  // no Nagle algorithm
                'bindto' => "0.0.0.0:{$this->_bindPort}",
            ],
        ]);

        $stream = stream_socket_client(
            "tcp://{$this->_ip}:{$this->_port}",
            $errno,
            $errstr,
            0, // timeout = 0, чтобы мгновенно вернулось
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT,
            $context, // @todo возмоэно надо будет таки перенести контекст из Connection_WebSocket
        );
        if (!$stream) {
            throw new StreamLoop_Exception("TCP connect failed immediately: $errstr ($errno)");
        }

        $this->streamID = (int) $stream;
        $this->stream = $stream;

        $loop->registerHandler($this);

        $this->_updateState(self::_STATE_CONNECTING, false, true, false);

        // Устанавливаем буфер до начала SSL
        $socket = new Connection_SocketStream($stream);
        $socket->setBufferSizeRead(10 * 1024 * 1024);
        $socket->setBufferSizeWrite(2 * 1024 * 1024);

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

    public function readyRead($tsSelect) {
        switch ($this->_state) {
            case self::_STATE_HANDSHAKE:
                $this->_checkHandshake($tsSelect);
                return;
            case self::_STATE_WEBSOCKET_READY:
                $readFrameLength = $this->_readFrameLength;
                $readFrameDrain = $this->_readFrameDrain;
                $stream = $this->stream;
                $buffer = $this->_buffer;

                $called = false;

                // dynamic drain: если после вычитки большого пакета fread() он считался ровно впритык - то вызываем
                // чтение еще раз и так до drainLimit.
                // Надо стараться делать меньше fread (syscall overhead), но если все-таки данных много - то лучше читать
                // еще раз, чтобы не ждать нового круга stream_select(). Но опять-таки, это сильно зависит от количество
                // потоков внутри всего StreamLoop и насколько я могу затупить на одном handler'e.
                for ($drainIndex = 1; $drainIndex <= $readFrameDrain; $drainIndex++) {
                    $data = fread($stream, $readFrameLength);

                    $buffer .= $data; // дописываемся в буфер
                    $offset = 0;
                    $bufferLength = strlen($buffer);

                    while ($offset < $bufferLength) {
                        // Минимальный заголовок — 2 байта
                        if ($bufferLength - $offset < 2) {
                            break;
                        }

                        $secondByte = ord($buffer[$offset + 1]);
                        $lenFlag = $secondByte & 0x7F;
                        $isMasked = (bool) ($secondByte & 0x80);

                        switch ($lenFlag) {
                            case 126:
                                $maskOffset = 4;
                                if ($bufferLength - $offset < $maskOffset) {
                                    break(2);
                                }
                                break;
                            case 127:
                                $maskOffset = 10;
                                if ($bufferLength - $offset < $maskOffset) {
                                    break(2);
                                }
                                break;
                            default:
                                $maskOffset = 2;
                                break;
                        }

                        $head = substr($buffer, $offset, $maskOffset);
                        $fmt  = $maskOffset === 2 ? 'Cfirst/Csecond' : ($maskOffset === 4 ? 'Cfirst/Csecond/nlen' : 'Cfirst/Csecond/Jlen');
                        $parts = unpack($fmt, $head);
                        $opcode = $parts['first'] & 0x0F;
                        $payloadLength = $parts['len'] ?? ($parts['second'] & 0x7F);
                        $frameLength = $maskOffset + ($isMasked ? 4 : 0) + $payloadLength;

                        if ($bufferLength - $offset < $frameLength) {
                            break;
                        }

                        if ($isMasked) {
                            $maskKey = substr($buffer, $offset + $maskOffset, 4);
                        } else {
                            $maskKey = '';
                        }

                        $payload = substr(
                            $buffer,
                            $offset + $maskOffset + ($isMasked ? 4 : 0),
                            $payloadLength
                        );

                        if ($isMasked) {
                            for ($j = 0; $j < $payloadLength; $j++) {
                                $payload[$j] = chr(
                                    ord($payload[$j]) ^ ord($maskKey[$j & 3])
                                );
                            }
                        }

                        // Обработка опкодов
                        switch ($opcode) {
                            case 0x8: // FRAME CLOSED
                                $this->disconnect();
                                ($this->_callbackError)($this, microtime(true), "StreamLoop_HandlerWSS: frame-closed");
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
                                    ($this->_callbackMessage)($this, $tsSelect, microtime(true), false);
                                    $called = true;
                                } catch (Exception $userException) {
                                    // тут вылетаем, но надо сделать disconnect
                                    $this->disconnect();
                                    throw $userException;
                                }

                                $encodedPong = $this->_encodeWebSocketMessage($payload, 0xA); // frame ping
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
                                    ($this->_callbackMessage)($this, $tsSelect, microtime(true), false);
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
                                    ($this->_callbackMessage)($this, $tsSelect, microtime(true), $payload);
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

                    // Если fread вернул меньше, чем запрошено — дальше не дренируем
                    if ($data === false) {
                        // в неблокирующем режиме если данных нет - то будет string ''
                        // а если false - то это ошибка чтения
                        // например, PHP Warning: fread(): SSL: Connection reset by peer
                        $errorString = error_get_last()['message'];
                        throw new Connection_Exception("$errorString - failed to read from {$this->_host}:{$this->_port}");
                    } elseif ($data === '') {
                        // Если fread вернул пустую строку, проверяем, достигнут ли EOF
                        $this->_checkEOF($tsSelect); // EOF: connection closed by remote host
                        break;
                    } elseif (strlen($data) < $readFrameLength) {
                        break;
                    }
                    // Иначе loop идёт дальше, возможно есть новые данные
                }

                // если так окажется, то я что-то прочитал, но сообщение невозможно распарсить
                // то я делаю пустое сообщение как-будто я пришел по timeout,
                // это особенность именно websocket layer, потому что там фрейм может прилететь не полный и я его не распаршу,
                // а вызвать что-то надо
                // @todo можно закосить после отказа от ws timeout
                if (!$called) {
                    try {
                        ($this->_callbackMessage)($this, $tsSelect, microtime(true), false);
                    } catch (Exception $userException) {
                        // тут вылетаем, но надо сделать disconnect
                        $this->disconnect();
                        throw $userException;
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
                $this->_checkUpgrade($tsSelect);
                return;
        }
    }

    public function readyWrite($tsSelect) {
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
                $this->_checkHandshake($tsSelect);
                return;
            case self::_STATE_HANDSHAKE:
                $this->_checkHandshake($tsSelect);
                return;
            case self::_STATE_WAITING_FOR_UPGRADE:
                $this->_checkUpgrade($tsSelect);
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
                $this->_checkUpgrade($tsSelect);
                return;
        }
    }

    public function readyExcept($tsSelect) {
        switch ($this->_state) {
            case self::_STATE_HANDSHAKE:
                $this->_checkHandshake($tsSelect);
                return;
            case self::_STATE_WAITING_FOR_UPGRADE:
                $this->_checkEOF($tsSelect);
                $this->_checkUpgrade($tsSelect);
                return;
        }
    }

    public function readySelectTimeout($tsSelect) {
        // для WSS фиксированно задан период 0.25 сек когда он должен слать что-то что он жив,
        // это и есть _FRAME_SELECT_TIMEOUT
        // он срабатывает на stream_select
        // если ничего не пришло - пушим это сообщение

        if ($this->_state != self::_STATE_WEBSOCKET_READY) {
            return;
        }

        $ts = microtime(true);

        // frame select timeout
        try {
            $callback = $this->_callbackMessage;
            $callback($this, $tsSelect, $ts, false);
        } catch (Exception $userException) {
            // тут вылетаем, но надо сделать disconnect
            $this->disconnect();
            throw $userException;
        }

        $this->_loop->updateHandlerTimeout($this, $ts + $this->_selectTimeout);

        $this->_checkPingPong(microtime(true));
    }

    private function _checkPingPong($ts) {
        // websocket layer ping
        // auto ping frame
        if ($ts - $this->_tsPing >= $this->_pingInterval) {
            $encodedPing = $this->_encodeWebSocketMessage('', 9); // ping
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

    private function _checkUpgrade($tsSelect) {
        $line = fgets($this->stream, 4096);
        if ($line === false) {
            $this->_checkEOF($tsSelect);
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

    private function _checkEOF($tsSelect) {
        if (feof($this->stream)) {
            $this->disconnect();

            $cb = $this->_callbackError;
            $cb($this, $tsSelect, microtime(true), 'EOF');
        }
    }

    private function _checkHandshake($tsSelect) {
        $this->_checkEOF($tsSelect);

        $return = stream_socket_enable_crypto(
            $this->stream,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        // тут нужны ===, потому что если вернется int 0 - то надо пробовать еще раз
        if ($return === true) {
            $this->_updateState(self::_STATE_READY, false, true, false);
        } elseif ($return === false) {
            throw new StreamLoop_Exception("Failed to setup SSL");
        }
    }

    private function _updateState($state, $flagRead, $flagWrite, $flagExcept) {
        // @todo maybe inline
        $this->_state = $state;
        $this->_loop->updateHandlerFlags($this, $flagRead, $flagWrite, $flagExcept);
    }

    public function write($data) {
        $data = $this->_encodeWebSocketMessage($data); // write (usually once)
        fwrite($this->stream, $data);
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
        $length = strlen($message);
        $mask = random_bytes(4);

        // 1. FIN + opcode
        $frame = chr(0x80 | $opcode);

        // 2. Длина и, при необходимости, extended-length
        if ($length <= 125) {
            $frame .= chr(0x80 | $length);
        } elseif ($length <= 0xFFFF) {
            $frame .= chr(0x80 | 126)
                . chr(($length >> 8) & 0xFF)
                . chr($length & 0xFF);
        } else {
            $frame .= chr(0x80 | 127);
            for ($i = 7; $i >= 0; $i--) {
                $frame .= chr(($length >> ($i * 8)) & 0xFF);
            }
        }

        // 3. Маска
        $frame .= $mask;

        // 4. Сразу маскируем и доклеиваем payload вариант с циклом:
        for ($i = 0; $i < $length; $i++) {
            $frame .= chr(ord($message[$i]) ^ ord($mask[$i & 3]));
        }

        return $frame;
    }

    // @todo no drain in HTTPS
    // @todo no drain in C_WS
    public function setReadFrame(int $length, int $drain) {
        if ($length <= 1) {
            throw new StreamLoop_Exception("Length must be a positive integer");
        }
        if ($drain < 0) {
            throw new StreamLoop_Exception("Drain must be a positive integer");
        }
        $this->_readFrameLength = $length;
        $this->_readFrameDrain = $drain;
    }

    private $_host, $_port, $_path, $_ip, $_bindPort;
    private $_writeArray;
    private $_callbackMessage, $_callbackError;
    private $_buffer = '';
    private $_state = 0;

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
    private $_readFrameLength = 4096; // 4Kb by default
    private $_readFrameDrain = 1;

}