<?php
abstract class FSM_Transition_Abstract extends FSM_Abstract{

    // @todo events сюда?

    public function registerTransition($from, $to, $callback = false) {
        $this->_transitionArray[$from][$to] = $callback;
    }

    protected function _updateState($state) {
        $stateOld = $this->getState();

        if (isset($this->_transitionArray[$stateOld][$state])) {
            parent::_updateState($state);
            $callback = $this->_transitionArray[$stateOld][$state];
            if ($callback) {
                $callback();
            }
        } else {
            throw new FSM_Exception("No transition from {$stateOld} to {$state}}");
        }
    }

    private $_transitionArray = [];

}