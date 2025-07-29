<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class Connection_SocketUDPConnected extends Connection_SocketUDP {

    public function __construct($host, int $port) {
        parent::__construct();
        $this->_host = $host;
        $this->_port = $port;
    }

    public function connect() {
        socket_connect($this->_socket, $this->_host, $this->_port);
    }

    public function write($message, $messageSize) {
        socket_write(
            $this->_socket,
            $message,
            $messageSize,
        );
    }

    // @todo override read() to socket_recv()

    private $_host;
    private $_port;

}