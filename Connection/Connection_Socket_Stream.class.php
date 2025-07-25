<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class Connection_SocketStream extends Connection_Socket_Abstract {

    public function __construct($stream) {
        parent::__construct(socket_import_stream($stream));
    }

    public function connect() {
        // nothing for stream
    }

}