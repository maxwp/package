<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * UDPConnected на отправку быстрее на 16% - я проверял это на hot-cold benchmark
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
        if (socket_write(
            $this->_socket,
            $message,
            $messageSize,
        ) != $messageSize) {
            // reconnect
            socket_connect($this->_socket, $this->_host, $this->_port);

            // повторная отправка
            return socket_write(
                $this->_socket,
                $message,
                $messageSize,
            ) == $messageSize;
        }

        // я отправил
        return true;
    }

    // @todo override read() to socket_recv()

    private $_host;
    private $_port;

}