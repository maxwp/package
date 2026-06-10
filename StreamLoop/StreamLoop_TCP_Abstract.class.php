<?php
abstract class StreamLoop_TCP_Abstract extends StreamLoop_Handler_Abstract {

    protected function _createAndConnectTCP() {
        # debug:start
        Cli::Print_n(__CLASS__." connecting to {$this->_host} ip={$this->_ip} port={$this->_port} bind={$this->_sourceIP}:{$this->_sourcePort}");
        # debug:end

        $stream = stream_socket_client(
            'tcp://'.($this->_ip ?: $this->_host).':'.$this->_port,
            $errno,
            $errstr,
            0, // timeout = 0, чтобы мгновенно вернулось
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT,
            stream_context_create([
                'socket' => [ // супер важно: надо создавать контекст без ssl-опций!
                    'tcp_nodelay' => true,  // no Nagle algorithm
                    'bindto' => "{$this->_sourceIP}:{$this->_sourcePort}",
                ],
            ]),
        );

        if ($stream) {
            $this->stream = $stream;
            $this->streamID = (int) $stream;

            // сразу 10 sec на connect .. ready
            $this->_loop->registerHandler($this); // 1st register (for connecting)
            $this->_loop->updateHandlerFlags($this, false, true);
            $this->_loop->updateStreamTimeout($this->streamID, microtime(true) + 10);

            // Устанавливаем буфер до начала SSL
            $socket = new Connection_SocketStream($stream);
            $socket->setBufferSizeRead(2 * 1024 * 1024);
            $socket->setBufferSizeWrite(2 * 1024 * 1024);
            $socket->setKeepAlive();
            $socket->setQuickACK();

            stream_set_blocking($stream, false);

            // отключаем буферизацию php
            stream_set_read_buffer($stream, 0);
            stream_set_write_buffer($stream, 0);
        } else {
            // критическая ошибка — завершаем
            throw new StreamLoop_Exception("TCP connect failed immediately: $errstr ($errno)");
        }
    }

    protected function _updateDestinationHost($host) {
        // @todo if наружу
        if (Checker::CheckHostname($host)) {
            $this->_host = $host;
        } else {
            throw new StreamLoop_Exception("Invalid hostname $host");
        }
    }

    protected function _updateDestinationIP($ip = false) {
        // @todo if наружу
        if ($ip) {
            if (Checker::CheckIP($ip)) {
                $this->_ip = $ip;
            } else {
                throw new StreamLoop_Exception("Invalid IP $ip");
            }
        } else {
            $this->_ip = false;
        }
    }

    protected function _updateDestinationPort($port) {
        // @todo if наружу
        $port = (int) $port;
        if ($port > 0) {
            $this->_port = $port;
        } else {
            throw new StreamLoop_Exception("Invalid port $port");
        }
    }

    protected function _updateSourceIP($ip = false) {
        // @todo if наружу
        if ($ip) {
            if (Checker::CheckIP($ip)) {
                $this->_sourceIP = $ip;
            } else {
                throw new StreamLoop_Exception("Invalid IP $ip");
            }
        } else {
            $this->_sourceIP = '0.0.0.0';
        }
    }

    protected function _updateSourcePort($port = 0) {
        // @todo if наружу
        $port = (int) $port;
        if ($port >= 0) {
            $this->_sourcePort = $port;
        } else {
            throw new StreamLoop_Exception("Invalid port $port");
        }
    }

    protected $_host; // string
    protected $_port; // int
    protected $_ip = false; // string
    protected $_sourceIP = '0.0.0.0'; // string, any ip by default
    protected $_sourcePort = 0; // int, any port by default

}