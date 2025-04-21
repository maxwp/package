<?php
interface EV_IHandler {

    public function notify(string $eventName, mixed ...$argumentArray);

    public function listen(string $eventName, $callback, int $priority = 0);

}