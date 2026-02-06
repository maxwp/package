<?php
abstract class StreamLoop_TCP_Abstract extends StreamLoop_Handler_Abstract {

    protected function _updateDestinationHost($host) {
        if (Checker::CheckHostname($host)) {
            $this->_host = $host;
        } else {
            throw new StreamLoop_Exception("Invalid hostname $host");
        }
    }

    protected function _updateDestinationIP($ip = false) {
        if ($ip) {
            if (Checker::CheckIP($ip)) {
                $this->_ip = $ip;
            } else {
                throw new StreamLoop_Exception("Invalid IP $ip");
            }
        } else {
            $this->_ip = false;
        }
    }

    protected function _updateDestinationPort($port) {
        $port = (int) $port;
        if ($port > 0) {
            $this->_port = $port;
        } else {
            throw new StreamLoop_Exception("Invalid port $port");
        }
    }

    protected function _updateSourceIP($ip = false) {
        if ($ip) {
            if (Checker::CheckIP($ip)) {
                $this->_sourceIP = $ip;
            } else {
                throw new StreamLoop_Exception("Invalid IP $ip");
            }
        } else {
            $this->_sourceIP = '0.0.0.0';
        }
    }

    protected function _updateSourcePort($port = 0) {
        $port = (int) $port;
        if ($port >= 0) {
            $this->_sourcePort = $port;
        } else {
            throw new StreamLoop_Exception("Invalid port $port");
        }
    }

    protected $_host; // string
    protected $_port; // int
    protected $_ip = false; // string
    protected $_sourceIP = '0.0.0.0'; // string, any ip by default
    protected $_sourcePort = 0; // int, any port by default

}