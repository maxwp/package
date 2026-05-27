<?php
abstract class StreamLoop_TCP_Abstract extends StreamLoop_Handler_Abstract {

    protected function _createAndConnectTCP() {
        # debug:start
        Cli::Print_n(__CLASS__." connecting to {$this->_host} ip={$this->_ip} port={$this->_port} bind={$this->_sourceIP}:{$this->_sourcePort}");
        # debug:end

        $ip = $this->_ip ?: $this->_host;

        // супер важно: надо создавать контекст без ssl-опций!
        $context = stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,  // no Nagle algorithm
                'bindto' => "{$this->_sourceIP}:{$this->_sourcePort}",
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

        // сразу 10 sec на connect .. ready
        $this->_timeoutTo = microtime(true) + 10;
        $this->_loop->updateHandler($this, false, true, true, $this->_timeoutTo);

        // Устанавливаем буфер до начала SSL
        $socket = new Connection_SocketStream($stream);
        //$socket->setBufferSizeRead(10 * 1024 * 1024);
        //$socket->setBufferSizeWrite(2 * 1024 * 1024);
        $socket->setKeepAlive();
        $socket->setQuickACK();

        stream_set_blocking($stream, false);

        // отключаем буферизацию php
        stream_set_read_buffer($stream, 0);
        stream_set_write_buffer($stream, 0);
    }

    protected function _updateDestinationHost($host) {
        if (Checker::CheckHostname($host)) {
            $this->_host = $host;
        } else {
            throw new StreamLoop_Exception("Invalid hostname $host");
        }
    }

    protected function _updateDestinationIP($ip = false) {
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
        $port = (int) $port;
        if ($port > 0) {
            $this->_port = $port;
        } else {
            throw new StreamLoop_Exception("Invalid port $port");
        }
    }

    protected function _updateSourceIP($ip = false) {
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
    protected $_timeoutTo = 0.0; // float

}