<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class Connection_SocketUDS implements Connection_IConnection {

    public function __construct($socketFile) {
        $this->_socketFile = $socketFile;
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
     * @param Connection_Socket_IReceiver $receiver
     * @param int $length
     */
    public function read(Connection_Socket_IReceiver $receiver, $length = 1024) {
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

        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

        while (1) {
            $bytes = socket_recvfrom($this->_socket, $buffer, $length, 0, $fromAddress, $fromPort);

            // меряем время сразу после получения
            $ts = microtime(true);

            // тут более правильно проверять на === false,
            // но в реальности пустой дата-граммы быть не может
            // и чтобы не делать внизу проверку на if ($buffer) с типизацией string $buffer to bool
            // я прямо тут проверяю не пустые ли байты, тем более что чаще всего $bytes это int
            if ($bytes <= 0) {
                $message = socket_strerror(socket_last_error($this->_socket)); // message надо получить ДО disconnect, бо поменяется
                $this->disconnect();
                throw new Connection_Exception($message);
            }

            // я сюда не дойду если $buffer пустой
            if ($receiver->onReceive($ts, $buffer, $fromAddress, $fromPort)) {
                // если есть какой-то результат - на выход
                break;
            }
        }
    }

    private $_socket;

    private $_socketFile;

}