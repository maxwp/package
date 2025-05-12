<?php
class StreamLoop_HandlerHTTPS extends StreamLoop_AHandler {

    public function __construct($host, $port, $ip = false) {
        $this->_host = $host;
        $this->_port = $port;
        $this->_ip = $ip ? $ip : $this->_host;

        $this->_requestQue = new SplQueue();

        // соединение я начинаю устанавливать сразу же
        // @todo в будущем можно переделать на установку соединения по требованию, но пока это просто не актульано
        $this->connect();

        // @todo я могу коннектор закинуть внутрь "request", он будет как команда handshake.
        // просто handshake ловит свои события,
        // и в случае успеха он заверщается на ready read
        // надо попробовать
    }

    public function request(string $method, string $path, string $body, array $headerArray, callable $callback, float $timeout = 0) {
        // добавляем запрос в очередь
        $this->_requestQue->enqueue(array(
            'method' => strtoupper($method),
            'path' => $path,
            'body' => $body,
            'headerArray' => $headerArray,
            'callback' => $callback,
            'timeout' => $timeout,
        ));

        if (!$this->_activeRequest) {
            $this->_checkRequestQue();
        }
    }

    public function connect() {
        $this->_reset();

        $this->_activeRequest = true;
        $this->_updateState(self::_STATE_CONNECTING, false, true, false);

        $this->stream = stream_socket_client(
            "tcp://{$this->_ip}:{$this->_port}",
            $errno,
            $errstr,
            0, // timeout = 0, чтобы мгновенно вернулось
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT,
            stream_context_create()  // без ssl-опций!
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
        $this->_reset();
        fclose($this->stream);
    }

    public function readyRead() {
        $this->_checkEOF();

        switch ($this->_state) {
            case self::_STATE_HANDSHAKE:
                $this->_checkHandshake();
                return;
            case self::_STATE_WAIT_FOR_RESPONSE_HEADERS:
                $this->_checkResponseHeaders();
                return;
            case self::_STATE_WAIT_FOR_RESPONSE_BODY:
                $this->_checkResponseBody();
                return;
        }
    }

    public function readyWrite() {
        switch ($this->_state) {
            case self::_STATE_CONNECTING:
                // коннект установился, я готов к записи
                stream_context_set_option($this->stream, array(
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                    ],
                ));
                stream_context_set_option($this->stream, 'ssl', 'peer_name', $this->_host);
                stream_context_set_option($this->stream, 'ssl', 'allow_self_signed', true);

                $this->_updateState(self::_STATE_HANDSHAKE, true, true, false, false);
                $this->_checkHandshake();
                return;
            case self::_STATE_HANDSHAKE:
                $this->_checkHandshake();
                return;
            case self::_STATE_READY:
                $this->_activeRequest = false;

                $this->_checkRequestQue();
                return;
        }
    }

    public function readyExcept() {
        $this->_checkEOF();

        if ($this->_state == self::_STATE_HANDSHAKE) {
            $this->_checkHandshake();
            return;
        }
    }

    public function readySelectTimeout() {
        if ($this->_activeRequest && !empty($this->_activeRequest['timeout'])) {
            $ts = microtime(true);
            if ($ts - $this->_activeRequestTS >= $this->_activeRequest['timeout']) {
                $cb = $this->_activeRequest['callback'];
                $cb($this->_activeRequestTS, $ts, 408, 'Request Timeout', [], '');

                $this->disconnect();
                $this->connect();
            }
        }
    }

    private function _checkEOF() {
        if (feof($this->stream)) {
            if ($this->_activeRequest && is_array($this->_activeRequest)) {
                $cb = $this->_activeRequest['callback'];
                $cb(
                    $this->_activeRequestTS,
                    microtime(true),
                    0, // http code 0
                    'Connection closed by server', // ясное сообщение
                    [], // заголовков нет
                    '' // тела нет
                );
            }

            $this->disconnect();
            $this->connect();
        }
    }

    private function _checkRequestQue() {
        if ($this->_requestQue->isEmpty()) {
            return;
        }

        $this->_activeRequest = $this->_requestQue->dequeue();
        $this->_activeRequestTS = microtime(true);
        if (!empty($this->_activeRequest['timeout'])) {
            $this->timeoutTo = $this->_activeRequestTS + $this->_activeRequest['timeout'];
        } else {
            $this->timeoutTo = 0;
        }

        $request = $this->_activeRequest['method']." ".$this->_activeRequest['path']." HTTP/1.1\r\n";
        foreach ($this->_activeRequest['headerArray'] as $value) {
            $request .= "{$value}\r\n";
        }
        if ($this->_activeRequest['body']) {
            $length = strlen($this->_activeRequest['body']);
            $request .= "Content-Length: {$length}\r\n";
        }

        $request .= "Host: {$this->_host}\r\n";
        //$request .= "Connection: close\r\n";
        $request .= "Connection: keep-alive\r\n";
        $request .= "\r\n";
        if ($this->_activeRequest['body']) {
            $request .= $this->_activeRequest['body'];
        }

        $n = fwrite($this->stream, $request);
        if ($n === false) {
            $cb = $this->_activeRequest['callback'];
            $cb(
                $this->_activeRequestTS,
                microtime(true),
                0, // http code 0
                'Connection closed by server', // ясное сообщение
                [], // заголовков нет
                '' // тела нет
            );

            $this->disconnect();
            $this->connect();
            return;
        }

        $this->_updateState(
            self::_STATE_WAIT_FOR_RESPONSE_HEADERS,
            true,
            false,
            false,
            false
        );
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
            $this->_reset();

            $this->_updateState(self::_STATE_READY, false, false, false, false);

            $this->_checkRequestQue();
        }
    }

    private function _checkResponseHeaders() {
        $line = fgets($this->stream, 2048);
        if ($line !== false) {
            $this->_buffer .= $line;
            // пустая строка — конец блока заголовков
            if ($line == "\r\n" || $line == "\n") {
                // разбираем заголовки в ассоц. массив
                $lines = explode("\r\n", trim($this->_buffer));

                // Формат статус-строки: HTTP/1.1 200 OK
                $statusParts = explode(' ', $lines[0], 3);
                // $statusParts[0] = "HTTP/1.1"
                // $statusParts[1] = "200"
                // $statusParts[2] = "OK"
                $this->_statusCode = isset($statusParts[1]) ? (int)$statusParts[1] : 0;
                $this->_statusMessage = isset($statusParts[2]) ? (string)$statusParts[2] : null;

                $this->_headerArray = [];
                for ($i = 1, $n = count($lines); $i < $n; $i++) {
                    // Пропускаем пустые строки (например, если что-то пошло не так)
                    if ($lines[$i] === '') {
                        continue;
                    }
                    // Разделяем заголовок на имя и значение
                    [$name, $value] = explode(': ', $lines[$i], 2);
                    $this->_headerArray[strtolower($name)] = $value;
                }

                $this->_updateState(
                    self::_STATE_WAIT_FOR_RESPONSE_BODY,
                    true,
                    false,
                    false,
                    false,
                );
                $this->_buffer = '';

                return;
            }
        }

        if ($line === '') {
            $this->_checkEOF();
        }
    }

    private function _checkResponseBody() {
        if (isset($this->_headerArray['content-length'])) {
            // ровно N байт
            $length = (int)$this->_headerArray['content-length'];
            $chunk = fread($this->stream, 8192);

            if ($chunk !== false && $chunk !== '') {
                $this->_buffer .= $chunk;
            }

            if ($chunk === '') {
                $this->_checkEOF();
            }

            if (strlen($this->_buffer) == $length) {
                $tsNow = microtime(true);
                // @todo возможно своя структура response с таймерами:
                // когда начал, когда закончил, что было в запросе,
                // id потому что мне идентифицировать его как-то надо
                $cb = $this->_activeRequest['callback'];
                $cb(
                    $this->_activeRequestTS,
                    $tsNow,
                    $this->_statusCode,
                    $this->_statusMessage,
                    $this->_headerArray,
                    $this->_buffer
                );

                $this->_updateState(self::_STATE_READY, false, false, false, false);
                $this->_reset();
                $this->_checkRequestQue();
            }
        } elseif (isset($this->_headerArray['transfer-encoding']) && strtolower($this->_headerArray['transfer-encoding']) === 'chunked') {
            // loop, чтобы «прокачать» как можно больше данных за один вызов
            while (true) {
                // 1) Если ещё не читали размер текущего чанка
                if ($this->_currentChunkSize === null) {
                    $line = fgets($this->stream);
                    if ($line === false) {
                        // данных пока нет — выходим, дождёмся следующего select
                        return;
                    }

                    if ($line === '') {
                        $this->_checkEOF();
                    }

                    $this->_currentChunkSize = hexdec(trim($line));
                    // если нулевой размер — это последний чанк
                    if ($this->_currentChunkSize === 0) {
                        // пропускаем завершающий CRLF
                        fgets($this->stream);
                        // вызываем ваш callback
                        $tsNow = microtime(true);
                        $this->_activeRequest['callback'](
                            $this->_activeRequestTS,
                            $tsNow,
                            $this->_statusCode,
                            $this->_statusMessage,
                            $this->_headerArray,
                            $this->_buffer
                        );
                        // сбрасываем state
                        $this->_updateState(self::_STATE_READY, false, false, false, false);
                        $this->_buffer = '';
                        $this->_headerArray = [];
                        $this->_statusCode = 0;
                        $this->_statusMessage = '';
                        $this->_activeRequest = false;
                        $this->_activeRequestTS = 0;
                        $this->_currentChunkSize = null;
                        $this->_currentChunkRead = 0;
                        // запускаем следующий запрос, если есть
                        $this->_checkRequestQue();
                        return;
                    }
                    // начинаем читать этот чанк
                    $this->_currentChunkRead = 0;
                }

                // 2) Читаем из тела чанка столько, сколько есть
                $toRead = $this->_currentChunkSize - $this->_currentChunkRead;
                $part = fread($this->stream, min(8192, $toRead));
                if ($part === false || $part === '') {
                    // пока нечего читать
                    return;
                }
                $this->_buffer .= $part;
                $this->_currentChunkRead += strlen($part);

                // 3) Если до конца текущего чанка ещё далеко — выходим
                if ($this->_currentChunkRead < $this->_currentChunkSize) {
                    return;
                }

                // 4) Мы дошли до конца этого чанка — пропускаем CRLF
                fgets($this->stream);

                // 5) Сбрасываем счётчики, чтобы на следующей итерации
                //    прочитать следующий заголовок чанка
                $this->_currentChunkSize = null;
                $this->_currentChunkRead = 0;

                // и loop’ом сразу же попробуем прочитать его размер,
                // или вернёмся, если данных не хватит
            }
        } else {
            // @todo
            // нет длины и не chunked — придётся читать до timeout или
            // возвращать то, что есть, и оставить соединение открытым
            // или нахер закрываться
        }
    }

    private $_currentChunkSize = null;
    private int $_currentChunkRead = 0;

    /**
     * @return SplQueue
     */
    public function getRequestQue() {
        return $this->_requestQue;
    }

    private function _updateState($state, $flagRead, $flagWrite, $flagExcept) {
        $this->_state = $state;
        $this->flagRead = $flagRead;
        $this->flagWrite = $flagWrite;
        $this->flagExcept = $flagExcept;
    }

    public function getState() {
        return $this->_state;
    }

    private function _reset() {
        $this->_buffer = '';
        $this->_statusCode = 0;
        $this->_statusMessage = '';
        $this->_headerArray = [];
        $this->_activeRequestTS = 0;
        $this->_activeRequest = false;
        $this->timeoutTo = 0;
    }

    private $_host, $_port, $_ip;

    private $_state = '';

    private $_buffer = '';
    private $_headerArray = [];
    private $_statusCode = 0;
    private $_statusMessage = '';

    private $_activeRequest;
    private $_activeRequestTS = 0;
    private SplQueue $_requestQue;

    private const _STATE_CONNECTING = 'connecting';
    private const _STATE_HANDSHAKE = 'handshake';
    private const _STATE_WAIT_FOR_RESPONSE_HEADERS = 'wait-for-response-headers';
    private const _STATE_WAIT_FOR_RESPONSE_BODY = 'wait-for-response-body';
    private const _STATE_READY = 'ready';

}