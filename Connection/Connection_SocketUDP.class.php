<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class Connection_SocketUDP implements Connection_IConnection {

    public function connect() {
        // @todo лучше вынести в конструктор? Потому что у UDP нет коннекта и метод connect выглядит не красиво

        $this->_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        // @todo
        // Использование опции IP_MTU_DISCOVER с режимом IP_PMTUDISC_WANT позволяет сокету попытаться определить
        // максимальный размер пакета (MTU) по пути к получателю без фрагментации.
        //socket_set_option($this->_socket, IPPROTO_IP, IP_MTU_DISCOVER, IP_PMTUDISC_WANT);
    }

    public function setNonBlocking() {
        socket_set_nonblock($this->_socket);
    }

    public function setSocketOption($type, $value) {
        if (!socket_set_option($this->_socket, SOL_SOCKET, $type, $value)) {
            throw new Connection_Exception("Socket option error type=$type");
        }
    }

    public function setTimeoutRead($timeoutSec, $timeoutUsec) {
        // @todo стоит ли переделывать на один параметр?
        // @todo а нафига сделали два?
        $timeoutSec = ['sec' => $timeoutSec, 'usec' => $timeoutUsec];
        $this->setSocketOption(SO_RCVTIMEO, $timeoutSec);
    }

    public function setBufferSizeRead($size) {
        $this->setSocketOption(SO_RCVBUF, $size);
        $this->_checkBufferSize(SO_RCVBUF, $size);
    }

    public function setBufferSizeWrite($size) {
        $this->setSocketOption(SO_SNDBUF, $size);
        $this->_checkBufferSize(SO_SNDBUF, $size);
    }

    private function _checkBufferSize($side, $size) {
        // При установке опции SO_RCVBUF/SO_SNDBUF в Linux значение, которое вы указываете, автоматически удваивается для учёта
        // накладных расходов ядра (служебных структур, буферов для управления данными и т.п.)
        // Это стандартное поведение, задокументированное в описании сокетов в Linux,
        // и оно работает как для TCP, так и для UDP.
        if (socket_get_option($this->_socket, SOL_SOCKET, $side) < $size * 2) {
            throw new Connection_Exception("$side size error");
        }
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

    public function write($message, $messageSize, $host, $port) {
        return socket_sendto($this->_socket, $message, $messageSize, MSG_DONTWAIT, $host, $port);
    }

    /**
     * @param int $port
     * @param Connection_Socket_IReceiver $receiver
     * @param int $length
     */
    public function read($port, Connection_Socket_IReceiver $receiver, $length = 1024, $drainReverse = false) {
        $result = socket_bind($this->_socket, '0.0.0.0', $port);
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

        // to locals
        $socket = $this->_socket;

        while (1) {
            // @todo reverse drain flag here

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

    private $_socket;

}