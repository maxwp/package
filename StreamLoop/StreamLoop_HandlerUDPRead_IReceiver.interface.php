<?php
interface StreamLoop_HandlerUDPRead_IReceiver {

    public function onReceive($ts, $message, $fromAddress, $fromPort);

}