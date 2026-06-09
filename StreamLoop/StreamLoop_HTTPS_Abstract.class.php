<?php
abstract class StreamLoop_HTTPS_Abstract extends StreamLoop_TCP_Abstract {

    abstract protected function _setupConnection();
    abstract protected function _onReceive($tsSelect, $statusCode, $statusMessage, $headerArray, $body);
    abstract protected function _onError($tsSelect, $errorCode, $errorMessage);
    abstract protected function _onReady($tsSelect); // @todo переделать на FSM Events?

    public function updateConnection($host, $port, $ip = false, $bindIP = false, $bindPort = false) {
        $this->_updateDestinationHost($host);
        $this->_updateDestinationPort($port);
        $this->_updateDestinationIP($ip);
        $this->_updateSourceIP($bindIP);
        $this->_updateSourcePort($bindPort);
    }

    // @todo вместо $timeout=sec сразу передавать $timeoutTo=ttl, это позволит убрать microtime call
    public function write($method, $path, $body, $headerArray, $timeout = 10) {
        if ($this->_active) { // @todo if-tree
            throw new StreamLoop_Exception(__CLASS__." already under active request");
        }

        if ($timeout) {
            $timeout = (float) $timeout;
        } else {
            $timeout = 10; // 10 sec everytime
        }

        $this->_active = true;

        $request = $method." ".$path." HTTP/1.1\r\n";
        foreach ($headerArray as $value) {
            $request .= "{$value}\r\n";
        }
        if ($body) {
            $request .= "Content-Length: ".strlen($body)."\r\n";
        }

        $request .= "Host: {$this->_host}\r\n";
        //$request .= "Connection: close\r\n"; // нельзя писать close для keep-alive
        $request .= "Connection: keep-alive\r\n";
        $request .= "\r\n";
        $request .= $body; // даже если body пустота - ну и ладно, это бытсрее if (body) ...

        $n = fwrite($this->stream, $request);
        if ($n === false) { // @todo отказаться от === и сделать другой if
            $this->throwError( // closed by server / reset by peer
                microtime(true), // tsSelect
                StreamLoop_HTTPS_Const::ERROR_CLOSED_BY_SERVER, // http code 0
                'Connection closed by server', // ясное сообщение
            );

            return;
        }

        // timeout на запрос есть всегда, по дефолту это 10 сек (см код выше)
        $this->_state = StreamLoop_HTTPS_Const::STATE_WAIT_FOR_RESPONSE_HEADERS; // new request

        // я специально регистрирую тут handler снова, потому что после успешного ответа вызывался _reset и handler был снят:
        // я так сделал специально, чтобы StreamLoop не таскал ничего в себе для пассивных HTTP соединений
        // @todo сделать updateStreamState method
        $this->_loop->registerHandler($this, true, false, microtime(true) + $timeout); // request sent -> waiting for headers
    }

    public function connect() {
        // перед connect надо вызвать setupConnection чтобы он поправил все параметры соединения
        $this->_setupConnection();

        $this->_active = true; // ставим флаг что я активен (потому что подключаюсь)
        $this->_state = StreamLoop_HTTPS_Const::STATE_CONNECTING; // in 1st connect

        $this->_createAndConnectTCP();
    }

    public function disconnect() {
        // reset сам сделает updateHandler в ноль
        $this->_reset(StreamLoop_HTTPS_Const::STATE_DISCONNECTED); // reset in disconnect

        // бывают ситуации когда throwError два раза подряд и тогда disconnect два раза подряд
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->streamID = 0;
        $this->stream = null;
    }

    public function readyRead($tsSelect) {
        // if-tree optimization
        if ($this->_state == StreamLoop_HTTPS_Const::STATE_WAIT_FOR_RESPONSE_HEADERS) {
            // drain read headers
            do {
                $line = fgets($this->stream, 4096); // я читаю через fgetS и врядли будет строка больше 4Kb

                $this->_buffer .= $line;

                // такая строка — конец блока заголовков
                if ($line == "\r\n" || $line == "\n") {
                    // разбираем заголовки в ассоц. массив
                    $lines = explode("\r\n", $this->_buffer);

                    // Формат статус-строки: HTTP/1.1 200 OK
                    $statusParts = explode(' ', $lines[0], 3);
                    // $statusParts[0] = "HTTP/1.1"
                    // $statusParts[1] = "200"
                    // $statusParts[2] = "OK"

                    $this->_statusCode = (int) $statusParts[1] ?? 0;
                    $this->_statusMessage = $statusParts[2] ?? '';

                    $this->_headerArray = [];
                    foreach ($lines as $line) {
                        // Пропускаем пустые строки (например, если что-то пошло не так)
                        if ($line) {
                            // Разделяем заголовок на имя и значение
                            $x = explode(':', $line, 2);
                            if (count($x) == 2) {
                                $this->_headerArray[strtolower(trim($x[0]))] = trim($x[1]);
                            }
                        }
                    }

                    $this->_state = StreamLoop_HTTPS_Const::STATE_WAIT_FOR_RESPONSE_BODY; // in read

                    $this->_buffer = '';

                    return;
                } elseif (!$line) {
                    // fgets может вернуть false - это или просто ничего нет в не-блок-режиме или реально EOF (не путай с fread)
                    $this->_checkEOF();
                    break; // break цикла
                }
            } while (true);
        } elseif ($this->_state == StreamLoop_HTTPS_Const::STATE_WAIT_FOR_RESPONSE_BODY) {
            // @todo как смержить wait for headers & body в кучу? Все равно у меня http 1.1
            $headerArray = $this->_headerArray;

            if (isset($headerArray['content-length'])) {
                // ровно N байт
                $length = (int) $headerArray['content-length'];

                // to locals
                // @todo возможно не стоит делать to locals так как в 99% случаев чтение одно
                $buffer = $this->_buffer;

                // dynamic drain read
                $drainIndex = 10;
                do {
                    $chunk = fread($this->stream, 4096);

                    // дописываемся всегда: так быстрее, потому что как правило $chunk это string или empty string.
                    // И даже если он false - то дальше сработао проверка
                    $buffer .= $chunk;

                    if (strlen($buffer) == $length) {
                        // надо сначала поменять состояние и все очистить,
                        // а только потом вызывать onResponce,
                        // потому что в onResponce я могу вызвать request снова, а там проверка на activeRequest
                        $statusCode = $this->_statusCode; // запоминаем перед очисткой
                        $statusMessage = $this->_statusMessage; // запоминаем перед очисткой

                        $this->_reset(); // reset in wait for body

                        $this->_onReceive(
                            $tsSelect,
                            $statusCode,
                            $statusMessage,
                            $headerArray,
                            $buffer
                        );

                        // очистка буфера, потому что считали тело до конца
                        $buffer = '';

                        break;
                    } elseif ($chunk === '') {
                        // drain stop
                        break;
                    } elseif ($chunk === false) {
                        // в неблокирующем режиме если данных нет - то будет string ''
                        // а если false - то это ошибка чтения
                        // например, PHP Warning: fread(): SSL: Connection reset by peer
                        $this->_checkEOF();
                        break;
                    }
                } while (--$drainIndex);

                $this->_buffer = $buffer;
            } elseif (isset($headerArray['transfer-encoding']) && stripos($headerArray['transfer-encoding'], 'chunked') !== false) {
                // ---- chunked ----
                // докачиваем сырой поток chunked-данных в _buffer
                $drainIndex = 10;
                do {
                    $chunk = fread($this->stream, 4096);
                    if ($chunk === '') {
                        break;
                    } elseif ($chunk === false) {
                        $this->_checkEOF();
                        break;
                    }
                    $this->_buffer .= $chunk;
                } while (--$drainIndex);

                // пытаемся распарсить то, что уже есть в _buffer
                do {
                    // 1) если не знаем размер текущего чанка — читаем строку размера
                    if ($this->_chunkExpected === null) {
                        $pos = strpos($this->_buffer, "\r\n");
                        if ($pos === false) {
                            // нет целой строки размера
                            break;
                        }

                        $line = substr($this->_buffer, 0, $pos);
                        $this->_buffer = (string) substr($this->_buffer, $pos + 2);

                        // отрезаем chunk-ext после ';'
                        $sc = strpos($line, ';');
                        if ($sc !== false) {
                            $line = substr($line, 0, $sc);
                        }

                        $line = trim($line);
                        if ($line === '') {
                            // иногда бывает лишний CRLF — просто пропускаем
                            continue;
                        }

                        // hex -> int
                        $size = hexdec($line);
                        $this->_chunkExpected = $size;

                        // нулевой чанк = конец. дальше могут быть трейлеры + пустая строка
                        if ($size === 0) {
                            // ждём конец трейлеров: \r\n\r\n (или просто \r\n если трейлеров нет)
                            $end = strpos($this->_buffer, "\r\n\r\n");
                            if ($end !== false) {
                                $this->_buffer = (string) substr($this->_buffer, $end + 4);
                            } else {
                                // самый частый кейс: сразу \r\n
                                if (substr($this->_buffer, 0, 2) === "\r\n") {
                                    $this->_buffer = (string) substr($this->_buffer, 2);
                                } else {
                                    // трейлеры ещё не пришли полностью
                                    break;
                                }
                            }

                            // готово — отдаём
                            $statusCode = $this->_statusCode;
                            $statusMessage = $this->_statusMessage;
                            $body = $this->_bodyDecoded;

                            $this->_reset(); // chunked parser

                            $this->_onReceive(
                                $tsSelect,
                                $statusCode,
                                $statusMessage,
                                $headerArray,
                                $body
                            );

                            break; // всё, запрос завершён
                        }
                    }

                    // 2) у нас есть ожидаемый размер чанка > 0: ждём данные + \r\n
                    $need = $this->_chunkExpected + 2; // data + CRLF
                    if (strlen($this->_buffer) < $need) {
                        break; // не хватает данных
                    }

                    $data = substr($this->_buffer, 0, $this->_chunkExpected);
                    $crlf = substr($this->_buffer, $this->_chunkExpected, 2);

                    // “по тупому”: если не CRLF — можно либо падать, либо пытаться жить
                    if ($crlf !== "\r\n") {
                        throw new StreamLoop_Exception("Bad chunked encoding (missing CRLF after chunk data)");
                    }

                    $this->_bodyDecoded .= $data;
                    $this->_buffer = substr($this->_buffer, $need);

                    // ждём следующий chunk-size
                    $this->_chunkExpected = null;
                } while (true);
            } else {
                throw new StreamLoop_Exception('Unsupported encoding');
            }
        } elseif ($this->_state == StreamLoop_HTTPS_Const::STATE_HANDSHAKING) {
            $this->_processHandshake($tsSelect);
        }
    }

    public function readyWrite($tsSelect) {
        // if-tree optimization
        if ($this->_state == StreamLoop_HTTPS_Const::STATE_READY) {
            $this->_active = false;
        } elseif ($this->_state == StreamLoop_HTTPS_Const::STATE_CONNECTING) {
            // коннект установился, я готов к записи
            stream_context_set_option($this->stream, [
                'ssl' => [
                    'SNI_enabled' => true,
                    'SNI_server_name' => $this->_host,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'peer_name' => $this->_host,
                    'allow_self_signed' => true,
                ],
            ]);

            $this->_state = StreamLoop_HTTPS_Const::STATE_HANDSHAKING; // handshake starting

            // NB! НЕ ставим write, потому что во время handshaking всегда идет write и просто зайобка
            $this->_loop->registerHandler($this, true, false, $this->_timeoutTo); // connected done -> waiting for SSL handshake

            // и сразу же проверяем его, вдруг подключился
            $this->_processHandshake($tsSelect);
        } elseif ($this->_state == StreamLoop_HTTPS_Const::STATE_HANDSHAKING) {
            $this->_processHandshake($tsSelect);
        }
    }

    public function readyTimeout($tsSelect) {
        // если прошел timeout - кидаем ошибку и отключаемся;
        // это касается любого типа timeout - request, connecting, handshaking.
        // потому что все равно соединению пизда

        // важно: readySelectTimeout не может вызваться если timeout не настал, поэтому никаких проверок на timeout'ы тут просто делать не надо.

        $this->throwError( // timeout 408
            $tsSelect,
            StreamLoop_HTTPS_Const::ERROR_TIMEOUT,
            'timeout',
        );
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

    private function _checkEOF() {
        if (feof($this->stream)) {
            // затем кидаем ошибку
            $this->throwError( // EOF
                microtime(true),
                StreamLoop_HTTPS_Const::ERROR_EOF, // http code 0
                'Connection closed by server', // ясное сообщение
            );

            return true;
        }

        return false;
    }

    private function _processHandshake($tsSelect) {
        $return = stream_socket_enable_crypto(
            $this->stream,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if ($return === true) {
            // я подключился
            $this->_reset(); // reset in handshake

            // готов + бросам событие что я готов
            $this->_onReady($tsSelect);
        } elseif ($return === false) {
            $this->throwError( // handshake
                $tsSelect,
                StreamLoop_HTTPS_Const::ERROR_HANDSHAKE,
                'Failed to setup SSL'
            );

            return; // чтобы не лупиться в eof
        }

        $this->_checkEOF();
    }

    private function _reset($state = StreamLoop_HTTPS_Const::STATE_READY) {
        // чистка всего перед новым запросом или отключением
        $this->_buffer = '';
        $this->_statusCode = 0;
        $this->_statusMessage = '';
        $this->_headerArray = [];
        $this->_active = false;

        // reset chunked state
        $this->_chunkExpected = null;
        $this->_bodyDecoded = '';

        // обнуляем состояние в ready и стираем все таймеры
        $this->_state = $state; // in reset

        $this->_timeoutTo = 0;
        $this->_loop->unregisterHandler($this); // важно: reset снимает регистрацию handler'a
    }

    public function getState() {
        return $this->_state;
    }

    private $_buffer = ''; // string
    private $_headerArray = [];
    private $_statusCode = 0; // int
    private $_statusMessage = ''; // string
    private $_active = false; // bool
    private $_state = 0; // int, 0 is STATE_DISCONNECTED, by default disconnected
    private $_chunkExpected = null; // int|null, сколько байт данных ждем в текущем чанке
    private $_bodyDecoded = ''; // сюда складываем уже декодированное тело (без chunk-обвязки)

}