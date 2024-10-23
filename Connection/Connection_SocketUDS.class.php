<?php
class Connection_SocketUDS implements Connection_IConnection {

    public function connect() {
        // @todo шо делать с sock file?

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

    public function write($message, $messageSize, $host, $port) {
        return socket_sendto($this->_socket, $message, $messageSize, 0, $host, $port);
    }

    /**
     * @param string $sockFile
     * @param callable(string $buf, string $fromIP, int $fromPort): void $callback
     * @param int $length
     */
    public function read($sockFile, callable $callback, $length = 1024) {
        // @todo ???
        if (file_exists($sockFile)) {
            unlink($sockFile);
        }

        $result = socket_bind($this->_socket, $sockFile);
        if ($result === false) {
            $message = socket_strerror(socket_last_error($this->_socket));
            $this->disconnect();
            throw new Connection_Exception($message.' sockfile='.$sockFile);
        }

        while (1) {
            $buf = '';
            $from = '';

            $bytes = socket_recvfrom($this->_socket, $buf, $length, 0, $from);
            if ($bytes === false) {
                $message = socket_strerror(socket_last_error($this->_socket)) . "\n";
                $this->disconnect();
                throw new Connection_Exception($message);
            }

            // @todo возможно callback переделать на interface
            // вызываем callback
            $callback($buf, $from);
        }
    }

    private $_socket;

}