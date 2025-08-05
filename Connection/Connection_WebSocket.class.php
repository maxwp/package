<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */
class Connection_WebSocket implements Connection_IConnection {

    public function __construct($host, $port, $path, $ip = false, $headerArray = []) {
        $this->_host = $host;
        $this->_ip = $ip;
        $this->_port = $port;
        $this->_path = $path;
        $this->_headerArray = $headerArray; // @todo возможно общий TCP connection
    }

    public function setLoopTimeout($us) {
        $this->_streamSelectTimeoutUS = $us;
    }

    public function loop(callable $callback) {
        // вытягивание в locals
        $stream = $this->_stream;
        $streamSelectTimeoutUS = $this->_streamSelectTimeoutUS;
        $pingInterval = $this->_pingInterval;
        $pongDeadline = $this->_pongDeadline;
        $buffer = '';

        $tsPing = time() + $pingInterval;
        $tsPong = $tsPing + $pongDeadline;

        stream_set_blocking($stream, false);

        while (true) {
            $read = [$stream];
            $write = null;
            $except = [$stream];

            $num_changed_streams = stream_select($read, $write, $except, 0, $streamSelectTimeoutUS);

            // меряем время select'a
            $tsSelect = microtime(true);

            $called = false;

            $readFrameLength = $this->_readFrameLength;
            $readFrameDrain = $this->_readFrameDrain;

            if ($num_changed_streams > 0) {
                // dynamic drain: если еще что-то осталось - увеличиваем буфер и читаем еще раз
                // это сильно экономит вызовы stream_select
                for ($drainIndex = 1; $drainIndex <= $readFrameDrain; $drainIndex++) {
                    $data = fread($stream, $readFrameLength);

                    $buffer .= $data; // дописывание в буфер

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

                        // супер важный момент: время надо получать после того, как я прочитал данные и разобрал их.
                        // потому что может быть момент, что я запросил время сразу после stream_select(), а затем
                        // fread() считал больше данных чем я ожидал - и тогда будет казаться что данные пришли из будущего.

                        // Обработка опкодов
                        switch ($opcode) {
                            case 0x8: // FRAME CLOSED
                                $this->disconnect();
                                throw new Connection_Exception("Connection_WebSocket: received frame-closed");
                            case 0x9: // FRAME PING
                                # debug:start
                                Cli::Print_n("Connection_WebSocket: received frame-ping $payload");
                                # debug:end

                                // тут очень важный нюанс:
                                // stream_select может выйти по таймауту, а может по ping.
                                // в случае pong таймаут будет продлен, поэтому нужно все равно вызывать callback,
                                // так как он ждет четкий loop по тайм-ауту 0.5..1.0 sec.
                                try {
                                    $callback($tsSelect, microtime(true), false);
                                    $called = true;
                                } catch (Exception $userException) {
                                    $this->disconnect();
                                    throw $userException;
                                }

                                $encodedPong = $this->_encodeWebSocketMessage($payload, 0xA); // @todo inline it
                                fwrite($stream, $encodedPong);

                                # debug:start
                                Cli::Print_n("Connection_WebSocket: send frame-pong $payload");
                                # debug:end

                                break;
                            case 0xA: // FRAME PONG
                                # debug:start
                                Cli::Print_n("Connection_WebSocket: received frame-pong $payload");
                                # debug:end

                                // тут очень важный нюанс:
                                // stream_select может выйти по таймауту, а может по pong.
                                // в случае pong таймаут будет продлен, поэтому нужно все равно вызывать callback,
                                // так как он ждет четкий loop по тайм-ауту 0.5..1.0 sec.
                                try {
                                    $callback($tsSelect, microtime(true), false);
                                    $called = true;
                                } catch (Exception $userException) {
                                    $this->disconnect();
                                    throw $userException;
                                }

                                // подвигаем pong
                                $tsPong = $tsPing + $pongDeadline;
                                break;
                            default: // FRAME with payload
                                try {
                                    $callback($tsSelect, microtime(true), $payload);
                                    $called = true;
                                } catch (Exception $userException) {
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

                    if ($data === false) {
                        // в неблокирующем режиме если данных нет - то будет string ''
                        // а если false - то это ошибка чтения
                        // например, PHP Warning: fread(): SSL: Connection reset by peer
                        $errorString = error_get_last()['message'];
                        throw new Connection_Exception("$errorString - failed to read from {$this->_host}:{$this->_port}");
                    } elseif ($data === '') {
                        // Если fread вернул пустую строку, проверяем, достигнут ли EOF
                        if (feof($stream)) {
                            $this->disconnect();
                            throw new Exception('EOF: connection closed by remote host');
                        } else {
                            // stop drain and do not parse
                            break;
                        }
                    } elseif (strlen($data) < $readFrameLength) {
                        // Если fread вернул меньше, чем запрошено — дальше не дренируем
                        break;
                    }
                    // Иначе loop идет дальше, возможно есть новые данные
                }
            } elseif ($num_changed_streams === false) {
                // согласно документации false может прилететь из-за system interrupt call
                $this->disconnect();
                throw new Connection_Exception("Connection_WebSocket: stream_select error");
            }

            if (!$called) {
                try {
                    $callback($tsSelect, microtime(true), false);
                } catch (Exception $userException) {
                    $this->disconnect();
                    throw $userException;
                }
            }

            if ($except) {
                $this->disconnect();
                throw new Connection_Exception("Connection_WebSocket: stream_select except");
            }

            // пинг-понг внизу после select'a
            // auto ping frame
            if ($tsSelect > $tsPing) {
                $encodedPing = $this->_encodeWebSocketMessage('', 9); // @todo inline it inside compiler
                fwrite($stream, $encodedPing);

                # debug:start
                Cli::Print_n("Connection_WebSocket: sent frame-ping");
                # debug:end

                // когда следующий ping?
                $tsPing = $tsSelect + $pingInterval;
            }

            if ($tsSelect > $tsPong) {
                // если задан дедлайн pong,
                // и время уже больше этого дедлайна, то это означает что pong не пришет
                // и мы идем на выход
                $this->disconnect();
                throw new Connection_Exception("Connection_WebSocket: no frame-pong - exit");
            }
        }

        // теоретически я сюда никогда не дойду, ну да ладно
        $this->disconnect();
    }

    public function connect() {
        $context = stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,  // no Nagle algorithm
            ],
            'ssl' => array(
                'peer_name' => $this->_host,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ),
        ]);

        $connectHost = $this->_ip ?: $this->_host;

        $this->_stream = stream_socket_client("tcp://{$connectHost}:{$this->_port}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        if (!$this->_stream) {
            throw new Connection_Exception("Failed to connect to {$this->_host}:{$this->_port} - $errstr ($errno)");
        }

        // Устанавливаем буфер до начала SSL
        $socket = new Connection_SocketStream($this->_stream);
        $socket->setBufferSizeRead(10 * 1024 * 1024);
        $socket->setBufferSizeWrite(2 * 1024 * 1024);

        // SSL поверх TCP
        if (!stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Connection_Exception("Failed to setup SSL");
        }

        stream_set_read_buffer($this->_stream, 0);
        stream_set_write_buffer($this->_stream, 0);

        $customHeaderString = '';
        foreach ($this->_headerArray as $key => $value) {
            $customHeaderString .= $key . ': ' . $value . "\r\n";
        }

        $key = base64_encode(random_bytes(16)); // Уникальный ключ для Handshake
        $headers = "GET {$this->_path} HTTP/1.1\r\n"
            . "Host: {$this->_host}\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Key: $key\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . $customHeaderString
            . "\r\n";
        fwrite($this->_stream, $headers);

        $response = fread($this->_stream, 1500);
        if (!str_contains($response, '101 Switching Protocols')) {
            throw new Connection_Exception("Handshake error: ".$response);
        }
    }

    public function write($data) {
        $data = $this->_encodeWebSocketMessage($data);
        fwrite($this->_stream, $data);
    }

    public function disconnect() {
        fclose($this->_stream);
    }

    /**
     * @return Connection_WebSocket
     * @throws Connection_Exception
     */
    public function getLink() {
        if (!$this->_stream) {
            $this->connect();
        }

        return $this->_stream;
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

    private $_host, $_ip;
    private $_port;
    private $_path;
    private $_headerArray = [];
    private $_stream;
    private $_streamSelectTimeoutUS = 500000; // 500 ms by default @todo
    private $_pingInterval = 5;
    private $_pongDeadline = 3;
    private $_readFrameLength = 4096;
    private $_readFrameDrain = 1;

}