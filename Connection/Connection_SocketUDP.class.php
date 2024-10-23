<?php
class Connection_SocketUDP implements Connection_IConnection {

    public function connect() {
        $this->_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        //socket_set_option($this->_socket, IPPROTO_IP, IP_MTU_DISCOVER, IP_PMTUDISC_WANT);
        //socket_set_option($this->_socket, SOL_SOCKET, SO_REUSEADDR, 1);

        $this->setBufferSizeRead(50 * 1024 * 1024);
        $this->setBufferSizeWrite(50 * 1024 * 1024);
    }

    public function setNonBlocking() {
        socket_set_nonblock($this->_socket);
    }

    public function setBufferSizeRead($size) {
        socket_set_option($this->_socket, SOL_SOCKET, SO_RCVBUF, $size);
    }

    public function setBufferSizeWrite($size) {
        socket_set_option($this->_socket, SOL_SOCKET, SO_SNDBUF, $size);
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
        return socket_sendto($this->_socket, $message, $messageSize, 0, $host, $port);
    }

    /**
     * @param int $port
     * @param callable(string $buf, string $fromIP, int $fromPort): void $callback
     * @param int $length
     */
    public function read($port, callable $callback, $length = 1024) {
        $result = socket_bind($this->_socket, '0.0.0.0', $port);
        if ($result === false) {
            $message = socket_strerror(socket_last_error($this->_socket));
            $this->disconnect();
            throw new Connection_Exception($message.' port='.$port);
        }

        while (1) {
            $buf = '';
            $fromIP = '';
            $fromPort = 0;

            $bytes = socket_recvfrom($this->_socket, $buf, $length, 0, $fromIP, $fromPort);
            if ($bytes === false) {
                $message = socket_strerror(socket_last_error($this->_socket)) . "\n";
                $this->disconnect();
                throw new Connection_Exception($message);
            }

            // @todo возможно callback переделать на interface
            // вызываем callback
            $callback($buf, $fromIP, $fromPort);
        }
    }

    private $_socket;

}