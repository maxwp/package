<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class Connection_SocketUDS extends Connection_Socket_Abstract {

    /*
     * В ядре Linux для каждого UDS-сокета с типом datagram есть ограничение длины очереди (max_dgram_qlen),
     * которое по умолчанию равно 10 датаграмм. При поступлении очередной датаграммы,
     * если в очереди уже лежит max_dgram_qlen сообщений, новая отбрасывается.
     * sysctl net.unix.max_dgram_qlen=128
     *
     * UDS SOCK_DGRAM не гарантирует доставку всех сообщений: на него накладываются некоторые системные лимиты (очередь в ядре, максимальный размер датаграммы).
     * Для надёжной доставки можно перейти на SOCK_SEQPACKET (поддержка с Linux 2.6.4) или организовать подтверждение на прикладном уровне.
     */

    public function __construct($socketFile) {
        $this->_socketFile = $socketFile;

        parent::__construct(socket_create(AF_UNIX, SOCK_DGRAM, 0));
    }

    public function connect() {
        // nothing for UDS
    }

    public function write($message, $messageSize) {
        return socket_sendto(
            $this->_socket,
            $message,
            $messageSize,
            MSG_DONTWAIT,
            $this->_socketFile
        );
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

        $socket = $this->_socket;

        $result = socket_bind($socket, $this->_socketFile);
        if ($result === false) {
            $message = $this->_getSocketError();
            $this->disconnect();
            throw new Connection_Exception($message.' sockfile='.$this->_socketFile);
        }

        $buffer = '';
        $fromAddress = '';
        $fromPort = 0;

        while (1) {
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

    private $_socketFile;

}