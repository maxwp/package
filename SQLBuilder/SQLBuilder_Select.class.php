<?php
class SQLBuilder_Select extends SQLBuilder_AQuery {


    public function make() {
        $table = $this->getTable();
        $fieldArray = $this->getFieldArray();
        $whereArray = $this->getWhereArray();

        if (!$table) {
            throw new SQLBuilder_Exception('Invalid table name');
        }

        if (!$fieldArray) {
            throw new SQLBuilder_Exception('Invalid fieldArray');
        }

        /*if (!$whereArray) {
            throw new SQLBuilder_Exception('Invalid whereArray');
        }*/

        $table = $this->getConnection()->escapeString($table);

        $wArray = [];
        foreach ($whereArray as $field => $value) {
            foreach ($value as $v) {
                $wArray[] = $field . ' '.$v->make($this->getConnection());
            }
        }

        $s = [];
        $s[] = "SELECT ".implode(', ', $fieldArray)." FROM `$table` WHERE ".implode(' AND ', $wArray);
        if ($this->_orderByString) {
            $s[] = "ORDER BY ".$this->_orderByString;
        }
        if ($this->_limitFrom && $this->_limitCount) {
            $s[] = "LIMIT ".$this->_limitFrom.', '.$this->_limitCount;
        } elseif ($this->_limitCount) {
            $s[] = "LIMIT ".$this->_limitCount;
        }
        return implode(' ', $s);
    }

    public function addField($field, $escapeField = true) {
        $value = $field;
        if ($escapeField && $field != '*') {
            $value = "`$value`";
        }
        $this->_fieldArray[$field] = $value;
    }

    public function removeField($field) {
        unset($this->_fieldArray[$field]);
    }

    public function getFieldArray() {
        return $this->_fieldArray;
    }

    public function addWhere($key, $value) {
        if (!$key) {
            throw new SQLBuilder_Exception('Key cannot be empty');
        }

        if (! ($value instanceof SQLBuilder_String)) {
            $value = new SQLBuilder_String('= ?', $value);
        }

        $this->_whereArray[$key][] = $value;
    }

    public function removeWhere($key) {
        unset($this->_whereArray[$key]);
    }

    public function getWhereArray() {
        return $this->_whereArray;
    }

    public function setOrderBy($orderByString) {
        $this->_orderByString = $orderByString;
    }

    public function setLimit($count, $from = 0) {
        $this->_limitCount = $count;
        $this->_limitFrom = $from;
    }

    private $_fieldArray = [];

    private $_whereArray = [];

    private $_orderByString;

    private $_limitFrom;

    private $_limitCount;

}