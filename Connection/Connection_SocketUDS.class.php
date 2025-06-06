<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class Connection_SocketUDS implements Connection_IConnection {

    public function __construct($socketFile, Connection_Socket_IReceiver $receiver) {
        $this->_socketFile = $socketFile;
        $this->_receiver = $receiver;
    }

    public function connect() {
        $this->_socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
    }

    public function disconnect() {
        if ($this->_socket) {
            socket_close($this->_socket);
        }
    }

    public function getLink() {
        if (!$this->_socket) {
            $this->connect();
        }
        return $this->_socket;
    }

    public function write($message, $messageSize) {
        return socket_sendto($this->_socket, $message, $messageSize, MSG_DONTWAIT, $this->_socketFile);
    }

    /**
     * @param callable(string $buf, string $fromIP, int $fromPort): void $callback
     * @param int $length
     */
    public function read(callable $callback, $length = 1024) {
        // всегда косим файл перед bind-ом
        if (file_exists($this->_socketFile)) {
            unlink($this->_socketFile);
        }

        $result = socket_bind($this->_socket, $this->_socketFile);
        if ($result === false) {
            $message = socket_strerror(socket_last_error($this->_socket));
            $this->disconnect();
            throw new Connection_Exception($message.' sockfile='.$this->_socketFile);
        }

        $buf = '';
        $fromAddress = '';
        $fromPort = 0;

        while (1) {
            $bytes = socket_recvfrom($this->_socket, $buf, $length, 0, $fromAddress, $fromPort);
            $ts = microtime(true);

            if ($bytes === false) {
                $message = socket_strerror(socket_last_error($this->_socket)); // message надо получить ДО disconnect, бо поменяется
                $this->disconnect();
                throw new Connection_Exception($message);
            }

            $this->_receiver->onReceive($ts, $buf, $fromAddress, $fromPort);
        }
    }

    private $_socket;

    private $_socketFile;

    private Connection_Socket_IReceiver $_receiver;

}