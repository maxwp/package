<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */
class Connection_WebSocket implements Connection_IConnection {

    public function __construct($host, $port, $path, $ip = false) {
        $this->_host = $host;
        $this->_ip = $ip;
        $this->_port = $port;
        $this->_path = $path;
    }

    public function setLoopTimeout($us) {
        $this->_streamSelectTimeoutUS = $us;
    }

    public function loop(callable $callback) {
        $tsPing = 0;
        $tsPong = 0;

        // вытягивание в locals
        $stream = $this->_stream;
        $streamSelectTimeoutUS = $this->_streamSelectTimeoutUS;
        $pingInterval = $this->_pingInterval;
        $pongDeadline = $this->_pongDeadline;
        $readFrameLength = $this->_readFrameLength;
        $buffer = '';

        stream_set_blocking($stream, false);

        while (true) {
            $read = [$stream];
            $write = null;
            $except = [$stream];

            $num_changed_streams = stream_select($read, $write, $except, 0, $streamSelectTimeoutUS);

            // меряем время select'a
            $tsSelect = microtime(true);

            $called = false;
            if ($num_changed_streams > 0) {
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
                        if (feof($stream)) {
                            $this->disconnect();
                            throw new Exception('EOF: connection closed by remote host');
                        } else {
                            // stop drain and do not parse
                            break;
                        }
                    }

                    $buffer .= $data; // дописывание в буфер

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

                                // запоминаем когда пришел pong
                                $tsPong = 0;
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

                    if (strlen($data) < $readFrameLength * $drainIndex) {
                        // stop drain
                        break;
                    }
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
            $time = microtime(true);
            if ($time - $tsPing >= $pingInterval) {
                $encodedPing = $this->_encodeWebSocketMessage('', 9); // @todo inline it inside compiler
                fwrite($stream, $encodedPing);

                # debug:start
                Cli::Print_n("Connection_WebSocket: sent frame-ping");
                # debug:end

                $tsPing = $time;
                // дедлайн до которого должен прийти pong
                $tsPong = $time + $pongDeadline;
            }

            if ($tsPong > 0 && $time > $tsPong) {
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
        $socket = Connection_Socket::CreateFromStream($this->_stream);
        $socket->setBufferSizeRead(10 * 1024 * 1024);
        $socket->setBufferSizeWrite(2 * 1024 * 1024);

        // SSL поверх TCP
        if (!stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Connection_Exception("Failed to setup SSL");
        }

        stream_set_read_buffer($this->_stream, 0);
        stream_set_write_buffer($this->_stream, 0);

        $key = base64_encode(random_bytes(16)); // Уникальный ключ для Handshake
        $headers = "GET {$this->_path} HTTP/1.1\r\n"
            . "Host: {$this->_host}\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Key: $key\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
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

    public function setReadFrameLength($length) {
        $this->_readFrameLength = $length;
    }

    private $_host, $_ip;
    private $_port;
    private $_path;
    private $_stream;
    private $_streamSelectTimeoutUS = 500000; // 500 ms by default
    private $_pingInterval = 1;
    private $_pongDeadline = 3;
    private $_readFrameLength = 512;

}