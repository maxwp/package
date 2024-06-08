<?php
/*!	@class		SocketServer
    @author		Navarr Barnier
    @abstract 	A Framework for creating a multi-client server using the PHP language.
 */
class MemSockServer {

    protected $_ip;

    protected $_port;

    /*!	@var		hooks
        @abstract	Array - a dictionary of hooks and the callbacks attached to them.
     */
    protected $_hookArray;

    /*!	@var		master_socket
        @abstract	resource - The master socket used by the server.
     */
    protected $_master_socket;

    /*!	@var		max_clients
        @abstract	unsigned int - The maximum number of clients allowed to connect.
     */
    public $_max_clients = 10000;

    /*!	@var		max_read
        @abstract	unsigned int - The maximum number of bytes to read from a socket at a single time.
     */
    public $_max_read = 1024*10*3;

    /*!	@var		clients
        @abstract	Array - an array of connected clients.
     */
    private $_connectionArray;

    /*!	@function	__construct
        @abstract	Creates the socket and starts listening to it.
        @param		string	- IP Address to bind to, NULL for default.
        @param		int	- Port to bind to
        @result		void
     */
    public function __construct($ip, $port) {
        set_time_limit(0);
        $this->_hookArray = array();

        $this->_ip = $ip;
        $this->_port = $port;

        $this->_master_socket = socket_create(AF_INET, SOCK_STREAM, 0);
        socket_set_option($this->_master_socket, SOL_SOCKET, SO_REUSEADDR, 1);
        //socket_set_option($this->_master_socket, SOL_SOCKET, TCP_NODELAY, 1);
        socket_bind($this->_master_socket, $ip, $port);
        socket_getsockname($this->_master_socket,$ip,$port);
        socket_listen($this->_master_socket);
    }

    public function clearBuffers() {
        foreach ($this->_connectionArray as $connection) {
            $connection->buffer = '';
        }
    }

    public function getConnectionCount() {
        return count($this->_connectionArray);
    }

    /*!	@function	hook
        @abstract	Adds a function to be called whenever a certain action happens.  Can be extended in your implementation.
        @param		string	- Command
        @param		callback- Function to Call.
        @see		unhook
        @see		trigger_hooks
        @result		void
     */
    public function hook($command,$function) {
        $command = strtoupper($command);
        if (!isset($this->_hookArray[$command])) {
            $this->_hookArray[$command] = array();
        }

        $k = array_search($function,$this->_hookArray[$command]);
        if($k === false) {
            $this->_hookArray[$command][] = $function;
        }
    }

    /*!	@function	unhook
        @abstract	Deletes a function from the call list for a certain action.  Can be extended in your implementation.
        @param		string	- Command
        @param		callback- Function to Delete from Call List
        @see		hook
        @see		trigger_hooks
        @result		void
     */
    public function unhook($command = NULL, $function) {
        $command = strtoupper($command);
        if($command !== NULL)
        {
            $k = array_search($function,$this->_hookArray[$command]);
            if($k !== FALSE)
            {
                unset($this->_hookArray[$command][$k]);
            }
        } else {
            $k = array_search($this->user_funcs,$function);
            if($k !== FALSE)
            {
                unset($this->user_funcs[$k]);
            }
        }
    }

    /*!	@function	loop_once
        @abstract	Runs the class's actions once.
        @discussion	Should only be used if you want to run additional checks during server operation.  Otherwise, use infinite_loop()
        @param		void
        @see		infinite_loop
        @result 	bool	- True
    */
    public function loop_once() {
        // Setup Clients Listen Socket For Reading
        $read[0] = $this->_master_socket;
        for($i = 0; $i < $this->_max_clients; $i++)  {
            if (isset($this->_connectionArray[$i])) {
                $read[$i + 1] = $this->_connectionArray[$i]->socket;
            }
        }

        // Set up a blocking call to socket_select
        $write = null;
        $except = null;
        if (socket_select($read,$write, $except, $tv_sec = 5) < 1)  {
            return true;
        }

        // Handle new Connections
        if (in_array($this->_master_socket, $read))  {
            for($i = 0; $i < $this->_max_clients; $i++)  {
                if (empty($this->_connectionArray[$i]))  {
                    $this->_connectionArray[$i] = new MemSockServerConnection($this->_master_socket, $i);
                    $this->trigger_hooks("CONNECT",$this->_connectionArray[$i],"");
                    break;
                } elseif($i == ($this->_max_clients-1)) {
                    print "Too many clients... :(\n";
                }
            }
        }

        // Handle Input for each client
        for($i = 0; $i < $this->_max_clients; $i++) {
            if (isset($this->_connectionArray[$i]))  {
                if (in_array($this->_connectionArray[$i]->socket, $read))  {
                    $input = socket_read($this->_connectionArray[$i]->socket, $this->_max_read);
                    if ($input == null)  {
                        $this->disconnect($i);
                    }  else  {
                        //SocketServer::debug("{$i}@{$this->_connectionArray[$i]->ip} --> {$input}");
                        $this->trigger_hooks("INPUT",$this->_connectionArray[$i],$input);
                    }
                }
            }
        }
        return true;
    }

    /*!	@function	disconnect
        @abstract	Disconnects a client from the server.
        @param		int	- Index of the client to disconnect.
        @param		string	- Message to send to the hooks
        @result		void
    */
    public function disconnect($client_index, $message = "") {
        $i = $client_index;
        //SocketServer::debug("Client {$i} from {$this->_connectionArray[$i]->ip} Disconnecting");
        $this->trigger_hooks("DISCONNECT",$this->_connectionArray[$i],$message);
        $this->_connectionArray[$i]->destroy();
        unset($this->_connectionArray[$i]);
    }

    /*!	@function	trigger_hooks
        @abstract	Triggers Hooks for a certain command.
        @param		string	- Command who's hooks you want to trigger.
        @param		object	- The client who activated this command.
        @param		string	- The input from the client, or a message to be sent to the hooks.
        @result		void
    */
    public function trigger_hooks($command,&$client,$input) {
        if (isset($this->_hookArray[$command]))  {
            foreach($this->_hookArray[$command] as $function) {
                //SocketServer::debug("Triggering Hook '{$function}' for '{$command}'");
                $continue = call_user_func($function,$this,$client,$input);
                if ($continue === false) {
                    break;
                }
            }
        }
    }

    /*!	@function	infinite_loop
        @abstract	Runs the server code until the server is shut down.
        @see		loop_once
        @param		void
        @result		void
    */
    public function infinite_loop() {
        $test = true;
        do {
            $test = $this->loop_once();
        }
        while($test);
    }

    /*!	@function	__get
        @abstract	Magic Method used for allowing the reading of protected variables.
        @discussion	You never need to use this method, simply calling $server->variable works because of this method's existence.
        @param		string	- Variable to retrieve
        @result		mixed	- Returns the reference to the variable called.
    */
    function &__get($name) {
        return $this->{$name};
    }
}
