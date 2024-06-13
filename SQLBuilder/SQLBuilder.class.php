<?php

/**
 * Супер-топорный построитеть SQL-запросов.
 * Экономия времени при написании кода на экранировании и тд для простых запросов.
 *
 * @deprecated
 */
class SQLBuilder {

    public function insert($table, $fieldArray) {
        if (!$table) {
            throw new SQLBuilder_Exception('Invalid table name');
        }

        if (!$fieldArray) {
            throw new SQLBuilder_Exception('Invalid fieldArray');
        }

        $keyArray = [];
        $valueArray = [];
        foreach ($fieldArray as $key => $value) {
            $keyArray[] = "`".$this->_getConnection()->escapeString($key)."`";
            $valueArray[] = "'".$this->_getConnection()->escapeString($value)."'";
        }

        $table = $this->_getConnection()->escapeString($table);

        $s = "INSERT INTO `$table` (".implode(',', $keyArray).") VALUES (".implode(',', $valueArray).")";
        return $s;
    }

    public function delete($table, $whereArray, $limit = false) {
        if (!$table) {
            throw new SQLBuilder_Exception('Invalid table name');
        }

        if (!$whereArray) {
            throw new SQLBuilder_Exception('Invalid whereArray');
        }

        $filterArray = [];
        $filterArray[] = '1=1';
        foreach ($whereArray as $key => $value) {
            $filterArray[] = "`".$this->_getConnection()->escapeString($key)."`='".$this->_getConnection()->escapeString($value)."'";
        }

        $table = $this->_getConnection()->escapeString($table);

        $s = "DELETE FROM `$table` WHERE ".implode(' AND ', $filterArray).' ';
        if ($limit > 0) {
            $s .= "LIMIT $limit";
        }
        return $s;
    }

    public function update($table, $fieldArray, $whereArray, $limit = false) {
        if (!$table) {
            throw new SQLBuilder_Exception('Invalid table name');
        }

        if (!$fieldArray) {
            throw new SQLBuilder_Exception('Invalid fieldArray');
        }

        if (!$whereArray) {
            throw new SQLBuilder_Exception('Invalid whereArray');
        }

        $setArray = [];
        foreach ($fieldArray as $key => $value) {
            $setArray[] = "`".$this->_getConnection()->escapeString($key)."`='".$this->_getConnection()->escapeString($value)."'";
        }

        $filterArray = [];
        $filterArray[] = '1=1';
        foreach ($whereArray as $key => $value) {
            $filterArray[] = "`".$this->_getConnection()->escapeString($key)."`='".$this->_getConnection()->escapeString($value)."'";
        }

        $table = $this->_getConnection()->escapeString($table);

        $s = "UPDATE `$table` SET ".implode(', ', $setArray)." WHERE ".implode(' AND ', $filterArray).' ';
        if ($limit > 0) {
            $s .= "LIMIT $limit";
        }
        return $s;
    }

    public function select($table, $fieldArray, $whereArray, $orderBy = false, $orderType = false, $limit = false) {
        if (!$table) {
            throw new SQLBuilder_Exception('Invalid table name');
        }

        if (!$fieldArray) {
            throw new SQLBuilder_Exception('Invalid fieldArray');
        }

        if (!$whereArray) {
            throw new SQLBuilder_Exception('Invalid whereArray');
        }

        $getArray = [];
        if ($fieldArray == '*') {
            $getArray[] = '*';
        } else {
            foreach ($fieldArray as $key) {
                $getArray[] = "`".$this->_getConnection()->escapeString($key)."`";
            }
        }

        $filterArray = [];
        $filterArray[] = '1=1';
        foreach ($whereArray as $key => $value) {
            $filterArray[] = "`".$this->_getConnection()->escapeString($key)."`='".$this->_getConnection()->escapeString($value)."'";
        }

        $table = $this->_getConnection()->escapeString($table);

        $s = "SELECT ".implode(', ', $getArray)." FROM `$table` WHERE ".implode(' AND ', $filterArray).' ';
        if ($orderBy && $orderType) {
            $s .= "ORDER BY $orderBy $orderType";
        }
        if ($limit > 0) {
            $s .= "LIMIT $limit";
        }
        return $s;
    }

    private function __construct(ConnectionManager_IDatabaseAdapter $connection) {
        $this->_connection = $connection;
    }

    /**
     * @return ConnectionManager_IDatabaseAdapter
     */
    private function _getConnection() {
        return $this->_connection;
    }

    /**
     * @return SQLBuilder
     */
    public static function Get(ConnectionManager_IDatabaseAdapter $connection) {
        return new self($connection);
    }

    private $_connection;

}