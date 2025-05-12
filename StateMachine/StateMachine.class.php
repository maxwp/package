<?php
class StateMachine {

    public function getState(): string {
        return $this->_state;
    }

    public function setState(string $state): bool {
        if ($this->canTransitionTo($state)) {
            $this->_state = $state;
            $this->_onEnterState($state);

            return true;
        } else {
            // @todo events
            $this->_onInvalidTransition($this->_state, $state);

            return false;
        }
    }

    public function registerTransition(string $stateFrom, string $stateTo) {
        $this->_transitionArray[$stateFrom][$stateTo] = true;
    }

    public function unregisterTransition(string $stateFrom, string $stateTo) {
        unset($this->_transitionArray[$stateFrom][$stateTo]);
    }

    public function canTransitionTo(string $state): bool {
        return !empty($this->_transitionArray[$this->_state][$state]);
    }

    protected function _onEnterState(string $state): void {
        // Override in subclass for state-specific entry actions
    }

    protected function _onInvalidTransition(string $from, string $to): void {
        throw new StateMachine_Exception("Invalid transition from $from to $to");
    }


    private string $_state;
    private array $_transitionArray = [];

}