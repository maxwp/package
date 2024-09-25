<?php
class SQLBuilder_String {

    public function __construct($string, $param) {
        // @todo как подставлять дохера параметров?
        // посмотреть PDO
        $this->_string = $string;
        $this->_param = $param;
    }

    /**
     * @return string
     */
    public function make(Connection_IDatabaseAdapter $connection) {
        return str_replace('?', "'$this->_param'", $connection->escapeString($this->_string));
    }

    private $_string;
    private $_param;

}