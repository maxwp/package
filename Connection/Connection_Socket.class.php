<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Обертка над socket resource
 */
class Connection_Socket {

    // @todo
    // Использование опции IP_MTU_DISCOVER с режимом IP_PMTUDISC_WANT позволяет сокету попытаться определить
    // максимальный размер пакета (MTU) по пути к получателю без фрагментации.
    //socket_set_option($this->_socket, IPPROTO_IP, IP_MTU_DISCOVER, IP_PMTUDISC_WANT);

    // @todo busy poll, busy read support

    // @todo а может лучше наследование SocketUDP over Socket?
    // потому что у меня и так есть IConnection->getLink() ?

    /**
     * @param $stream
     * @return self
     */
    public static function CreateFromStream($stream) {
        return new self(socket_import_stream($stream));
    }

    /**
     * @param $stream
     * @return self
     */
    public static function CreateSocketUDP() {
        return new self(socket_create(AF_INET, SOCK_DGRAM, SOL_UDP));
    }

    /**
     * @param $stream
     * @return self
     */
    public static function CreateSocketUDS() {
        return new self(socket_create(AF_UNIX, SOCK_DGRAM, 0));
    }

    public static function CreateSocketTCP() {
        return new self(socket_create(AF_INET, SOCK_STREAM, SOL_TCP));
    }

    public function setNonBlocking() {
        socket_set_nonblock($this->socketResource);
    }

    public function setBlocking() {
        socket_set_block($this->socketResource);
    }

    public function setSocketOption($type, $value) {
        if (!socket_set_option($this->socketResource, SOL_SOCKET, $type, $value)) {
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

    public function setReuseAddr($mode = 0) {
        $this->setSocketOption(SO_REUSEADDR, $mode);
    }

    private function _checkBufferSize($side, $size) {
        // При установке опции SO_RCVBUF/SO_SNDBUF в Linux значение, которое вы указываете, автоматически удваивается для учёта
        // накладных расходов ядра (служебных структур, буферов для управления данными и т.п.)
        // Это стандартное поведение, задокументированное в описании сокетов в Linux,
        // и оно работает как для TCP, так и для UDP.
        if (socket_get_option($this->socketResource, SOL_SOCKET, $side) < $size * 2) {
            throw new Connection_Exception("$side size error");
        }
    }

    public function __construct($socket) {
        $this->socketResource = $socket;
    }

    /**
     * @return resource
     */
    public function getSocketResource() {
        return $this->socketResource;
    }

    public $socketResource; // @todo private

}