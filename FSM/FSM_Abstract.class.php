<?php
abstract class FSM_Abstract {

    protected function _updateState($state) {
        $this->_state = $state;
    }

    public function getState() {
        return $this->_state;
    }

    private $_state;

}