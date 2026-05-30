<?php
trait FSM {

    // @todo разделить FSM на simple & transion & event, чтобы без if'a

    public function registerTransition($from, $to, $callback = false) {
        $this->_transitionArray[$from][$to] = $callback;
        $this->_transitions = true;
    }

    private function _updateState($state) {
        if ($this->_transitions) {
            if (isset($this->_transitionArray[$this->_state][$state])) {
                $this->_state = $state;
                $callback = $this->_transitionArray[$this->_state][$state];
                if ($callback) {
                    $callback();
                }
            }
        } else {
            $this->_state = $state;
        }
    }

    public function getState() {}

    private $_state;
    private $_transitionArray = [];
    private $_transitions = false;

}