<?php
trait FSM_Trait {

    private function _updateState($state) {
        $this->_state = $state;
    }

    private function _updateStateCallback($state, $callback) {
        $this->_state = $state;
        $callback();
    }

    public function getState() {
        return $this->_state;
    }

    private $_state;

}