<?php
class ConnectionManager_SocketUDP implements ConnectionManager_IConnection {

    public function connect() {
        $this->_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

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
        socket_close($this->_socket);
    }

    public function getLinkID() {
        return $this->_socket;
    }

    public function write($message, $host, $port) {
        return socket_sendto($this->_socket, $message, strlen($message), 0, $host, $port);
    }

    public function read($port, $callback, $length = 1024) {
        $result = socket_bind($this->_socket, '0.0.0.0', $port);
        if ($result === false) {
            $message = socket_strerror(socket_last_error($this->_socket));
            $this->disconnect();
            throw new ConnectionManager_Exception($message);
        }

        while (1) {
            $buf = '';
            $fromIP = '';
            $fromPort = 0;

            $bytes = socket_recvfrom($this->_socket, $buf, $length, 0, $fromIP, $portPort);
            if ($bytes === false) {
                $message = socket_strerror(socket_last_error($this->_socket)) . "\n";
                $this->disconnect();
                throw new ConnectionManager_Exception($message);
                break;
            }

            // вызываем callback
            $callback($buf, $fromIP, $fromPort);
        }
    }

    private $_socket = null;

}