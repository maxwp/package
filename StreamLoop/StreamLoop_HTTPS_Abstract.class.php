<?php
abstract class StreamLoop_HTTPS_Abstract extends StreamLoop_Handler_Abstract {

    abstract protected function _setupConnection();
    abstract protected function _onReceive($tsSelect, $statusCode, $statusMessage, $headerArray, $body);
    abstract protected function _onError($tsSelect, $errorCode, $errorMessage);
    abstract protected function _onReady($tsSelect); // @todo надо переписановать onReady, потому что для SL это скорее on 1st ready @todo после StateMachines

    public function updateConnection($host, $port, $ip = false, $bindIP = false, $bindPort = false) {
        // @todo возможно структура connection'a?
        $this->_host = $host;
        $this->_port = $port;
        $this->_ip = $ip;

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

    public function write($method, $path, $body, $headerArray, $timeout = 10) {
        if ($this->_active) {
            throw new StreamLoop_Exception("SL_HTTP already under active request");
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
        if ($n === false) {
            // явно отключаесся
            $this->disconnect();

            // и кидаем ошибку
            $this->_onError(
                microtime(true), // tsSelect
                0, // http code 0
                'Connection closed by server', // ясное сообщение
            );

            return;
        }

        // timeout на запрос есть всегда, по дефолту это 10 сек (см код выше)
        $this->_state = StreamLoop_HTTPS_Const::STATE_WAIT_FOR_RESPONSE_HEADERS; // new request
        $this->_loop->updateHandlerFlags($this, true, false, false);
        $this->_loop->updateHandlerTimeoutTo($this, microtime(true) + $timeout);
    }

    public function connect() {
        // перед connect надо вызвать setupConnection чтобы он поправил все параметры соединения
        $this->_setupConnection();

        # debug:start
        Cli::Print_n(__CLASS__." connecting to {$this->_host} ip={$this->_ip} port={$this->_port}");
        # debug:end

        $this->_active = true; // ставим флаг что я активен (потому что подключаюсь)

        $ip = $this->_ip ? $this->_ip : $this->_host;

        // супер важно: надо создавать контекст без ssl-опций!
        $context = stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,  // no Nagle algorithm
                'bindto' => "{$this->_bindIP}:{$this->_bindPort}",
            ],
        ]);

        $stream = stream_socket_client(
            "tcp://{$ip}:{$this->_port}",
            $errno,
            $errstr,
            0, // timeout = 0, чтобы мгновенно вернулось
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT,
            $context,
        );
        if (!$stream) {
            // критическая ошибка — завершаем
            throw new StreamLoop_Exception("TCP connect failed immediately: $errstr ($errno)");
        }

        $this->stream = $stream;
        $this->streamID = (int) $stream;

        // регистрируем handler
        $this->_loop->registerHandler($this);

        $this->_state = StreamLoop_HTTPS_Const::STATE_CONNECTING; // in 1st connect
        $this->_loop->updateHandlerFlags($this, false, true, true);
        // устанавливаем timeout на подключение
        $this->_loop->updateHandlerTimeoutTo($this, microtime(true) + 10);

        // Устанавливаем буфер до начала SSL
        $socket = new Connection_SocketStream($stream);
        $socket->setBufferSizeRead(10 * 1024 * 1024);
        $socket->setBufferSizeWrite(2 * 1024 * 1024);
        $socket->setKeepAlive();

        stream_set_blocking($stream, false);

        // отключаем буферизацию php
        stream_set_read_buffer($stream, 0);
        stream_set_write_buffer($stream, 0);
    }

    public function disconnect() {
        // все сбрасываем
        $this->_reset(); // reset in disconnect

        // сниммаем регистрацию
        $this->_loop->unregisterHandler($this);

        // бывают ситуации когда throwError два раза подряд и тогда disconnect два раза подряд
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->streamID = 0;
        $this->stream = null;

        $this->_state = StreamLoop_HTTPS_Const::STATE_DISCONNECTED;
    }

    public function readyRead($tsSelect) {
        $state = $this->_state; // to locals

        // if-tree optimization
        if ($state == StreamLoop_HTTPS_Const::STATE_WAIT_FOR_RESPONSE_HEADERS) {
            // drain read headers
            while (1) {
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

                    // @todo лажа
                    $this->_statusCode = isset($statusParts[1]) ? (int) $statusParts[1] : 0;
                    $this->_statusMessage = isset($statusParts[2]) ? (string) $statusParts[2] : null;

                    $this->_headerArray = [];
                    foreach ($lines as $line) {
                        // Пропускаем пустые строки (например, если что-то пошло не так)
                        if (!$line) {
                            continue;
                        }
                        // Разделяем заголовок на имя и значение
                        $x = explode(':', $line, 2);
                        if (count($x) == 2) {
                            $this->_headerArray[strtolower(trim($x[0]))] = trim($x[1]);
                        }
                    }

                    $this->_state = StreamLoop_HTTPS_Const::STATE_WAIT_FOR_RESPONSE_BODY; // in read
                    $this->_loop->updateHandlerFlags($this, true, false, false);

                    $this->_buffer = '';

                    return;
                } elseif (!$line) {
                    // fgets может вернуть false - это или просто ничего нет в не-блок-режиме или реально EOF (не путай с fread)
                    $this->_checkEOF();
                    break; // break цикла
                }
            }
        } elseif ($state == StreamLoop_HTTPS_Const::STATE_WAIT_FOR_RESPONSE_BODY) {
            // @todo как смержить wait for headers & body в кучу? Все равно у меня http 1.1
            $headerArray = $this->_headerArray;

            if (isset($headerArray['content-length'])) {
                // ровно N байт
                $length = (int) $headerArray['content-length'];

                // to locals
                $stream = $this->stream;
                $buffer = $this->_buffer;

                // dynamic drain read
                for ($drainIndex = 1; $drainIndex <= 10; $drainIndex++) {
                    $chunk = fread($stream, 4096);

                    // дописываемся всегда: так быстрее, потому что как правило $chunk это string или empty string.
                    // И даже если он false - то дальше сработао проверка
                    $buffer .= $chunk;

                    if (strlen($buffer) == $length) {
                        // надо сначала поменять состояние и все очистить,
                        // а только потом вызывать onResponce,
                        // потому что в onResponce я могу вызвать request снова, а там проверка на activeRequest
                        $statusCode = $this->_statusCode; // запоминаем перед очисткой
                        $statusMessage = $this->_statusMessage;
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
                }

                $this->_buffer = $buffer;
            } elseif (isset($headerArray['transfer-encoding']) && stripos($headerArray['transfer-encoding'], 'chunked') !== false) {
                // ---- chunked ----
                $stream = $this->stream;

                // докачиваем сырой поток chunked-данных в _buffer
                for ($drainIndex = 1; $drainIndex <= 10; $drainIndex++) {
                    $chunk = fread($stream, 4096);
                    if ($chunk === '') {
                        break;
                    } elseif ($chunk === false) {
                        $this->_checkEOF();
                        break;
                    }
                    $this->_buffer .= $chunk;
                }

                // пытаемся распарсить то, что уже есть в _buffer
                while (true) {
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

                            $this->_reset();

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
                }
            } else {
                throw new StreamLoop_Exception('Unsupported encoding');
            }
        } elseif ($state == StreamLoop_HTTPS_Const::STATE_HANDSHAKING) {
            $this->_checkHandshake($tsSelect);
        }
    }

    public function readyWrite($tsSelect) {
        $state = $this->_state; // to locals

        // if-tree optimization
        if ($state == StreamLoop_HTTPS_Const::STATE_READY) {
            $this->_active = false;
        } elseif ($state == StreamLoop_HTTPS_Const::STATE_CONNECTING) {
            // коннект установился, я готов к записи
            $stream = $this->stream;

            stream_context_set_option($stream, array(
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'peer_name' => $this->_host,
                    'allow_self_signed' => true,
                ],
            ));

            $this->_state = StreamLoop_HTTPS_Const::STATE_HANDSHAKING; // handshake starting
            // не ставим write, потому что во время handshaking всегда идет write и просто зайобка
            $this->_loop->updateHandlerFlags($this, true, false, true);

            // и сразу же проверяем его, вдруг подключился
            $this->_checkHandshake($tsSelect);
        } elseif ($state == StreamLoop_HTTPS_Const::STATE_HANDSHAKING) {
            $this->_checkHandshake($tsSelect);
        }
    }

    public function readyExcept($tsSelect) {
        if ($this->_checkEOF()) {
            return;
        }

        if ($this->_state == StreamLoop_HTTPS_Const::STATE_HANDSHAKING) {
            $this->_checkHandshake($tsSelect);
            return;
        }
    }

    public function readySelectTimeout($tsSelect) {
        // если прошел timeout - кидаем ошибку и отключаемся;
        // это касается любого типа timeout - request, connecting, handshaking.
        // потому что все равно соединению пизда

        // важно: readySelectTimeout не может вызваться если timeout не настал, поэтому никаких проверок на timeout'ы тут просто делать не надо.

        $this->disconnect();

        $this->_onError(
            $tsSelect,
            408,
            'timeout',
        );
    }

    private function _checkEOF() {
        if (feof($this->stream)) {
            // сначала отключаемся
            $this->disconnect();

            // затем кидаем ошибку (оно само переподключится если надо)
            $this->_onError(
                microtime(true),
                0, // http code 0
                'Connection closed by server', // ясное сообщение
            );

            return true;
        }
    }

    private function _checkHandshake($tsSelect) {
        $return = stream_socket_enable_crypto(
            $this->stream,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if ($return === true) {
            // я подключился
            $this->_reset(); // reset чтобы очистить все

            // бросам событие что я готов
            $this->_onReady($tsSelect);
        } elseif ($return === false) {
            //throw new StreamLoop_Exception("Failed to setup SSL");
            $this->disconnect();
            $this->_onError(
                microtime(true),
                0,
                'Failed to setup SSL'
            );

            return; // чтобы не лупиться в eof
        }

        $this->_checkEOF();
    }

    private function _reset() {
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
        $this->_state = StreamLoop_HTTPS_Const::STATE_READY; // in reset
        $this->_loop->updateHandlerFlags($this, false, false, false);
        $this->_loop->updateHandlerTimeoutTo($this, 0); // стереть таймер
    }

    public function getState() {
        return $this->_state;
    }

    private $_host, $_port, $_ip, $_bindIP, $_bindPort;
    private $_buffer = '';
    private $_headerArray = [];
    private $_statusCode = 0;
    private $_statusMessage = '';
    private $_active = false; // bool
    protected $_state = 0; // int, 0 is STATE_DISCONNECTED, by default disconnected // @todo protected это лажа
    private $_chunkExpected = null; // int|null, сколько байт данных ждем в текущем чанке
    private $_bodyDecoded = '';     // сюда складываем уже декодированное тело (без chunk-обвязки)

}