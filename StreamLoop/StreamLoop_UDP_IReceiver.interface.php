<?php
interface StreamLoop_UDP_IReceiver {

    public function onReceive($ts, $message, $fromAddress, $fromPort); // @todo add this

}