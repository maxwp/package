<?php
/**
 * @deprecated use ATimer
 */
interface StreamLoop_Timer_ICallback {

    public function onTimer(StreamLoop_Timer $handler, $tsSelect);

}