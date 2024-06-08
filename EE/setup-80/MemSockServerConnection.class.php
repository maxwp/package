<?php
class MemSockServerConnection {
    /*!	@var		socket
        @abstract	resource - The client's socket resource, for sending and receiving data with.
     */
    protected $socket;

    /*!	@var		server_clients_index
        @abstract	int - The index of this client in the SocketServer's client array.
     */
    public $server_clients_index;

    public $client_ip;

    //public $buffer = '';

    /*!	@function	__construct
        @param		resource- The resource of the socket the client is connecting by, generally the master socket.
        @param		int	- The Index in the Server's client array.
        @result		void
     */
    public function __construct(&$master_socket, $i) {
        $this->server_clients_index = $i;
        $this->socket = socket_accept($master_socket) or die("Failed to Accept socket connection $i");
        //SocketServer::debug("New Client Connected");

        // client ip
        //socket_getpeername($master_socket,$this->client_ip);
    }

    public function write($string, $crlf = "\r\n") {
        //SocketServer::debug("<-- {$string}");
        if ($crlf) {
            $string = "{$string}{$crlf}";
        }

        return socket_write($this->socket, $string, strlen($string));
    }

    public function destroy() {
        socket_close($this->socket);
    }

    function &__get($name)
    {
        return $this->{$name};
    }

    function __isset($name)
    {
        return isset($this->{$name});
    }
}