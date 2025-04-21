<?php
class EV_Internal implements EV_IHandler {

    public function notify(string $eventName, ...$argumentArray) {
        if (empty($this->_listenerArray[$eventName])) {
            return;
        }

        foreach ($this->_listenerArray[$eventName] as $listener) {
            $callback = $listener[0];
            $callback(...$argumentArray);
        }
    }

    public function listen(string $eventName, $callback, int $priority = 0) {
        $this->_listenerArray[$eventName][] = [$callback, $priority];
        // @todo priority sort
    }

    private $_listenerArray = [];

}