<?php
class StreamLoop_HandlerHTTPS extends StreamLoop_AHandler {

    public function __construct(StreamLoop $loop, $host, $port, $ip = false) {
        parent::__construct($loop);

        $this->_host = $host;
        $this->_port = $port;
        $this->setIP($ip);

        $this->_requestQue = new SplQueue();

        // соединение я начинаю устанавливать сразу же
        // @todo в будущем можно переделать на установку соединения по требованию, но пока это просто не актульано
        $this->connect();

        // @todo я могу коннектор закинуть внутрь "request", он будет как команда handshake.
        // просто handshake ловит свои события,
        // и в случае успеха он заверщается на ready read
        // надо попробовать
    }

    public function request($method, $path, $body, $headerArray, $callback, $timeout = 0) {
        // добавляем запрос в очередь
        $this->_requestQue->enqueue(array(
            'method' => strtoupper($method),
            'path' => $path,
            'body' => $body,
            'headerArray' => $headerArray,
            'callback' => $callback,
            'timeout' => (float) $timeout, // timeout нужен всегда
        ));

        if (!$this->_activeRequest) {
            $this->_checkRequestQue();
        }
    }

    public function setIP($ip) {
        // этот метод нужен чтобы на лету менять ip не пересоздавая полностью весь handler
        $this->_ip = $ip;
    }

    public function connect() {
        $this->_loop->unregisterHandler($this);

        $this->_reset();

        $this->_activeRequest = true; // @todo от это жопа

        $ip = $this->_ip ? $this->_ip : $this->_host;

        $stream = stream_socket_client(
            "tcp://{$ip}:{$this->_port}",
            $errno,
            $errstr,
            0, // timeout = 0, чтобы мгновенно вернулось
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT,
            stream_context_create()  // без ssl-опций!
        );
        if (!$stream) {
            throw new StreamLoop_Exception("TCP connect failed immediately: $errstr ($errno)");
        }

        $this->streamID = (int) $stream;
        $this->stream = $stream;

        $this->_loop->registerHandler($this);

        $this->_updateState(self::_STATE_CONNECTING, false, true, false);

        stream_set_blocking($stream, false);

        // отключаем буферизацию php
        stream_set_read_buffer($stream, 0);
        stream_set_write_buffer($stream, 0);
    }

    public function disconnect() {
        $this->_reset();
        fclose($this->stream);
    }

    public function readyRead($tsSelect) {
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

    public function readyWrite($tsSelect) {
        switch ($this->_state) {
            case self::_STATE_CONNECTING:
                // коннект установился, я готов к записи
                $stream = $this->stream;

                stream_context_set_option($stream, array(
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                    ],
                ));
                stream_context_set_option($stream, 'ssl', 'peer_name', $this->_host);
                stream_context_set_option($stream, 'ssl', 'allow_self_signed', true);

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

    public function readyExcept($tsSelect) {
        $this->_checkEOF(); // тут оставить как есть, потому что state machine не покрывает все косяки

        if ($this->_state == self::_STATE_HANDSHAKE) {
            $this->_checkHandshake();
            return;
        }
    }

    // @todo встроить tsSelect во все callback

    public function readySelectTimeout($tsSelect) {
        if ($this->_activeRequest && isset($this->_activeRequest['timeout'])) { // @todo жопа
            $timeout = $this->_activeRequest['timeout'];
            if ($timeout > 0) {
                $ts = microtime(true);
                $tsRequest = $this->_activeRequestTS;
                if ($ts - $tsRequest >= $timeout) {
                    $cb = $this->_activeRequest['callback'];
                    $cb($tsRequest, $ts, 408, 'Request Timeout', [], '');

                    $this->disconnect();
                    $this->connect();
                }
            }
        }
    }

    private function _checkEOF() {
        if (feof($this->stream)) {
            // @todo говно с double typing
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
        // to locals
        $que = $this->_requestQue;

        if ($que->isEmpty()) {
            return;
        }

        // @todo to locals
        $this->_activeRequest = $que->dequeue();
        $this->_activeRequestTS = microtime(true); // время когда я начал запрос

        // @todo все ниже после вдувания в порт
        if (!empty($this->_activeRequest['timeout'])) {
            $this->_loop->updateHandlerTimeout($this, $this->_activeRequestTS + $this->_activeRequest['timeout']);
        } else {
            $this->_loop->updateHandlerTimeout($this, 0);
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
        //$request .= "Connection: close\r\n"; // нельзя писать close для keep-alive
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
        );
    }

    private function _checkHandshake() {
        $return = stream_socket_enable_crypto(
            $this->stream,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if ($return === true) {
            $this->_reset();

            $this->_updateState(self::_STATE_READY, false, false, false);

            $this->_checkRequestQue();
        } elseif ($return === false) {
            throw new StreamLoop_Exception("Failed to setup SSL");
        }

        $this->_checkEOF();
    }

    private function _checkResponseHeaders() {
        // @todo inline it

        $line = fgets($this->stream, 4096);

        $this->_buffer .= $line; // @todo lo locals?

        // пустая строка — конец блока заголовков
        if ($line == "\r\n" || $line == "\n") {
            // разбираем заголовки в ассоц. массив
            $lines = explode("\r\n", $this->_buffer);

            // Формат статус-строки: HTTP/1.1 200 OK
            $statusParts = explode(' ', $lines[0], 3);
            // $statusParts[0] = "HTTP/1.1"
            // $statusParts[1] = "200"
            // $statusParts[2] = "OK"

            // @todo лажа
            $this->_statusCode = isset($statusParts[1]) ? (int) $statusParts[1] : 0;
            $this->_statusMessage = isset($statusParts[2]) ? (string) $statusParts[2] : null;

            $this->_headerArray = [];
            $n = count($lines);
            for ($i = 1; $i < $n; $i++) { // @todo лажа
                // Пропускаем пустые строки (например, если что-то пошло не так)
                if (!$lines[$i]) {
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
            );

            $this->_buffer = '';
        } elseif ($line === false) {
            $this->_checkEOF();
        }
    }

    private function _checkResponseBody() {
        // @todo inline it

        if (isset($this->_headerArray['content-length'])) {
            // @todo buffer to locals

            // ровно N байт
            $length = (int) $this->_headerArray['content-length'];
            $chunk = fread($this->stream, 8192); // @todo dynamic drain like in WS

            // дописываемся всегда: так быстрее, потому что как правило $chunk это string или empty string.
            // И даже если он false - то дальше сработао проверка
            $this->_buffer .= $chunk;

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

                $this->_updateState(self::_STATE_READY, false, false, false);
                $this->_reset();
                $this->_checkRequestQue();
            } elseif ($chunk === false) {
                // в неблокирующем режиме если данных нет - то будет string ''
                // а если false - то это ошибка чтения
                // например, PHP Warning: fread(): SSL: Connection reset by peer
                $this->_checkEOF();
            }
        } elseif (isset($this->_headerArray['transfer-encoding']) && strtolower($this->_headerArray['transfer-encoding']) === 'chunked') {
            throw new StreamLoop_Exception('Chunked not supported');
            // @todo этот блок пока-что не пашет и я его не проверял

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
                if ($part === '') {
                    // пока нечего читать
                    return;
                }
                if ($part === false) {
                    // @todo соединение закрыто
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
            throw new StreamLoop_Exception('Unknown encoding mode');
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
        $this->_loop->updateHandlerFlags($this, $flagRead, $flagWrite, $flagExcept);
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
        $this->_loop->updateHandlerTimeout($this, 0);
    }

    private $_host, $_port, $_ip;

    private $_buffer = '';
    private $_headerArray = [];
    private $_statusCode = 0;
    private $_statusMessage = '';

    private $_activeRequest;
    private $_activeRequestTS = 0;
    private SplQueue $_requestQue; // @todo дека медленее массива

    private $_state;
    private const _STATE_CONNECTING = 1;
    private const _STATE_HANDSHAKE = 2;
    private const _STATE_WAIT_FOR_RESPONSE_HEADERS = 3;
    private const _STATE_WAIT_FOR_RESPONSE_BODY = 4;
    private const _STATE_READY = 5;

}