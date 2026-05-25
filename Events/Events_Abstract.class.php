<?php
abstract class Events_Abstract extends Pattern_ASingleton {

    public function notify(string $eventName, ...$argumentArray) {
        if (isset($this->_listenerArray[$eventName])) {
            foreach ($this->_listenerArray[$eventName] as [$callback]) {
                $callback(...$argumentArray);
            }
        }
    }

    public function listen($eventName, callable $callback, $priority = 0) {
        // @todo параллельные массивы для приоритета?
        $this->_listenerArray[$eventName][] = [$callback, $priority];

        usort($this->_listenerArray[$eventName], function ($a, $b) {
            return $a[1] <=> $b[1];
        });
    }

    public function hasEvent($eventName) {
        return isset($this->_listenerArray[$eventName]);
    }

    private $_listenerArray = [];

}