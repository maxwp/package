<?php
/**
 * Важное отличие StreamLoop_WebSocket от Connection_WebSocket:
 * SL_WS вызывает selectTimeout только ради websocket-layer frame-ping-pong, он не вызывает его
 * ради пустых callback.
 * Если нужны пустые callback - то надо добавлять timer внутрь StreamLoop.
 * Такой подход делает меньше вызовов selectTimeout и позволяет держать сильно больше WebSocket-handler-ов внутри одного StreamLoop,
 * но timer не будет синхронизирован с last event от websocket. Хотя он и в C_WS может быть не синхронизирован из-за app-layer & iframe-layer ping-pong.
 */
abstract class StreamLoop_WebSocket_Abstract extends StreamLoop_Handler_Abstract {

    abstract protected function _onInit();
    abstract protected function _onReceive($tsSelect, $payload, $opcode);
    abstract protected function _onError($tsSelect, $errorCode, $errorMessage);
    abstract protected function _onReady($tsSelect);

    // @todo возможно вернуть EE Events чтобы красиво бросать события, бо якась хуйня повторяется везде

    // @todo как слепить в кучу websocket over https?
    // @todo сначала надо придумать как сделать StateMachine, чтобы я мог помещать команду с событиями onXXX,
    // и затем handshake и switching protocol снанут этими командами

    public function updateConnection($host, $port, $path, $writeArray, $ip = false, $headerArray = [], $bindIP = false, $bindPort = false) {
        // @todo возможно структура connection'a?
        $this->_host = $host;
        $this->_port = $port;
        $this->_path = $path;
        $this->_writeArray = $writeArray;
        $this->_headerArray = $headerArray;
        $this->_ip = $ip ? $ip : $this->_host;

        if ($bindIP) {
            if (!Checker::CheckIP($bindIP)) {
                throw new StreamLoop_Exception("Invalid Bind IP $bindIP");
            }
        } else {
            $bindIP = '0.0.0.0'; // any ip
        }

        if ($bindPort) {
            if (!Checker::CheckPort($bindPort)) {
                throw new StreamLoop_Exception("Invalid Port $bindPort");
            }
        } else {
            $bindPort = 0; // any port
        }

        $this->_bindIP = $bindIP;
        $this->_bindPort = $bindPort;
    }

    public function connect() {
        $this->_buffer = '';

        // to locals
        $loop = $this->_loop;

        // супер важно: надо создавать контекст без ssl-опций!
        $context = stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,  // no Nagle algorithm
                'bindto' => "{$this->_bindIP}:{$this->_bindPort}",
            ],
        ]);

        $stream = stream_socket_client(
            "tcp://{$this->_ip}:{$this->_port}",
            $errno,
            $errstr,
            0, // timeout = 0, чтобы мгновенно вернулось
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT,
            $context, // @todo возможно надо будет таки перенести контекст из Connection_WebSocket
        );
        if (!$stream) {
            // @todo шо делать с такой ошибкой?
            throw new StreamLoop_Exception("TCP connect failed immediately: $errstr ($errno)");
        }

        $this->streamID = (int) $stream;
        $this->stream = $stream;

        $loop->registerHandler($this);

        // Устанавливаем буфер до начала SSL
        $socket = new Connection_SocketStream($stream);
        $socket->setBufferSizeRead(10 * 1024 * 1024);
        $socket->setBufferSizeWrite(2 * 1024 * 1024);
        $socket->setKeepAlive();
        $socket->setQuickACK(1);

        stream_set_blocking($stream, false);

        // отключаем буферизацию php
        stream_set_read_buffer($stream, 0);
        stream_set_write_buffer($stream, 0);

        $this->_updateState(StreamLoop_WebSocket_Const::STATE_CONNECTING, false, true, true);

        // устанавливаем timeout на connect + handshake + upgrade
        $this->_loop->updateHandlerTimeoutTo($this, time() + 10);

        $this->_onInit();
    }

    public function disconnect() {
        // дисконнект закрывает снимает регистрацию handler'a и закрывает stream.
        // это приводит к тому, что SL временно забывает про handler и не ебет его
        $this->_loop->unregisterHandler($this);

        // бывают ситуации когда throwError два раза подряд и тогда disconnect два раза подряд
        if ($this->stream) {
            fclose($this->stream);
        }

        $this->_buffer = '';
        $this->_state = StreamLoop_WebSocket_Const::STATE_STOPPED;
    }

    public function readyRead($tsSelect) {
        $state = $this->_state; // to locals
        // if-tree optimization
        if ($state == StreamLoop_WebSocket_Const::STATE_READY) {
            $readFrameLength = $this->_readFrameLength;
            $readFrameDrain = $this->_readFrameDrain;
            $stream = $this->stream;
            $buffer = $this->_buffer;

            // dynamic drain: если после вычитки большого пакета fread() он считался ровно впритык - то вызываем
            // чтение еще раз и так до drainLimit.
            // Надо стараться делать меньше fread (syscall overhead), но если все-таки данных много - то лучше читать
            // еще раз, чтобы не ждать нового круга stream_select(). Но опять-таки, это сильно зависит от количество
            // потоков внутри всего StreamLoop и насколько я могу затупить на одном handler'e.
            for ($drainIndex = 1; $drainIndex <= $readFrameDrain; $drainIndex++) {
                $data = fread($stream, $readFrameLength);
                $length = strlen($data);

                // чаще всего будет срабатывать length > 0
                if ($length > 0) {
                    $buffer .= $data;
                    $offset = 0;
                    $bufferLength = strlen($buffer);

                    while ($offset < $bufferLength) {
                        // Минимальный заголовок — 2 байта
                        if ($bufferLength - $offset < 2) {
                            break;
                        }

                        $secondByte = ord($buffer[$offset + 1]);
                        $lenFlag = $secondByte & 0x7F;
                        $isMasked = ($secondByte & 0x80);

                        if ($lenFlag == 126) { // чаще всего срабатывает 126
                            $maskOffset = 4;
                            if ($bufferLength - $offset < $maskOffset) {
                                break;
                            }
                            $parts = unpack('Ca/Cb/nc', substr($buffer, $offset, $maskOffset));
                            $opcode = $parts['a'] & 0x0F;
                            $payloadLength = $parts['c'];
                        } elseif ($lenFlag == 127) {
                            $maskOffset = 10;
                            if ($bufferLength - $offset < $maskOffset) {
                                break;
                            }
                            $parts = unpack('Ca/Cb/Jc', substr($buffer, $offset, $maskOffset));
                            $opcode = $parts['a'] & 0x0F;
                            $payloadLength = $parts['c'];
                        } else {
                            $maskOffset = 2;
                            $parts = unpack('Ca/Cb', substr($buffer, $offset, $maskOffset));
                            $opcode = $parts['a'] & 0x0F;
                            $payloadLength = $parts['b'] & 0x7F;
                        }

                        $frameLength = $maskOffset + $payloadLength;
                        if ($isMasked) {
                            $frameLength += 4;
                        }

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
                        if ($opcode == 0x1) { // FRAME PAYLOAD text
                            try {
                                $this->_onReceive($tsSelect, $payload, $opcode);
                            } catch (Exception $ue) {
                                // тут вылетаем, но надо сделать disconnect
                                $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_USER, $ue->getMessage());
                                return;
                            } catch (Throwable $te) {
                                // более жесткая ошибка
                                $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_USER, $te->getMessage());
                                return;
                            }
                        } elseif ($opcode == 0x2) { // FRAME PAYLOAD binary
                            try {
                                $this->_onReceive($tsSelect, $payload, $opcode);
                            } catch (Exception $ue) {
                                // тут вылетаем, но надо сделать disconnect
                                $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_USER, $ue->getMessage());
                                return;
                            } catch (Throwable $te) {
                                // более жесткая ошибка
                                $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_USER, $te->getMessage());
                                return;
                            }
                        } elseif ($opcode == 0xA) { // FRAME PONG
                            # debug:start
                            Cli::Print_n(__CLASS__ . ": received frame-pong $payload");
                            # debug:end

                            // считаем что соединение активно и с ним все ок
                            $this->_active = true;
                        } elseif ($opcode == 0x9) { // FRAME PING
                            # debug:start
                            Cli::Print_n(__CLASS__ . ": received frame-ping $payload");
                            # debug:end

                            fwrite($stream, $this->_encodeWebSocketMessage($payload, 0xA));

                            # debug:start
                            Cli::Print_n(__CLASS__ . ": sent frame-pong $payload");
                            # debug:end
                        } elseif ($opcode == 0x8) { // FRAME CLOSED
                            $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_FRAME_CLOSED, false);
                            return;
                        } else {
                            throw new StreamLoop_Exception("Unknown opcode $opcode in ".$this->_host);
                        }

                        // Сдвигаем указатель на следующий фрейм
                        $offset += $frameLength;
                    }

                    // Удаляем обработанные данные из буфера
                    $buffer = substr($buffer, $offset);

                    if ($length < $readFrameLength) {
                        // Если fread вернул меньше, чем запрошено — дальше не дренируем
                        break;
                    }
                } elseif ($data === '') {
                    // на втором месте по частоте срабатывания - пустая строка, я упрусь в drain limit
                    // Если fread вернул пустую строку, проверяем, достигнут ли EOF
                    if ($this->_checkEOF($tsSelect)) {
                        // EOF: connection closed by remote host
                        return; // на выход, чтобы дальше ничего не проверять, ошибка уже выкинута
                    }
                    break;
                } elseif ($data === false) {
                    // и в редких случаях ошибка drain
                    // в неблокирующем режиме если данных нет - то будет string ''
                    // а если false - то это ошибка чтения
                    // например, PHP Warning: fread(): SSL: Connection reset by peer
                    //$errorString = error_get_last()['message'];
                    $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_RESET_BY_PEER, false);
                    return;
                }
            }

            // сохраняем буфер или что от него осталось
            $this->_buffer = $buffer;
        } elseif ($state == StreamLoop_WebSocket_Const::STATE_HANDSHAKING) {
            $this->_checkHandshake($tsSelect);
        } elseif ($state == StreamLoop_WebSocket_Const::STATE_UPGRADING) {
            $this->_checkUpgrade($tsSelect);
        }
    }

    public function readyWrite($tsSelect) {
        switch ($this->_state) {
            case StreamLoop_WebSocket_Const::STATE_CONNECTING:
                // коннект установился, я готов к записи
                stream_context_set_option($this->stream, array(
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'peer_name' => $this->_host, // так надо делать если я перебираю IPшники хоста
                        'allow_self_signed' => true,
                    ],
                ));

                $this->_updateState(StreamLoop_WebSocket_Const::STATE_HANDSHAKING, true, true, false);
                $this->_checkHandshake($tsSelect);
                return;
            case StreamLoop_WebSocket_Const::STATE_HANDSHAKING:
                $this->_checkHandshake($tsSelect);
                return;
            case StreamLoop_WebSocket_Const::STATE_UPGRADING:
                $this->_checkUpgrade($tsSelect);
                return;
        }
    }

    public function readyExcept($tsSelect) {
        switch ($this->_state) {
            case StreamLoop_WebSocket_Const::STATE_CONNECTING:
                $this->_checkEOF($tsSelect); // не надо проверять на if return true then exit
                return;
            case StreamLoop_WebSocket_Const::STATE_HANDSHAKING:
                $this->_checkHandshake($tsSelect);
                return;
            case StreamLoop_WebSocket_Const::STATE_UPGRADING:
                if ($this->_checkEOF($tsSelect)) {
                    return; // на выход
                }
                $this->_checkUpgrade($tsSelect);
                return;
        }
    }

    public function readySelectTimeout($tsSelect) {
        /*
         * idle ping logic:
         * - изначально после подключения ставится первый интервал в 10-15 sec (rand) updateHandlerTimeout(),
         *   а флаг active = 1 (bool)
         * - при каждом pong ставится active =1 и продлевается updateHandlerTimeout() на 10-15 sec rand
         * - когда запускается readySelectTimeout():
         *   если флаг active = 1, то я ставлю active = 0 и вбрасываю ping и продлеваю updateHandlerTimeout на 10-15 sec rand.
         *   если флаг active = 0 - я выхожу по ошибке что мол нет iframe pong'a
         *
         * Таким образом интервалы длинные, нет бесконечных проверок в конце каждого read.
         *
         * плюс я отказываюсь от переменных ping intrval чтобы их не запрашивать все время.
         */

        if ($this->_state == StreamLoop_WebSocket_Const::STATE_READY) {
            // в состоянии READY может прилететь timeout только ради ping
            if ($this->_active) {
                $this->_active = false;
                fwrite($this->stream, $this->_encodeWebSocketMessage('', 9));

                # debug:start
                Cli::Print_n(__CLASS__.": sent frame-ping");
                # debug:end
            } else {
                $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_NO_PONG, false);
                return;
            }

            $this->_loop->updateHandlerTimeoutTo($this, $tsSelect + rand(10, 15));
        } else {
            // во всех остальных случаях я нарвался на проблему что за timeout я не смог установить соединение и сделать handshake/upgrade
            // (то есть не успел аж до ready)
            $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_TIMEOUT, false);
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
                // @todo not not
                if (!str_contains($this->_buffer, '101 Switching Protocols')) {
                    $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_HANDSHAKE, false);
                    return;
                }

                // вот тут опционально ебашим writeArray если он передан
                if ($this->_writeArray) {
                    foreach ($this->_writeArray as $msg) {
                        $this->write($msg);
                    }
                }

                $this->_updateState(StreamLoop_WebSocket_Const::STATE_READY, true, false, false);
                $this->_buffer = '';

                // считаем соединение активно и с ни все ок
                $this->_active = true;
                // таймер двигаем вперед на 10-15 сек
                $this->_loop->updateHandlerTimeoutTo($this, $tsSelect + rand(10, 15));

                $this->_onReady($tsSelect);
            }
        }
    }

    private function _checkEOF($tsSelect) {
        if (feof($this->stream)) {
            $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_EOF, false);
            return true;
        }
    }

    /**
     * Disconnect + onError
     *
     * @param $tsSelect
     * @param $message
     * @param $errorMessage
     * @return void
     */
    public function throwError($tsSelect, $errorCode, $errorMessage = false) {
        $this->disconnect();
        $this->_onError($tsSelect, $errorCode, $errorMessage);
    }

    private function _checkHandshake($tsSelect) {
        if ($this->_checkEOF($tsSelect)) {
            return; // на выход потому что ошибка уже выкинута
        }

        $return = stream_socket_enable_crypto(
            $this->stream,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        // тут нужны ===, потому что если вернется int 0 - то надо пробовать еще раз
        if ($return === true) {
            // handshake случился - делаем websocket upgrade
            $key = base64_encode(random_bytes(16)); // Уникальный ключ для Handshake

            $customHeaderString = '';
            foreach ($this->_headerArray as $key => $value) {
                $customHeaderString .= $key . ': ' . $value . "\r\n";
            }

            $headers = "GET {$this->_path} HTTP/1.1\r\n"
                . "Host: {$this->_host}\r\n"
                . "Upgrade: websocket\r\n"
                . "Connection: Upgrade\r\n"
                . "Sec-WebSocket-Key: $key\r\n"
                . "Sec-WebSocket-Version: 13\r\n"
                . $customHeaderString
                . "\r\n";
            fwrite($this->stream, $headers);
            $this->_updateState(StreamLoop_WebSocket_Const::STATE_UPGRADING, true, false, false);
            $this->_checkUpgrade($tsSelect);

        } elseif ($return === false) {
            $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_SSL, false);
            return;
        }
    }

    private function _updateState($state, $flagRead, $flagWrite, $flagExcept) {
        // @todo maybe inline
        $this->_state = $state;
        $this->_loop->updateHandlerFlags($this, $flagRead, $flagWrite, $flagExcept);
    }

    public function write($data) {
        fwrite($this->stream, $this->_encodeWebSocketMessage($data));
    }

    public function writeAndFlush($data) {
        fwrite($this->stream, $this->_encodeWebSocketMessage($data));
        fflush($this->stream);
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

    public function setReadFrame(int $length, int $drain) {
        if ($length <= 0) {
            throw new StreamLoop_Exception("Length must be a positive integer");
        }
        if ($drain <= 0) {
            throw new StreamLoop_Exception("Drain must be a positive integer");
        }
        $this->_readFrameLength = $length;
        $this->_readFrameDrain = $drain;
    }

    // @todo maybe state machine (если opcache/jit ее инлайнит)
    public function getState() {
        return $this->_state;
    }

    public function isState($state) {
        return $this->_state == $state;
    }

    protected $_host, $_port, $_path, $_ip, $_bindIP, $_bindPort;
    private $_writeArray = [];
    private $_headerArray = [];
    private $_buffer = ''; // string
    private $_state = 0; // 0 is a stop, by default
    private $_active = false; // bool, см логику idle ping
    private $_readFrameLength = 4096; // 4Kb by default
    private $_readFrameDrain = 1;

}