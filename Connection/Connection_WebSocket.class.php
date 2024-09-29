<?php
class Connection_WebSocket {

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
            if ($time - $this->_tsPing >= 5) {
                $this->_sendPingFrame($this->_stream);
                //print "ping\n";
                $this->_tsPing = $time;
            }

            $read = [$this->_stream];
            $write = null;
            $except = null;

            $num_changed_streams = stream_select($read, $write, $except, 0, $this->_streamSelectTimeout);
            $msg = false;
            if ($num_changed_streams > 0) {
                $msg = $this->read();
            }

            $result = $callback($msg);

            // если что-то вернули - на выход
            if ($result) {
                return $result;
            }
        }
    }

    public function connect() {
        $context = stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,  // no Nagle algorithm
            ],
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            ),
        ]);

        $this->_stream = stream_socket_client("ssl://{$this->_host}:{$this->_port}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        if (!$this->_stream) {
            throw new Connection_Exception("Failed to connect to {$this->_host}:{$this->_port} - $errstr ($errno)");
        }

        $key = base64_encode(random_bytes(16)); // Уникальный ключ для Handshake
        $headers = "GET {$this->_path} HTTP/1.1\r\n"
            . "Host: {$this->_host}\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Key: $key\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . "\r\n";
        $this->write($headers, false);

        $response = $this->read(1500, false);
        if (strpos($response, '101 Switching Protocols') === false) {
            throw new Connection_Exception("Handshake error: ".$response);
        }
    }

    public function read($maxLength = 2000, $decode = true) {
        $data = fread($this->_stream, $maxLength);

        if ($decode && $data != 'pong') {
            $data = $this->_decodeWebSocketMessage($data);
        }

        return $data;
    }

    public function write($data, $encode = true) {
        if ($encode) {
            $data = $this->_encodeWebSocketMessage($data);
        }

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

    /**
     * Функция для декодирования сообщений WebSocket с проверкой типа фрейма
     *
     * @param $data
     * @return string
     */
    private function _decodeWebSocketMessage($data) {
        $firstByte = ord($data[0]);
        $secondByte = ord($data[1]);

        $opcode = $firstByte & 0x0F; // Определяем тип фрейма
        $isMasked = ($secondByte & 0b10000000) !== 0; // Проверяем, замаскировано ли сообщение
        $payloadLength = $secondByte & 0b01111111; // Длина полезной нагрузки

        if ($opcode === 0x8) { // Если это фрейм закрытия соединения
            return 'closed';
        } elseif ($opcode === 0xA) { // Если это фрейм pong
            return 'pong';
        }

        if ($payloadLength === 126) {
            $maskOffset = 4;
            $payloadLength = unpack('n', substr($data, 2, 2))[1];
        } elseif ($payloadLength === 127) {
            $maskOffset = 10;
            $payloadLength = unpack('J', substr($data, 2, 8))[1]; // 64-битная длина
        } else {
            $maskOffset = 2;
        }

        // Читаем маску
        $mask = '';
        if ($isMasked) {
            $mask = substr($data, $maskOffset, 4);
        }

        // Читаем полезную нагрузку
        $payload = substr($data, $maskOffset + ($isMasked ? 4 : 0), $payloadLength);

        // Расшифровываем замаскированное сообщение
        if ($isMasked) {
            $unmaskedPayload = '';
            for ($i = 0; $i < strlen($payload); $i++) {
                $unmaskedPayload .= $payload[$i] ^ $mask[$i % 4];
            }
            return $unmaskedPayload;
        }

        return $payload;
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

}