<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class Connection_SocketUDP implements Connection_IConnection {

    public function __construct() {
        $this->socket = Connection_Socket::CreateSocketUDP();
        $this->_socketResource = $this->socket->socketResource;
    }

    public function connect() {
        // nothing for UDP
    }

    public function disconnect() {
        if ($this->_socketResource) {
            socket_close($this->_socketResource);
        }
    }

    public function getLink() {
        return $this->_socketResource;
    }

    public function write($message, $messageSize, $host, $port) {
        return socket_sendto($this->_socketResource, $message, $messageSize, MSG_DONTWAIT, $host, $port);
    }

    /**
     * @param int $port
     * @param Connection_Socket_IReceiver $receiver
     * @param int $length
     */
    public function read($port, Connection_Socket_IReceiver $receiver, $length = 1024, $drainReverse = false) {
        $result = socket_bind($this->_socketResource, '0.0.0.0', $port);
        if ($result === false) {
            $message = socket_strerror(socket_last_error($this->_socketResource));
            $this->disconnect();
            throw new Connection_Exception($message.' port='.$port);
        }

        // инициализация переменных ДО цикла,
        // все равно socket_recvfrom() их перетирает, ему нужен только указатель
        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

        // to locals
        $socket = $this->_socketResource;

        while (1) {
            // читаем в блок режиме
            $bytes = socket_recvfrom(
                $socket,
                $buffer,
                $length,
                0,
                $fromAddress,
                $fromPort
            );

            // меряем время сразу после получения
            $ts = microtime(true);

            // тут более правильно проверять на === false,
            // но в реальности пустой дата-граммы быть не может
            // и чтобы не делать внизу проверку на if ($buffer) с типизацией string $buffer to bool
            // я прямо тут проверяю не пустые ли байты, тем более что чаще всего $bytes это int
            if ($bytes <= 0) {
                $message = socket_strerror(socket_last_error($socket)); // message надо получить ДО disconnect, бо поменяется
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

    public readonly Connection_Socket $socket;
    private $_socketResource;

}