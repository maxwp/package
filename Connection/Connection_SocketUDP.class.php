<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class Connection_SocketUDP extends Connection_Socket_Abstract {

    public function __construct() {
        parent::__construct(socket_create(AF_INET, SOCK_DGRAM, SOL_UDP));
    }

    public function connect() {
        // nothing for UDP
    }

    public function writeTo($message, $messageSize, $host, $port) {
        return socket_sendto(
            $this->_socket,
            $message,
            $messageSize,
            MSG_DONTWAIT,
            $host,
            $port
        );
    }

    /**
     * @param int $port
     * @param Connection_Socket_IReceiver $receiver
     * @param int $length
     */
    public function read($port, Connection_Socket_IReceiver $receiver, $length = 1024) {
        // to locals
        $socket = $this->_socket;

        $result = socket_bind($socket, '0.0.0.0', $port);
        if ($result === false) {
            $message = socket_strerror(socket_last_error($this->_socket));
            $this->disconnect();
            throw new Connection_Exception($message.' port='.$port);
        }

        // инициализация переменных ДО цикла,
        // все равно socket_recvfrom() их перетирает, ему нужен только указатель
        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

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

            if ($bytes > 0) {
                // я сюда не дойду если $buffer пустой
                if ($receiver->onReceive($ts, $buffer, $fromAddress, $fromPort)) {
                    // если есть какой-то результат - на выход
                    break;
                }
            } else {
                // тут более правильно проверять на === false,
                // но в реальности пустой дата-граммы быть не может
                // и чтобы не делать внизу проверку на if ($buffer) с типизацией string $buffer to bool
                // я прямо тут проверяю не пустые ли байты, тем более что чаще всего $bytes это int
                $message = $this->_getSocketError(); // message надо получить ДО disconnect, бо поменяется
                $this->disconnect();
                throw new Connection_Exception($message);
            }
        }
    }

}