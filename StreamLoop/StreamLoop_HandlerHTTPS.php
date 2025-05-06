<?php
class StreamLoop_HandlerHTTPS implements StreamLoop_IHandler {

    public function __construct($host, $port) {
        $this->_host = $host;
        $this->_port = $port;

        $this->_requestQue = new SplQueue();

        // соединение я начинаю устанавливать сразу же
        // @todo в будущем можно переделать на установку соединения по требованию, но пока это просто не актульано

        // @todo я могу коннектор закинуть внутрь "request", он будет как команда handshake.
        // просто handshake ловит свои события,
        // и в случае успеха он заверщается на ready read
        // надо попробовать

        $this->_connect();
    }

    public function request(string $method, string $path, string $body, array $headerArray, callable $callback) {
        // добавляем запрос в очередь
        $this->_requestQue->enqueue(array(
            'method' => strtoupper($method),
            'path' => $path,
            'body' => $body,
            'headerArray' => $headerArray,
            'callback' => $callback,
        ));

        if (!$this->_activeRequest) {
            $this->_checkRequestQue();
        }
    }

    private function _connect() {
        $this->_activeRequest = true;
        $this->_updateState(self::_STATE_CONNECTING, false, true, false);

        $ctx = stream_context_create();  // без ssl-опций!
        $flags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT;
        $this->_stream = stream_socket_client(
            "tcp://{$this->_host}:{$this->_port}",
            $errno,
            $errstr,
            0, // timeout = 0, чтобы мгновенно вернулось
            $flags,
            $ctx
        );
        if (!$this->_stream) {
            throw new StreamLoop_Exception("TCP connect failed immediately: $errstr ($errno)");
        }

        stream_set_blocking($this->_stream, false);

        // отключаем буферизацию php
        stream_set_read_buffer($this->_stream, 0);
        stream_set_write_buffer($this->_stream, 0);
    }

    public function readyRead() {
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
                stream_context_set_option($this->_stream, array(
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                    ],
                ));
                stream_context_set_option($this->_stream, 'ssl', 'peer_name', $this->_host);
                stream_context_set_option($this->_stream, 'ssl', 'allow_self_signed', true);

                $this->_updateState(self::_STATE_HANDSHAKE, true, true, false);
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

    private function _checkRequestQue() {
        if ($this->_requestQue->isEmpty()) {
            return;
        }

        $this->_activeRequest = $this->_requestQue->dequeue();
        $this->_activeRequestTS = microtime(true);

        $request = $this->_activeRequest['method']." ".$this->_activeRequest['path']." HTTP/1.1\r\n";
        foreach ($this->_activeRequest['headerArray'] as $name => $value) {
            $request .= "{$name}: {$value}\r\n";
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

        fwrite($this->_stream, $request);

        $this->_updateState(self::_STATE_WAIT_FOR_RESPONSE_HEADERS, true, false, false);
    }

    public function readyExcept() {
        //var_dump('EXCEPT'); // @todo

        if ($this->_state == self::_STATE_HANDSHAKE) {
            $this->_checkHandshake();
            return;
        }
    }

    private function _checkHandshake() {
        $return = stream_socket_enable_crypto(
            $this->_stream,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if ($return === false) {
            throw new StreamLoop_Exception("Failed to setup SSL");
        }

        if ($return === true) {
            $this->_updateState(self::_STATE_READY, false, false, false);

            $this->_checkRequestQue();
        }
    }

    private function _checkResponseHeaders() {
        $line = fgets($this->_stream, 2048);
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

                $this->_updateState(self::_STATE_WAIT_FOR_RESPONSE_BODY, true, false, false);
                $this->_buffer = '';

                return;
            }
        }
    }

    private function _checkResponseBody() {
        if (isset($this->_headerArray['content-length'])) {
            // ровно N байт
            $length = (int)$this->_headerArray['content-length'];
            $chunk = fread($this->_stream, 8192);

            if ($chunk !== false && $chunk !== '') {
                $this->_buffer .= $chunk;
            }

            if (strlen($this->_buffer) == $length) {
                $tsNow = microtime(true);
                // @todo возможно своя структура response с таймерами: когда начал, когда закончил, что было в запросе (потому что мне идентифицировать его как-то его надо)
                $this->_activeRequest['callback']($this->_activeRequestTS, $tsNow, $this->_statusCode, $this->_statusMessage, $this->_headerArray, $this->_buffer);

                $this->_updateState(self::_STATE_READY, false, false, false);
                $this->_buffer = '';
                $this->_headerArray = [];
                $this->_statusCode = 0;
                $this->_statusMessage = '';
                $this->_activeRequest = false;
                $this->_activeRequestTS = 0;

                $this->_checkRequestQue();
            }
        } elseif (isset($this->_headerArray['transfer-encoding']) && strtolower($this->_headerArray['transfer-encoding']) === 'chunked') {
            // @todo
            // chunked-encoding
            /*while (true) {
                $line = fgets($this->_stream);
                $chunkSize = hexdec(trim($line));
                if ($chunkSize === 0) {
                    // финальный chunk
                    fgets($this->_stream); // читает завершающий CRLF
                    break;
                }
                $read = 0;
                while ($read < $chunkSize) {
                    $part = fread($this->_stream, min(8192, $chunkSize - $read));
                    if ($part === false || $part === '') break 2;
                    $body .= $part;
                    $read += strlen($part);
                }
                fgets($this->_stream); // CRLF после куска
            }*/
        } else {
            // @todo
            // нет длины и не chunked — придётся читать до timeout или
            // возвращать то, что есть, и оставить соединение открытым
        }
    }

    public function getStreamConfig() {
        if (feof($this->_stream)) {
            //var_dump('EOF');

            // @todo в зависимости от того отправлен был запрос или нет - лучше по разному вести себя:
            // не был отправлен - добавляем
            // уже был отправлен (и мог быть выполнен) - callback шо всему пизда
            // = для HFT не критично из-за unique nonce, можно повторять всегда
            if ($this->_activeRequest && $this->_activeRequest !== true) {
                $this->_requestQue->enqueue($this->_activeRequest);
                $this->_activeRequest = false;
            }

            $this->_connect();
        }

        // [stream, r, w, e]
        // @todo а нафига я выдаю, если я могу просто менять эти флаги readonly?
        // прийдется делать класс
        // @todo но куда тогда перенести проверку eof?
        return [$this->_stream, $this->_flagRead, $this->_flagWrite, $this->_flagExcept];
    }

    /**
     * @return SplQueue
     */
    public function getRequestQue() {
        return $this->_requestQue;
    }

    private function _updateState($state, $flagRead, $flagWrite, $flagExcept) {
        $this->_state = $state;
        $this->_flagRead = $flagRead;
        $this->_flagWrite = $flagWrite;
        $this->_flagExcept = $flagExcept;
    }

    private $_stream;

    private $_host, $_port;

    private $_state = '';

    private $_buffer = '';
    private $_headerArray = [];
    private $_statusCode = 0;
    private $_statusMessage = '';

    private $_activeRequest;
    private $_flagRead = false, $_flagWrite = false, $_flagExcept = false;
    private $_activeRequestTS = 0;
    private SplQueue $_requestQue;

    private const _STATE_CONNECTING = 'connecting';
    private const _STATE_HANDSHAKE = 'handshake';
    private const _STATE_WAIT_FOR_RESPONSE_HEADERS = 'wait-for-response-headers';
    private const _STATE_WAIT_FOR_RESPONSE_BODY = 'wait-for-response-body';
    private const _STATE_READY = 'ready';

}