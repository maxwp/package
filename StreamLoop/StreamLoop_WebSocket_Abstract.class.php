<?php
/**
 * Важное отличие StreamLoop_WebSocket от Connection_WebSocket:
 * SL_WS вызывает selectTimeout только ради websocket-layer frame-ping-pong, он не вызывает его
 * ради пустых callback.
 * Если нужны пустые callback - то надо добавлять timer внутрь StreamLoop.
 * Такой подход делает меньше вызовов selectTimeout и позволяет держать сильно больше WebSocket-handler-ов внутри одного StreamLoop,
 * но timer не будет синхронизирован с last event от websocket. Хотя он и в C_WS может быть не синхронизирован из-за app-layer & iframe-layer ping-pong.
 */
abstract class StreamLoop_WebSocket_Abstract extends StreamLoop_TCP_Abstract {

    // @todo как слепить в кучу websocket over https?
    // @todo сначала надо придумать как сделать StateMachine, чтобы я мог помещать команду с событиями onXXX,
    //       и затем handshake и switching protocol снанут этими командами

    // @todo возможно в момент ready тоже пихать except=true?

    abstract protected function _setupConnection();
    abstract protected function _onInit(); // @todo rename to _beforeConnect, и вообще надо явно Events before/after
    abstract protected function _onReceive($tsSelect, $payload, $opcode);
    abstract protected function _onError($tsSelect, $errorCode, $errorMessage);
    abstract protected function _onReady($tsSelect);

    /**
     * @throws StreamLoop_Exception
     * @todo нахер этот лишний метод? и зачем он public для abstract-класса?
     */
    public function updateConnection($host, $port, $path, $writeArray, $ip = false, $headerArray = [], $bindIP = false, $bindPort = false) {
        $this->_updateDestinationHost($host);
        $this->_updateDestinationPort($port);
        $this->_updateDestinationIP($ip);
        $this->_updateSourceIP($bindIP);
        $this->_updateSourcePort($bindPort);

        $this->_path = $path;
        $this->_writeArray = $writeArray;
        $this->_headerArray = $headerArray;
    }

    public function connect() {
        // перед connect я вызываю setupConnection чтобы он поправил все что надо
        $this->_setupConnection();

        $this->_buffer = '';
        $this->_bufferLength = 0;
        $this->_bufferOffset = 0;

        $this->_state = StreamLoop_WebSocket_Const::STATE_CONNECTING;

        $this->_createAndConnectTCP();

        $this->_onInit();
    }

    public function disconnect() {
        // дисконнект закрывает снимает регистрацию handler'a и закрывает stream.
        // это приводит к тому, что SL временно забывает про handler и не ебет его
        $this->_timeoutTo = 0;
        $this->_loop->unregisterHandler($this);

        // бывают ситуации когда throwError два раза подряд и тогда disconnect два раза подряд
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->streamID = 0;
        $this->stream = null;

        $this->_buffer = '';
        $this->_bufferLength = 0;
        $this->_bufferOffset = 0;

        $this->_state = StreamLoop_WebSocket_Const::STATE_DISCONNECTED;
    }

    public function readyRead($tsSelect) {
        // if-tree optimization
        if ($this->_state == StreamLoop_WebSocket_Const::STATE_READY) {
            $readFrameLength = $this->_readFrameLength;
            $readFrameDrain = $this->_readFrameDrain;
            $stream = $this->stream;

            // берем buffer + cursor
            $buffer = $this->_buffer;
            $bufLen = $this->_bufferLength;
            $offset = $this->_bufferOffset;

            // один общий try-catch экономит до 11% cpu time если вызовов onReceive несколько
            try {
                // dynamic drain: если после вычитки большого пакета fread() он считался ровно впритык - то вызываем
                // чтение еще раз и так до drainLimit.
                // Надо стараться делать меньше fread (syscall overhead), но если все-таки данных много - то лучше читать
                // еще раз, чтобы не ждать нового круга stream_select(). Но опять-таки, это сильно зависит от количество
                // потоков внутри всего StreamLoop и насколько я могу затупить на одном handler'e.
                do {
                    $data = fread($stream, $readFrameLength);
                    $length = strlen($data);

                    // чаще всего будет срабатывать length > 0
                    if ($length > 0) {
                        $buffer .= $data;
                        $bufLen += $length;

                        // минимальный заголовок - 2 байта
                        while ($bufLen - $offset >= 2) {
                            $secondByte = ord($buffer[$offset + 1]);
                            $lenFlag = $secondByte & 0x7F;
                            $isMasked = ($secondByte >= 128); // установлен ли 7й бит, это быстрее чем & + bool
                            $opcode = ord($buffer[$offset]) & 0x0F;

                            if ($lenFlag == 126) { // чаще всего срабатывает 126
                                $maskOffset = 4; // 2 + 2 bytes ext len
                                if ($bufLen - $offset < $maskOffset) {
                                    break;
                                }
                                $payloadLength = (ord($buffer[$offset + 2]) << 8) | ord($buffer[$offset + 3]);
                            } elseif ($lenFlag == 127) { // 127 почти никогда не срабатывает
                                $maskOffset = 10; // 2 + 8 bytes ext len
                                if ($bufLen - $offset < $maskOffset) {
                                    break;
                                }
                                // я проверял, тут unpack(J) это правильно и это быстрее чем ord
                                $payloadLength = unpack('J', $buffer, $offset + 2)[1];
                            } else {
                                $maskOffset = 2;
                                $payloadLength = $lenFlag; // 0..125
                            }

                            $maskLen = $isMasked ? 4 : 0; // +4 если masked
                            $payloadOffset = $offset + $maskOffset + $maskLen;
                            $frameLength = $maskOffset + $maskLen + $payloadLength;
                            if ($bufLen - $offset < $frameLength) {
                                break;
                            }

                            $payload = substr(
                                $buffer,
                                $payloadOffset,
                                $payloadLength
                            );

                            // masked frames бывают редко
                            if ($isMasked) {
                                $maskKey = substr($buffer, $offset + $maskOffset, 4);

                                // повторяем маску до длины payload и XOR'им строкой
                                //$mask = str_repeat($maskKey, ($payloadLength + 3) >> 2);
                                //$payload ^= substr($mask, 0, $payloadLength);
                                // XOR возьмет только strlen($payload) байт из правого операнда автоматически
                                $payload ^= str_repeat($maskKey, ($payloadLength >> 2) + 1);
                            }

                            // Обработка опкодов
                            if ($opcode <= 0x2) { // // 0x1 (text) или 0x2 (binary)
                                $this->_onReceive($tsSelect, $payload, $opcode);
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

                                $this->write($payload, 0xA); // pong

                                # debug:start
                                Cli::Print_n(__CLASS__ . ": sent frame-pong $payload");
                                # debug:end
                            } elseif ($opcode == 0x8) { // FRAME CLOSED
                                throw new StreamLoop_Exception(StreamLoop_WebSocket_Const::ERROR_FRAME_CLOSED);
                            } else {
                                throw new StreamLoop_Exception(StreamLoop_WebSocket_Const::ERROR_UNKNOWN_OPCODE);
                            }

                            // Сдвигаем указатель на следующий фрейм
                            $offset += $frameLength;
                        }

                        // если всё съели - сбрасываем буфер полностью (самый дешевый случай)
                        if ($offset == $bufLen) {
                            $buffer = '';
                            $bufLen = 0;
                            $offset = 0;
                        } elseif ($offset > 65536) {
                            // редкое "сжатие" буфера: чаще всего фреймы летят целые, поэтому обрабатывается предыдущее условие
                            $buffer = substr($buffer, $offset);
                            $bufLen -= $offset;
                            $offset = 0;
                        }

                        if ($length < $readFrameLength) {
                            // Если fread вернул меньше, чем запрошено — дальше не дренируем
                            break;
                        }
                    } elseif ($data === '') {
                        // на втором месте по частоте срабатывания - пустая строка, я упрусь в drain limit
                        // Если fread вернул пустую строку, проверяем, достигнут ли EOF
                        // upd: она запускается только если drain вернул пустоту, что бывает очень редко, так как есть проверка на length
                        if ($this->_checkEOF($tsSelect)) { // in drain read
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
                        throw new StreamLoop_Exception(StreamLoop_WebSocket_Const::ERROR_RESET_BY_PEER);
                    }
                } while (--$readFrameDrain);

                $this->_buffer = $buffer;
                $this->_bufferLength = $bufLen;
                $this->_bufferOffset = $offset;

            } catch (StreamLoop_Exception $se) {
                $this->throwError($tsSelect, $se->getMessage());
                return;
            } catch (Exception $ue) {
                // тут вылетаем, но надо сделать disconnect
                $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_USER, $ue->getMessage());
                return;
            } catch (Throwable $te) {
                // более жесткая ошибка
                $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_USER, $te->getMessage());
                return;
            }

        } elseif ($this->_state == StreamLoop_WebSocket_Const::STATE_HANDSHAKING) {
            $this->_checkHandshake($tsSelect);
        } elseif ($this->_state == StreamLoop_WebSocket_Const::STATE_UPGRADING) {
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

                $this->_state = StreamLoop_WebSocket_Const::STATE_HANDSHAKING;

                // NB! НЕ ставим write, потому что во время handshaking всегда идет write и просто зайобка
                $this->_loop->registerHandler($this, true, false, true, $this->_timeoutTo);

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
        if ($this->_checkEOF($tsSelect)) { // in except
            return; // на выход
        }

        switch ($this->_state) {
            case StreamLoop_WebSocket_Const::STATE_HANDSHAKING:
                $this->_checkHandshake($tsSelect);
                return;
            case StreamLoop_WebSocket_Const::STATE_UPGRADING:
                $this->_checkUpgrade($tsSelect);
                return;
        }
    }

    public function readyTimeout($tsSelect) {
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
                $this->write('', 9); // ping

                # debug:start
                Cli::Print_n(__CLASS__.": sent frame-ping");
                # debug:end
            } else {
                $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_NO_PONG, false);
                return;
            }

            // @todo какая-то мутная логика ping-pong
            $this->_timeoutTo = $tsSelect + 10 + rand() % 5;
            $this->_loop->registerHandler($this, true, false, false, $this->_timeoutTo);
        } else {
            // во всех остальных случаях я нарвался на проблему что за timeout я не смог установить соединение и сделать handshake/upgrade
            // (то есть не успел аж до ready)
            $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_TIMEOUT, false);
        }
    }

    private function _checkUpgrade($tsSelect) {
        $line = fgets($this->stream, 4096);
        if ($line === false) {
            $this->_checkEOF($tsSelect); // in upgrade
        } else {
            $this->_buffer .= $line; // @todo locals?
            // пустая строка — конец блока заголовков
            if ($line == "\r\n" || $line == "\n") {
                // @todo not not
                if (!str_contains($this->_buffer, '101 Switching Protocols')) {
                    # debug:start
                    Cli::Print_n(__CLASS__.": invalid upgrade response: ".$this->_buffer);
                    # debug:end

                    $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_HANDSHAKE, false);
                    return;
                }

                // вот тут опционально ебашим writeArray если он передан
                if ($this->_writeArray) {
                    foreach ($this->_writeArray as $msg) {
                        $this->write($msg);
                    }
                }

                $this->_state = StreamLoop_WebSocket_Const::STATE_READY;

                // таймер двигаем вперед на 10-15 сек
                $this->_timeoutTo = $tsSelect + 10 + rand() % 5;
                $this->_loop->registerHandler($this, true, false, false, $this->_timeoutTo);

                $this->_buffer = '';
                $this->_bufferLength = 0;
                $this->_bufferOffset = 0;

                // считаем соединение активно и с ни все ок
                $this->_active = true;

                $this->_onReady($tsSelect);
            }
        }
    }

    private function _checkEOF($tsSelect) {
        if (feof($this->stream)) {
            $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_EOF, false);
            return true;
        }

        return false;
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
        if ($this->_checkEOF($tsSelect)) { // in handshake
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

            $customHeaderString = '';
            if ($this->_headerArray) {
                foreach ($this->_headerArray as $key => $value) {
                    $customHeaderString .= $key . ': ' . $value . "\r\n";
                }
            }

            fwrite(
                $this->stream,
                "GET {$this->_path} HTTP/1.1\r\n"
                . "Host: {$this->_host}\r\n"
                . "Upgrade: websocket\r\n"
                . "Connection: Upgrade\r\n"
                . "Sec-WebSocket-Key: ".base64_encode(random_bytes(16))."\r\n"
                . "Sec-WebSocket-Version: 13\r\n"
                . $customHeaderString
                . "\r\n"
            );

            $this->_state = StreamLoop_WebSocket_Const::STATE_UPGRADING;
            $this->_loop->registerHandler($this, true, false, false, $this->_timeoutTo);

            $this->_checkUpgrade($tsSelect);

        } elseif ($return === false) {
            $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_SSL, false);
            return;
        }
    }

    public function write($data, $opcode = 1) {
        try {
            fwrite(
                $this->stream,
                $this->_encodeMessage($data, $opcode)
            );
        } catch (Throwable $te) {
            $this->throwError(
                microtime(true),
                StreamLoop_WebSocket_Const::ERROR_EOF,
                $te->getMessage()
            );
        }
    }

    public function writeMulti($dataArray, $opcode = 1) {
        $s = '';
        foreach ($dataArray as $data) {
            $s .= $this->_encodeMessage($data, $opcode);
        }

        try {
            fwrite(
                $this->stream,
                $s
            );
        } catch (Throwable $te) {
            $this->throwError(
                microtime(true),
                StreamLoop_WebSocket_Const::ERROR_EOF,
                $te->getMessage()
            );
        }
    }

    private function _encodeMessage($data, $opcode = 1) {
        $length = strlen($data);

        if ($length <= 125) {
            return chr(0x80 | $opcode) . chr(0x80 | $length)."\x00\x00\x00\x00".$data;
        } elseif ($length <= 0xFFFF) {
            return chr(0x80 | $opcode) . $this->_chr126 . chr($length >> 8) . chr($length)."\x00\x00\x00\x00".$data;
        } else {
            // 64-bit length (network order)
            return chr(0x80 | $opcode). $this->_chr127 . pack('J', $length)."\x00\x00\x00\x00".$data;
        }
    }

    public function setReadFrame($length, $drain) {
        $length = (int) $length;
        $drain = (int) $drain;

        if ($length <= 0) {
            throw new StreamLoop_Exception("Length must be a positive integer");
        }
        if ($drain <= 0) {
            throw new StreamLoop_Exception("Drain must be a positive integer");
        }
        $this->_readFrameLength = $length;
        $this->_readFrameDrain = $drain;
    }

    public function getState() {
        return $this->_state;
    }


    public function __construct(StreamLoop $loop) {
        parent::__construct($loop);

        $this->_chr126 = chr(0x80 | 126);
        $this->_chr127 = chr(0x80 | 127);
    }

    private $_writeArray = [];
    private $_headerArray = [];
    private $_path = ''; // string
    private $_buffer = ''; // string
    private $_bufferLength = 0; // int
    private $_bufferOffset = 0; // cursor: сколько байт уже "съели" из _buffer
    private $_state = 0; // 0 is a stop, by default
    private $_active = false; // bool, см логику idle ping
    private $_readFrameLength = 4096; // 4Kb by default
    private $_readFrameDrain = 1;
    private $_chr126, $_chr127;

}