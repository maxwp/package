<?php
abstract class SQLBuilder_AQuery {

    public function __construct(ConnectionManager_IDatabaseAdapter $connection, $table) {
        $this->_connection = $connection;
        $this->setTable($table);
    }

    public function setTable($table) {
        $this->_table = $table;
    }

    public function getTable() {
        return $this->_table;
    }

    /**
     * @return ConnectionManager_IDatabaseAdapter
     */
    public function getConnection() {
        return $this->_connection;
    }

    abstract public function make();

    public function execute() {
        return $this->getConnection()->query($this->make());
    }

    private $_connection;

    private $_table;

}