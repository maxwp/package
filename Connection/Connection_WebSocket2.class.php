<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class Connection_WebSocket2 implements Connection_IConnection {

    public function __construct($host, $port, $path) {
        $this->_host = $host;
        $this->_port = $port;
        $this->_path = $path;
    }

    public function setLoopTimeout($microseconds) {
        $this->_streamSelectTimeout = $microseconds;
    }

    public function loop($callback) {
        while (true) {
            $time = time();

            // auto ping frame
            if ($time - $this->_tsPing >= $this->_pingInterval) {
                $this->_sendPingFrame($this->_stream);
                $this->_tsPing = $time;
                // дедлайн до которого должен прийти pong
                $this->_tsPong = $time + $this->_pongDeadline;
            }

            if ($this->_tsPong > 0 && $time > $this->_tsPong) {
                // если задан дедлайн pong,
                // и время уже больше этого дедлайна, то это означает что pong не пришет
                // и мы идем на выход
                print "no pong - exit\n";
                return true;
            }

            $read = [$this->_stream];
            $write = null;
            $except = null;

            $num_changed_streams = stream_select($read, $write, $except, 0, $this->_streamSelectTimeout);
            $msgArray = false;
            if ($num_changed_streams > 0) {
                $msgArray = $this->read();
            }

            if ($msgArray) {
                foreach ($msgArray as $msg) {
                    if ($msg == 'pong') {
                        // запоминаем когда пришел pong
                        $this->_tsPong = 0;
                        continue;
                    }

                    if ($msg == 'closed') {
                        return true;
                    }

                    $result = $callback($msg);

                    // если что-то вернули - на выход
                    if ($result) {
                        return $result;
                    }
                }
            } else {
                $result = $callback(false);
                // если что-то вернули - на выход
                if ($result) {
                    return $result;
                }
            }
        }
    }

    public function connect() {
        $context = stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,  // no Nagle algorithm
            ],
            /*'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
            ),*/
        ]);

        $t = microtime(true);
        $this->_stream = stream_socket_client("tcp://{$this->_host}:{$this->_port}", $errno, $errstr, 30, STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT, $context);
        if (!$this->_stream) {
            throw new Connection_Exception("Failed to connect to {$this->_host}:{$this->_port} - $errstr ($errno)");
        }

        stream_set_blocking($this->_stream, false);

        $t = microtime(true) - $t;
        $t *= 1000;
        var_dump('connect='.$t);

        //sleep(1);

        while (1) {
            $read = [$this->_stream];
            $write = null;
            $except = null;

            $t = microtime(true);
            stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $t = microtime(true) - $t;
            $t *= 1000;
            var_dump('ssl='.$t);

            if (stream_select($read, $write, $except, 0, 100)) {
                $t = microtime(true);
                $result = stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $t = microtime(true) - $t;
                $t *= 1000;
                var_dump('ssl2='.$t);

                if ($result) {
                    break;
                }
            }
        }

        /*$t = microtime(true);
        if (stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
            print "ok";
        }
        $t = microtime(true) - $t;
        $t *= 1000;
        var_dump('ssl='.$t);*/

        $sent = false;

        while (1) {
            $read = [$this->_stream];
            $write = [$this->_stream];
            $except = [$this->_stream];
            $num_changed_streams = stream_select($read, $write, $except, 0, $this->_streamSelectTimeout);

            if ($write && !$sent) {
                var_dump(1);
                $key = base64_encode(random_bytes(16)); // Уникальный ключ для Handshake
                $headers = "GET {$this->_path} HTTP/1.1\r\n"
                    . "Host: {$this->_host}\r\n"
                    . "Upgrade: websocket\r\n"
                    . "Connection: Upgrade\r\n"
                    . "Sec-WebSocket-Key: $key\r\n"
                    . "Sec-WebSocket-Version: 13\r\n"
                    . "\r\n";
                $t = microtime(true);
                fwrite($this->_stream, $headers);
                $t = microtime(true) - $t;
                $t *= 1000;
                var_dump('write='.$t);
                $sent = true;
            }

            if ($read) {
                $t = microtime(true);
                $response = fread($this->_stream, 1500);
                $t = microtime(true) - $t;
                $t *= 1000;
                var_dump('read='.$t);


                if (strpos($response, '101 Switching Protocols') === false) {
                    throw new Connection_Exception("Handshake error: ".$response);
                }

                break;
            }
        }
    }

    public function read($maxLength = 2000) {
        $data = fread($this->_stream, $maxLength);
        if ($data === false) {
            return false;
        }

        $this->_buffer .= $data;
        return $this->_decodeMessageArray();
    }

    private function _decodeMessageArray() {
        $messages = [];
        $offset = 0;
        $bufferLength = strlen($this->_buffer);

        while ($offset < $bufferLength) {
            // Минимальный заголовок WebSocket — 2 байта
            if ($bufferLength - $offset < 2) {
                break;  // Недостаточно данных для заголовка
            }

            // Первый байт заголовка
            $firstByte = ord($this->_buffer[$offset]);
            $secondByte = ord($this->_buffer[$offset + 1]);

            $opcode = $firstByte & 0x0F;  // Определяем тип фрейма
            $isMasked = ($secondByte & 0b10000000) !== 0;  // Проверяем, замаскировано ли сообщение
            $payloadLength = $secondByte & 0b01111111;  // Длина полезной нагрузки
            $maskOffset = 2;

            // Обработка фреймов закрытия и pong
            if ($opcode === 0x8) { // Фрейм закрытия соединения
                $messages[] = 'closed';
                $offset += 2;  // Перемещаем указатель вперед, так как у фрейма закрытия может быть полезная нагрузка
                continue;
            } elseif ($opcode === 0xA) { // Фрейм pong
                $messages[] = 'pong';
                $offset += 2;  // Перемещаем указатель вперед, потому что у pong обычно нет полезной нагрузки
                continue;
            }

            // Обработка разных значений длины полезной нагрузки
            if ($payloadLength === 126) {
                // Если длина указана как 126, то следующие 2 байта содержат фактическую длину
                if ($bufferLength - $offset < 4) {
                    break;  // Недостаточно данных для заголовка и длины
                }
                $payloadLength = unpack('n', substr($this->_buffer, $offset + 2, 2))[1];  // Читаем 16-битную длину
                $maskOffset = 4;
            } elseif ($payloadLength === 127) {
                // Если длина указана как 127, то следующие 8 байт содержат фактическую длину
                if ($bufferLength - $offset < 10) {
                    break;  // Недостаточно данных для заголовка и длины
                }
                $payloadLength = unpack('J', substr($this->_buffer, $offset + 2, 8))[1];  // Читаем 64-битную длину
                $maskOffset = 10;
            }

            // Проверка длины полезной нагрузки меньше 126 байт (обычный случай)
            // Здесь payloadLength уже содержит длину полезной нагрузки (до 125 байт)

            // Полная длина фрейма (заголовок + маска + полезная нагрузка)
            $frameLength = $maskOffset + ($isMasked ? 4 : 0) + $payloadLength;

            // Проверяем, хватает ли данных для полного фрейма
            if ($bufferLength - $offset < $frameLength) {
                break;  // Данных недостаточно, ждем больше
            }

            // Читаем маску (если сообщение замаскировано)
            $mask = '';
            if ($isMasked) {
                $mask = substr($this->_buffer, $offset + $maskOffset, 4);
            }

            // Читаем полезную нагрузку
            $payload = substr($this->_buffer, $offset + $maskOffset + ($isMasked ? 4 : 0), $payloadLength);

            // Расшифровываем замаскированное сообщение (если маскировано)
            if ($isMasked) {
                $unmaskedPayload = '';
                for ($i = 0; $i < $payloadLength; $i++) {
                    $unmaskedPayload .= $payload[$i] ^ $mask[$i % 4];
                }
                $messages[] = $unmaskedPayload;
            } else {
                $messages[] = $payload;
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
        fwrite($this->_stream, $data);
    }

    public function disconnect() {
        fclose($this->_stream);
    }

    /**
     * @return WebSocket
     * @throws WebSocket_Exception
     */
    public function getLink() {
        if (!$this->_stream) {
            $this->connect();
        }

        return $this->_stream;
    }

    private function _sendPingFrame($socket) {
        $pingMessage = ''; // Пустое тело для ping
        $encodedPing = $this->_encodeWebSocketMessage($pingMessage, 9); // 9 — это opcode для ping
        fwrite($socket, $encodedPing);
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

    private $_host;
    private $_port;
    private $_path;
    private $_stream;
    private $_streamSelectTimeout = 500000; // 500 ms
    private $_tsPing = 0;
    private $_tsPong = 0;
    private $_pingInterval = 1;
    private $_pongDeadline = 3;

    private $_buffer = '';

}