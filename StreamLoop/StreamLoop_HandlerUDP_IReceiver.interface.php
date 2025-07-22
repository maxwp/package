<?php
interface StreamLoop_HandlerUDP_IReceiver {

    public function onReceive($ts, $message, $fromAddress, $fromPort);

}